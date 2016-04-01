<?php

namespace TMCms\Files;

defined('INC') or exit;

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
    private $dir;
    /**
     * @var string
     */
    private $file4unknown = 'unknown.gif';

    /**
     * Sets default icon directory if $dir is null
     * @param string $dir - directory with icons
     * @param string $file4unknown
     */
    private function __construct($dir = '', $file4unknown = '')
    {
        if ($dir) {
            $this->dir = $dir . (substr($dir, -1) !== '/' ? '/' : '');
        }

        if ($file4unknown) {
            $this->file4unknown = $file4unknown;
        }
    }

    /**
     * @param string $ext
     * @return array
     */
    public static function getIconUrlByExt($ext)
    {
        return self::getInstance()->getIconByExt($ext, 1, 1);
    }

    /**
     * Get icon for file by its extension
     * @param string $ext - file's extension
     * @param int $incl_path - include in result path to file or not: 0 - return only file name; 1 - return relative path; 2 - return absolute path;
     * @param bool $return_unknown - should the false or path to unknown.gif be returned in case icon for the extension not found
     * @return array
     */
    public function getIconByExt($ext, $incl_path = 0, $return_unknown = false)
    {
        $data = $this->get();
        $ext = strtolower($ext);
        $res = isset($data[$ext]) ? $data[$ext] : NULL;

        if (!$res) {
            if (!$return_unknown) {
                return false;
            }
            $res = $this->file4unknown;
        }

        switch ($incl_path) {
            case 0:
                return $res;
            case 1:
                return substr($this->dir, strlen(DIR_BASE) - 1) . $res;
            case 2:
                return $this->dir . $res;
        }
        return false;
    }

    /**
     * Get all available icons list
     * @param array $img_ext - possible extensions of files in scanned directory
     * @param array $skip - files to skip
     * @return array
     */
    public function get($img_ext = array('gif'), array $skip = array('unknown.gif', 'recycle.bin.empty.gif', 'recycle.bin.full.gif'))
    {
        if (is_string($img_ext)) {
            $img_ext = [$img_ext];
        } elseif (!is_array($img_ext)) {
            dump('Supply a list of possible extensions for icons.');
        }

        if (isset(self::$data[$this->dir])) {
            $data = self::$data[$this->dir];
        } else {
            $data = [];
            $img_ext = array_flip($img_ext);

            foreach (array_diff(scandir($this->dir),['.', '..']) as $f) {
                $exts = explode('.', $f);

                if (!isset($img_ext[array_pop($exts)])) {
                    continue;
                }

                foreach ($exts as $v) {
                    $data[$v] = $f;
                }
            }
            self::$data[$this->dir] = $data;
        }

        return $skip ? array_diff($data, $skip) : $data;
    }

    /**
     * @param string $dir
     * @param string $file4unknown
     * @return FileIcons
     */
    public static function getInstance($dir = '', $file4unknown = '')
    {
        return new self($dir, $file4unknown);
    }

    /**
     * Get icon for unknown extension
     * @return string
     */
    public function getIconForUnknown()
    {
        return $this->dir . $this->file4unknown;
    }

    /**
     * @return string
     */
    public function getFolderUrl()
    {
        return substr($this->dir, strlen(DIR_BASE) - 1);
    }
}