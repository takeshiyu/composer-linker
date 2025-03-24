<?php

declare(strict_types=1);

namespace TakeshiYu\Composer\Linker;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class LinkerService
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $globalLinksDir;

    /**
     * @var string
     */
    protected $linksFile;

    /**
     * @var array
     */
    protected $linksData = [];

    /**
     * LinkerService constructor.
     */
    public function __construct()
    {
        $this->filesystem = new Filesystem;
        $this->globalLinksDir = $this->getGlobalLinksDir();
        $this->linksFile = $this->globalLinksDir.'/links.json';

        // Create global links directory if it doesn't exist
        if (! $this->filesystem->exists($this->globalLinksDir)) {
            $this->filesystem->mkdir($this->globalLinksDir, 0755);
        }

        // Load existing links data
        $this->loadLinksData();
    }

    /**
     * Get the global links directory.
     *
     * @return string
     */
    public function getGlobalLinksDir()
    {
        // Use XDG Base Directory if available
        if (getenv('XDG_DATA_HOME')) {
            return rtrim(getenv('XDG_DATA_HOME'), '/').'/composer/links';
        }

        // Fall back to HOME directory
        $home = getenv('HOME');
        if (! $home && isset($_SERVER['HOMEDRIVE'], $_SERVER['HOMEPATH'])) {
            $home = $_SERVER['HOMEDRIVE'].$_SERVER['HOMEPATH'];
        }

        return $home.'/.composer/links';
    }

    /**
     * Load links data from the JSON file
     */
    protected function loadLinksData()
    {
        if ($this->filesystem->exists($this->linksFile)) {
            $content = file_get_contents($this->linksFile);
            $data = json_decode($content, true);

            if (is_array($data)) {
                $this->linksData = $data;
            }
        }
    }

    /**
     * Save links data to the JSON file
     */
    protected function saveLinksData()
    {
        file_put_contents(
            $this->linksFile,
            json_encode($this->linksData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Register a package globally.
     *
     * @param  string  $packagePath  Path to the package
     * @return array Result information
     */
    public function register($packagePath)
    {
        // Resolve the real path
        $packagePath = realpath($packagePath);

        if (! $this->filesystem->exists($packagePath.'/composer.json')) {
            return [
                'success' => false,
                'message' => "Package at '$packagePath' does not contain a composer.json file.",
            ];
        }

        $composerJsonContent = file_get_contents($packagePath.'/composer.json');
        $composerJson = json_decode($composerJsonContent, true);

        if (! isset($composerJson['name'])) {
            return [
                'success' => false,
                'message' => "The composer.json in '$packagePath' does not contain a 'name' property.",
            ];
        }

        $packageName = $composerJson['name'];

        // Initialize registered_packages if not exists
        if (! isset($this->linksData['registered_packages'])) {
            $this->linksData['registered_packages'] = [];
        }

        // Store package information
        $this->linksData['registered_packages'][$packageName] = [
            'path' => $packagePath,
            'autoload' => $composerJson['autoload'] ?? [],
            'time' => time(),
        ];

        // Save to the central links file
        $this->saveLinksData();

        return [
            'success' => true,
            'message' => "Package '$packageName' has been registered globally.",
            'package' => $packageName,
            'path' => $packagePath,
        ];
    }

    /**
     * Link a globally registered package to the current project.
     *
     * @param  string  $packageName  Package name
     * @return array Result information
     */
    public function link($packageName)
    {
        // Check if package is registered
        if (! isset($this->linksData['registered_packages'][$packageName])) {
            return [
                'success' => false,
                'message' => "Package '$packageName' is not registered. Run 'composer link' in the package directory first.",
            ];
        }

        $packageInfo = $this->linksData['registered_packages'][$packageName];
        $packagePath = $packageInfo['path'];

        if (! $this->filesystem->exists($packagePath)) {
            return [
                'success' => false,
                'message' => "The package directory '$packagePath' no longer exists.",
            ];
        }

        // Determine vendor directory
        $vendorDir = 'vendor';

        // Create package directory structure
        $packageDir = $vendorDir.'/'.$packageName;
        $targetDir = dirname($packageDir);

        if (! $this->filesystem->exists($targetDir)) {
            $this->filesystem->mkdir($targetDir, 0755);
        }

        // Backup existing package if it exists
        if ($this->filesystem->exists($packageDir)) {
            // Check if it's already a symbolic link
            if (is_link($packageDir)) {
                // If it's already a symbolic link, just remove it
                $this->filesystem->remove($packageDir);
            } else {
                // Only backup when it's not a symbolic link
                if ($this->filesystem->exists($packageDir.'.bak')) {
                    $this->filesystem->remove($packageDir.'.bak');
                }
                $this->filesystem->rename($packageDir, $packageDir.'.bak');
            }
        }

        try {
            // Create symbolic link
            $this->filesystem->symlink($packagePath, $packageDir);

            // Save link information to global links file
            $currentProject = realpath(getcwd());

            if (! isset($this->linksData['projects'])) {
                $this->linksData['projects'] = [];
            }

            if (! isset($this->linksData['projects'][$currentProject])) {
                $this->linksData['projects'][$currentProject] = [
                    'linked_packages' => [],
                ];
            }

            $this->linksData['projects'][$currentProject]['linked_packages'][$packageName] = $packagePath;
            $this->saveLinksData();

            return [
                'success' => true,
                'message' => "Package '$packageName' has been linked to the current project.",
                'package' => $packageName,
                'path' => $packagePath,
            ];
        } catch (IOExceptionInterface $exception) {
            // Restore backup if linking failed
            if ($this->filesystem->exists($packageDir.'.bak')) {
                if ($this->filesystem->exists($packageDir)) {
                    $this->filesystem->remove($packageDir);
                }
                $this->filesystem->rename($packageDir.'.bak', $packageDir);
            }

            return [
                'success' => false,
                'message' => 'Failed to create symbolic link: '.$exception->getMessage(),
            ];
        }
    }

    /**
     * Unlink a package from the current project.
     *
     * @param  string  $packageName  Package name
     * @return array Result information
     */
    public function unlink($packageName)
    {
        $currentProject = realpath(getcwd());

        // Check if the package is linked in this project
        $isLinked = isset($this->linksData['projects'][$currentProject]['linked_packages'][$packageName]);

        if (! $isLinked) {
            return [
                'success' => false,
                'message' => "Package '$packageName' is not linked in this project.",
            ];
        }

        $packagePath = $this->linksData['projects'][$currentProject]['linked_packages'][$packageName];
        $packageDir = 'vendor/'.$packageName;

        if (! $this->filesystem->exists($packageDir)) {
            // Clean up the link record even if package doesn't exist
            unset($this->linksData['projects'][$currentProject]['linked_packages'][$packageName]);
            $this->saveLinksData();

            return [
                'success' => false,
                'message' => "Package directory '$packageDir' does not exist.",
            ];
        }

        // Remove symbolic link
        $this->filesystem->remove($packageDir);

        // Restore backup if it exists
        if ($this->filesystem->exists($packageDir.'.bak')) {
            $this->filesystem->rename($packageDir.'.bak', $packageDir);
        }

        // Remove link record
        unset($this->linksData['projects'][$currentProject]['linked_packages'][$packageName]);

        // Clean up empty project entries
        if (empty($this->linksData['projects'][$currentProject]['linked_packages'])) {
            unset($this->linksData['projects'][$currentProject]);
        }

        $this->saveLinksData();

        return [
            'success' => true,
            'message' => "Package '$packageName' has been unlinked and restored to the installed version.",
            'package' => $packageName,
        ];
    }

    /**
     * List all linked packages in the current project.
     *
     * @return array List of linked packages
     */
    public function getLinkedPackages()
    {
        $currentProject = realpath(getcwd());

        if (! isset($this->linksData['projects'][$currentProject]['linked_packages'])) {
            return [];
        }

        $links = [];
        $linkedPackages = $this->linksData['projects'][$currentProject]['linked_packages'];

        foreach ($linkedPackages as $packageName => $packagePath) {
            $links[$packageName] = [
                'name' => $packageName,
                'path' => $packagePath,
                'exists' => $this->filesystem->exists($packagePath),
            ];
        }

        return $links;
    }

    /**
     * List all globally registered packages.
     *
     * @return array List of registered packages
     */
    public function getRegisteredPackages()
    {
        if (! isset($this->linksData['registered_packages'])) {
            return [];
        }

        $packages = [];
        foreach ($this->linksData['registered_packages'] as $packageName => $packageInfo) {
            $packages[$packageName] = [
                'name' => $packageName,
                'path' => $packageInfo['path'],
                'time' => $packageInfo['time'],
                'exists' => $this->filesystem->exists($packageInfo['path']),
            ];
        }

        return $packages;
    }

    /**
     * List all projects that have linked packages.
     *
     * @return array List of projects with their linked packages
     */
    public function getProjectsWithLinks()
    {
        return $this->linksData['projects'] ?? [];
    }
}
