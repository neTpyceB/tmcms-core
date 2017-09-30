<?php

namespace TMCms\Files;

use RuntimeException;
use TMCms\Admin\Filemanager\Entity\FilePropertyEntityRepository;

defined('INC') or exit;

/**
 * Class FileSystem
 * @package TMCms\Files
 */
class FileSystem
{
    /**
     * Check that name of file or folder is allowed for file system and no odd symbols in it
     * @param string $file
     * @return bool
     */
    public static function checkFileName($file)
    {
        return strpos($file, '/') === false && strpos($file, '\\') === false && strpos($file, '..') === false;
    }

    /**
     * Set file permissions
     * @param string $path
     * @param int    $mode
     * @return bool
     */
    public static function chmod($path, $mode = NULL)
    {
        if (!isset($mode)) {
            if (is_file($path)) {
                $mode = CFG_DEFAULT_FILE_PERMISSIONS;
            } elseif (is_dir($path)) {
                $mode = CFG_DEFAULT_DIR_PERMISSIONS;
            } else {
                return false;
            }
        }
        $umask = umask();
        $res = chmod($path, $mode);
        umask($umask);

        return $res;
    }

    /**
     * Remove folder recursively with all content from file system
     * @param string $path
     * @param bool $leave_folder itself or delete it too
     * @return bool
     */
    public static function remdir($path, $leave_folder = false)
    {
        if (!is_dir($path)) {
            return false;
        }

        // Always have trailing slash
        $slash = substr($path, -1);
        if ($slash != '/' && $slash != '\\') {
            $path .= '/';
        }

        // Remove all inner folders
        foreach (array_diff(scandir($path), ['.', '..']) as $v) {
            if (is_dir($path . $v)) {
                self::remdir($path . $v . '/', $leave_folder);
            } elseif (!is_writable($path . $v) || !unlink($path . $v)) {
                return false;
            }
        }

        // Skip latest folder if required
        if ((!$leave_folder) && !rmdir($path)) {
            return false;
        }

        return true;
    }

    /**
     * Copy folder content to another directory
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public static function copyRecursive($source, $destination)
    {
        if (is_dir($source)) {
            $dir_handle = opendir($source);

            while ($file = readdir($dir_handle)) {
                if ($file != "." && $file != "..") {
                    self::mkDir($destination);

                    if (is_dir($source . "/" . $file)) {
                        if (!is_dir($destination . "/" . $file)) {
                            mkdir($destination . "/" . $file);
                        }
                        self::copyRecursive($source . "/" . $file, $destination . "/" . $file);
                    } else {
                        copy($source . "/" . $file, $destination . "/" . $file);
                    }
                }
            }
            closedir($dir_handle);
        } else {
            copy($source, $destination);
        }

        return true;
    }

    /**
     * Make folder
     *
     * @param string $path
     *
     * @return bool
     */
    public static function mkDir($path)
    {
        if (file_exists($path)) {
            return false;
        }

        $umask = umask();
        $res = @mkdir($path, CFG_DEFAULT_DIR_PERMISSIONS, true);
        if ($res === false) {
            throw new RuntimeException('Can not create directory "' . $path . '" - no permissions.');
        }
        umask($umask);

        return $res;
    }

    /**
     * Returns the directory and its content data recursively
     * @param string $path - Path to directory from where to start
     * @param bool   $onlyLevel
     * @param int    $lvl  - level
     * @return array with content
     */
    public static function scanDirs($path, $onlyLevel = false, $lvl = 0)
    {
        static $res;

        if ($lvl == 0) {
            $res = [];
        }

        if (!is_dir($path)) {
            return [];
        }

        if (substr($path, -1) == '/') {
            $path = substr($path, 0, -1);
        }
        $dir_base_length = strlen(DIR_BASE) - 1;

        foreach (array_diff(scandir($path), ['.', '..']) as $f) {
            if (is_dir($path . '/' . $f)) {
                if (!$onlyLevel) $res[] = [
                    'path_url' => substr($path, $dir_base_length),
                    'path'     => $path,
                    'name'     => $f,
                    'full'     => $path . '/' . $f,
                    'fs'       => 0,
                    'type'     => 'dir'
                ];
                self::scanDirs($path . '/' . $f, $onlyLevel, $lvl + 1);
            } elseif ($onlyLevel) {
                $res[] = $path . '/' . $f;
            } else {
                $res[] = [
                    'path_url' => substr($path, $dir_base_length),
                    'path'     => $path,
                    'name'     => $f,
                    'full'     => $path . '/' . $f,
                    'fs'       => filesize($path . '/' . $f),
                    'type'     => 'file'
                ];
            }
        }

        if ($lvl == 0) {
            return $res;
        }

        return [];
    }


    /**
     * Make file downloadable in browser
     * @param string $filename
     * @param string $res
     * @param string $mimetype
     */
    public static function streamOutput($filename, $res, $mimetype = 'application/octet-stream')
    {
        ob_clean();
        header('HTTP/1.1 200 OK');
        header('Content-Length: ' . strlen($res));
        header('Content-Type: ' . $mimetype);
        header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Pragma: no-cache', true);

        echo $res;

        exit;

        /**
         * This can be added to .htaccess to force download .pdf files
        <Files *.pdf>
        ForceType application/octet-stream
        Header set Content-Disposition attachment
        </Files>
         *
         * or prevent download
        <FilesMatch "\.(tex|log|aux)$">
        Header set Content-Type text/plain
        </FilesMatch>
         */
    }


    /**
     * Download selected array of data as one export file with headers
     * @param array $data
     */
    public static function downloadDataAsCsvFile($data)
    {
        ob_clean();
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=file.csv");
        // Disable caching
        header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
        header("Pragma: no-cache"); // HTTP 1.0
        header("Expires: 0"); // Proxies

        $output = fopen("php://output", "w");

        $first = reset($data);
        if ($first && is_array($first)) {
            // Keys as headers
            fputcsv($output, array_keys($first), ';');
        }

        // Rows with data
        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }

        exit;
    }

    /**
     * Generate base64 representation of image
     * @param string $image
     * @param string $mime
     * @return string
     */
    public static function getDataURI($image, $mime = '') {
        return 'data: ' . (function_exists('mime_content_type') ? mime_content_type($image) : $mime) . ';base64,' . base64_encode(file_get_contents($image));
    }

    /**
     * Get file's or folder's properties saved in Filemanager
     *
     * @param string $path to file or folder
     *
     * @return FilePropertyEntityRepository
     */
    public function getFileOrFolderProperties($path)
    {
        $properties = new FilePropertyEntityRepository;
        $properties->setWherePath($path);

        return $properties;
    }

    /**
     * Get  ALTs from ile properties
     * @param string $path to image file
     *
     * @return FilePropertyEntityRepository
     */
    public function getImageFileAltProperties($path)
    {
        $properties = new FilePropertyEntityRepository;
        $properties->setWherePath($path);
        $properties->addWhereFieldIsLike('key', 'alt_', false);

        return $properties;
    }

    public static function getImageFileAltProperty($path, $lng = LNG)
    {
        $properties = new FilePropertyEntityRepository;
        $properties->setWherePath($path);
        $properties->setWhereKey('alt_' . $lng);
        /* @var \TMCms\Admin\Filemanager\Entity\FilePropertyEntity $result */
        $result = $properties->getFirstObjectFromCollection();

        return $result ? $result->getValue() : '';
    }
}