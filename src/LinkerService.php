<?php

declare(strict_types=1);

namespace TakeshiYu\Linker;

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
    protected $localLinksDir = '.composer-links';

    /**
     * LinkerService constructor.
     */
    public function __construct()
    {
        $this->filesystem = new Filesystem;
        $this->globalLinksDir = $this->getGlobalLinksDir();

        // Create global links directory if it doesn't exist
        if (! $this->filesystem->exists($this->globalLinksDir)) {
            $this->filesystem->mkdir($this->globalLinksDir, 0755);
        }
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

        // Store package information
        $linkInfo = [
            'path' => $packagePath,
            'name' => $packageName,
            'autoload' => $composerJson['autoload'] ?? [],
            'time' => time(),
        ];

        // Create global links directory if it doesn't exist
        if (! $this->filesystem->exists($this->globalLinksDir)) {
            $this->filesystem->mkdir($this->globalLinksDir, 0755);
        }

        // Save link information
        file_put_contents(
            $this->globalLinksDir.'/'.$this->sanitizeFilename($packageName).'.json',
            json_encode($linkInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

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
        $linkFile = $this->globalLinksDir.'/'.$this->sanitizeFilename($packageName).'.json';

        if (! $this->filesystem->exists($linkFile)) {
            return [
                'success' => false,
                'message' => "Package '$packageName' is not registered. Run 'composer link' in the package directory first.",
            ];
        }

        $linkInfo = json_decode(file_get_contents($linkFile), true);
        $packagePath = $linkInfo['path'];

        if (! $this->filesystem->exists($packagePath)) {
            return [
                'success' => false,
                'message' => "The package directory '{$linkInfo['path']}' no longer exists.",
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
            // Rename instead of removing to preserve any modifications
            if ($this->filesystem->exists($packageDir.'.bak')) {
                $this->filesystem->remove($packageDir.'.bak');
            }
            $this->filesystem->rename($packageDir, $packageDir.'.bak');
        }

        try {
            // Create symbolic link
            $this->filesystem->symlink($packagePath, $packageDir);

            // Create local links directory if needed
            if (! $this->filesystem->exists($this->localLinksDir)) {
                $this->filesystem->mkdir($this->localLinksDir, 0755);
            }

            // Save local link information
            file_put_contents(
                $this->localLinksDir.'/'.$this->sanitizeFilename($packageName),
                $packagePath
            );

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
        $localLinkFile = $this->localLinksDir.'/'.$this->sanitizeFilename($packageName);

        if (! $this->filesystem->exists($localLinkFile)) {
            return [
                'success' => false,
                'message' => "Package '$packageName' is not linked in this project.",
            ];
        }

        $packagePath = trim(file_get_contents($localLinkFile));
        $packageDir = 'vendor/'.$packageName;

        if (! $this->filesystem->exists($packageDir)) {
            // Clean up the link file even if package doesn't exist
            $this->filesystem->remove($localLinkFile);

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

        // Remove link file
        $this->filesystem->remove($localLinkFile);

        return [
            'success' => true,
            'message' => "Package '$packageName' has been unlinked and restored to the installed version.",
            'package' => $packageName,
        ];
    }

    /**
     * Sanitize a package name for use as a filename.
     *
     * @param  string  $packageName  Package name
     * @return string Sanitized filename
     */
    protected function sanitizeFilename($packageName)
    {
        return str_replace('/', '--', $packageName);
    }
}
