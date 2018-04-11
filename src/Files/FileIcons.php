<?php
declare(strict_types=1);

namespace TMCms\Files;

\defined('INC') or exit;

/**
 * Class FileIcons
 */
class FileIcons
{
    /**
     * @var array
     */
    private static $data = [];
    /**
     * @var string
     */
    private $directory_with_icons;
    /**
     * @var string
     */
    private $unknown_extension_icon = 'unknown.gif';

    /**
     * @param string $directory_with_icons - directory with icons
     * @param string $unknown_extension_icon
     */
    private function __construct(string $directory_with_icons = '', string $unknown_extension_icon = '')
    {
        if (!$directory_with_icons) {
            $directory_with_icons = '/vendor/devp-eu/tmcms-core/src/assets/images/icons/';
        }

        if ($directory_with_icons) {
            $this->directory_with_icons = $directory_with_icons . (substr($directory_with_icons, -1) !== '/' ? '/' : '');
        }

        if ($unknown_extension_icon) {
            $this->unknown_extension_icon = $unknown_extension_icon;
        }
    }

    /**
     * Get full path to icon
     *
     * @param string $extension
     *
     * @return string
     */
    public static function getIconUrlByExtension(string $extension): string
    {
        return self::getInstance()->getIconByExtension($extension, true);
    }

    /**
     * Get icon for file by its extension
     *
     * @param string $extension - file's extension
     * @param bool $include_full_path - include in result relative path to file or not
     *
     * @return string
     */
    public function getIconByExtension(string $extension, bool $include_full_path = false): string
    {
        $data = $this->getData();
        $extension = strtolower($extension);
        $res = $data[$extension] ?? '';

        if (!$res) {
            $res = $this->unknown_extension_icon;
        }

        if ($include_full_path) {
            $res = $this->directory_with_icons . $res;
        }

        return $res;
    }

    /**
     * Get all available icons list
     *
     * @param array $image_extensions - possible extensions of files in scanned directory
     * @param array $skip - files to skip
     *
     * @return array
     */
    public function getData(array $image_extensions = ['gif'], array $skip = ['unknown.gif', 'recycle.bin.empty.gif', 'recycle.bin.full.gif']): array
    {
        // Cached
        if (isset(self::$data[$this->directory_with_icons])) {
            $data = self::$data[$this->directory_with_icons];
        } else {
            // Scan folder
            $data = [];
            $image_extensions = array_flip($image_extensions);

            foreach (array_diff(scandir(DIR_BASE . $this->directory_with_icons, SCANDIR_SORT_NONE),['.', '..']) as $file) {
                $extension = explode('.', $file);

                if (!isset($image_extensions[array_pop($extension)])) {
                    continue;
                }

                foreach ($extension as $v) {
                    $data[$v] = $file;
                }
            }

            // Save in cache
            self::$data[$this->directory_with_icons] = $data;
        }

        return $skip ? array_diff($data, $skip) : $data;
    }

    /**
     * @param string $dir
     * @param string $file4unknown
     *
     * @return $this
     */
    public static function getInstance($dir = '', $file4unknown = ''): self
    {
        return new self($dir, $file4unknown);
    }

    /**
     * Get icon for unknown extension
     *
     * @return string
     */
    public function getIconForUnknownExtension(): string
    {
        return $this->directory_with_icons . $this->unknown_extension_icon;
    }

    /**
     * @return string
     */
    public function getFolderUrl(): string
    {
        return $this->directory_with_icons;
    }
}
