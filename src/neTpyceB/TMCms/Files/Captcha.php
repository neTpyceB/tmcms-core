<?php

namespace neTpyceB\TMCms\Files;

defined('INC') or exit;

/**
 * Class Captcha
 */
class Captcha
{
    private $chars = 'abdefhknrstyz23456789';
    private $min_length = 3;
    private $max_length = 5;

    /**
     * @param string $chars
     * @return $this
     */
    public function setChars($chars)
    {
        $this->chars = $chars;

        return $this;
    }

    /**
     * @param int $length
     * @return $this
     */
    public function setMinLength($length)
    {
        $this->min_length = $length;

        return $this;
    }

    /**
     * @param int $length
     * @return $this
     */
    public function setMaxLength($length)
    {
        $this->max_length = $length;

        return $this;
    }

    /**
     * @return array [code, image_path]
     */
    public function getGeneratedData()
    {
        $code = $this->generate_code();

        return array('code' => $code, 'img' => $this->getImage($code));
    }

    /**
     * Generates code for visitor
     * @return string
     */
    private function generate_code()
    {
        $length = rand($this->min_length, $this->max_length);
        $numChars = strlen($this->chars);

        $str = [];
        for ($i = 0; $i < $length; $i++) {
            $str[] = substr($this->chars, rand(1, $numChars) - 1, 1);
        }
        $str = implode('', $str);

        // Mix string
        $array_mix = preg_split('//', $str, -1, PREG_SPLIT_NO_EMPTY);
        srand(microtime(true));
        shuffle($array_mix);

        return implode('', $array_mix);
    }

    /**
     * Generates image with code
     * @param string $code
     * @return string
     */
    private function getImage($code)
    {
        if (!is_dir(DIR_CACHE . 'captcha/')) {
            FileSystem::mkDir(DIR_CACHE . 'captcha/');
        }

        $filename_url = DIR_CACHE_URL . 'captcha/' . VISITOR_HASH . md5($code) . '.png';
        $filename = DIR_BASE . $filename_url;

        // Already generated
        if (file_exists($filename)) {
            return $filename_url;
        }

        // Placeholders
        $linenum = rand($this->min_length, $this->max_length);
        $img_arr = array(
            'captcha_empty.png',
            'captcha_empty_black.png'
        );

        // Fonts, can set multiple
        $font_arr = [];
        $font_arr[0]['fname'] = 'droidsans.ttf'; // TODO place fonts in folder
        $font_arr[0]['size'] = rand(15, 20); // Size in pt

        // Random font
        $n = rand(0, count($font_arr) - 1);
        $img_fn = $img_arr[rand(0, count($img_arr) - 1)];
        $image_handler = imagecreatefrompng(DIR_CMS_IMAGES . $img_fn);

        // Fill
        for ($i = 0; $i < $linenum; $i++) {
            $color = imagecolorallocate($image_handler, rand(0, 150), rand(0, 100), rand(0, 150));
            imageline($image_handler, rand(0, 15), rand(1, 15), rand(100, 160), rand(1, 50), $color);
        }
        $color = imagecolorallocate($image_handler, rand(0, 200), 0, rand(0, 200));

        $x = rand(0, 5);
        for ($i = 0; $i < strlen($code); $i++) {
            $x += 15;
            $letter = substr($code, $i, 1);
            imagettftext($image_handler, $font_arr[$n]['size'], rand(2, 4), $x, rand(20, 25), $color, DIR_ASSETS . 'fonts/' . $font_arr[$n]['fname'], $letter);
        }

        for ($i = 0; $i < $linenum; $i++) {
            $color = imagecolorallocate($image_handler, rand(0, 255), rand(0, 200), rand(0, 255));
            imageline($image_handler, rand(0, 20), rand(1, 50), rand(150, 180), rand(1, 50), $color);
        }

        // Save file
        ImagePNG($image_handler, $filename);
        ImageDestroy($image_handler);
        return $filename_url;
    }
}