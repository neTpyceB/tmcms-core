<?php
declare(strict_types=1);

namespace TMCms\Files;

use RuntimeException;
use TMCms\Strings\Converter;

defined('INC') or exit;

/**
 * Class Image
 * JPEG, PNG, GIF images modifications.
 */
class Image
{
    /**
     * Coefficient to increase memory calculated value
     */
    const FUDGE = 1.65;

    private $image_resource;
    private $image_width = 0;
    private $image_height = 0;
    private $image_type = 0; // 1 = gif, 2 = jpeg, 3 = png

    /**
     * @var array
     */
    private $actions_to_perform = [];

    /**
     * @return resource
     */
    public function getHandler()
    {
        return $this->image_resource;
    }

    /**
     * @param string $file_path
     * @param bool   $check_avail_mem
     *
     * @return bool
     *
     * @throws RuntimeException
     */
    public function open(string $file_path, bool $check_avail_mem = true): bool
    {
        if (!file_exists($file_path)) {
            return false;
        }

        if (!($imgData = getimagesize($file_path))) {
            return false;
        }

        $this->image_width = (int)$imgData[0];
        $this->image_height = (int)$imgData[1];

        if ($check_avail_mem && !$this->haveEnoughMemoryToOpenAndProcess()) {
            throw new RuntimeException('Empty can not be opened.');
        }

        $image = false;
        switch ($this->image_type = $imgData[2]) {
            case '1':

                $image = imagecreatefromgif($file_path);

                break;

            case '2':

                $image = imagecreatefromjpeg($file_path);

                break;

            case '3':

                $image = imagecreatefrompng($file_path);

                break;
        }

        if ($image) {
            $this->image_resource = $image;

            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function haveEnoughMemoryToOpenAndProcess()
    {
        return !$this->checkIfMemoryLimitReached((int)$this->getMemoryUsage($this->image_width, $this->image_height));
    }

    /**
     * @param int $width
     * @param int $height
     * @param int $multiply_by
     *
     * @return float
     */
    private function getMemoryUsage(int $width = 0, int $height = 0, $multiply_by = 1)
    {

        if (!$width) {
            $width = $this->image_width;
        }

        if (!$height) {
            $height = $this->image_height;
        }

        $depth_kilobytes = 65536; // 64 KB

        return ($width * $height * 4 + $depth_kilobytes) * self::FUDGE * $multiply_by;
    }

    /**
     * @param int $memory_required
     *
     * @return bool
     */
    private function checkIfMemoryLimitReached(int $memory_required): bool
    {
        return $memory_required >= Converter::formatIniParamSize(ini_get('memory_limit')) - memory_get_usage(true);
    }

    /**
     * Create empty image to work with, used for creation from non-source
     *
     * @param int  $width
     * @param int  $height
     * @param bool $true_color
     *
     * @return bool
     *
     * @throws RuntimeException
     */
    public function create(int $width, int $height, bool $true_color = true): bool
    {
        $this->image_width = $width;
        $this->image_height = $height;

        if (!$this->haveEnoughMemoryToOpenAndProcess()) {
            throw new RuntimeException('Empty can not be created.');
        }

        if ($true_color) {
            $img = imagecreatetruecolor($width, $height);
        } else {
            $img = imagecreate($width, $height);
        }

        $this->image_resource = $img;

        return true;
    }

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function interlace(bool $value = true)
    {
        $this->check();

        imageinterlace($this->image_resource, $value);

        return $this;
    }

    /**
     * @return bool
     *
     * @throws RuntimeException
     */
    private function check(): bool
    {
        if (!$this->image_resource) {
            throw new RuntimeException('Image resource is not set');
        }

        return true;
    }

    /**
     * @param int $r
     * @param int $g
     * @param int $b
     *
     * @return $this
     */
    public function setBackground(int $r = 255, int $g = 255, int $b = 255)
    {
        $this->check();

        $c = imagecolorallocate($this->image_resource, $r, $g, $b);

        imagefilledrectangle($this->image_resource, 0, 0, $this->image_width, $this->image_height, $c);

        return $this;
    }

    /**
     * @param int  $width
     * @param int  $height
     * @param bool $resample
     *
     * @return $this
     */
    public function resize(int $width, int $height, bool $resample = true)
    {
        $this->actions_to_perform[] = ['resize', ['width' => $width, 'height' => $height, 'resample' => $resample]];

        return $this;
    }

    /**
     * @param int  $width
     * @param int  $height
     * @param bool $resample
     *
     * @return $this
     */
    public function resizeFit(int $width, int $height, bool $resample = true)
    {
        $this->actions_to_perform[] = ['resizeFit', ['width' => $width, 'height' => $height, 'resample' => $resample]];

        return $this;
    }

    /**
     * @param int    $radius
     * @param string $background_color
     * @param int    $aa_level
     *
     * @return $this
     */
    public function roundedCorners(int $radius, string $background_color, int $aa_level = 4)
    {
        $this->actions_to_perform[] = ['roundedCorners', ['radius' => $radius, 'background_color' => $background_color, 'aa_lvl' => $aa_level]];

        return $this;
    }

    /**
     * @param string $watermark_image
     * @param string $position
     *
     * @return $this
     */
    public function watermark(string $watermark_image, string $position)
    {
        $this->actions_to_perform[] = ['watermark', ['watermark_image' => $watermark_image, 'position' => $position]];

        return $this;
    }

    /**
     * @param int $position_x1
     * @param int $position_y1
     * @param int $position_x2
     * @param int $position_y2
     *
     * @return array
     */
    public function getAverageColorInRegion(int $position_x1, int $position_y1, int $position_x2, int $position_y2): array
    {
        $this->check();

        $r = $g = $b = 0;

        for ($x = $position_x1; $x < $position_x2; ++$x) {
            for ($y = $position_y1; $y < $position_y2; ++$y) {
                $rgb = imagecolorat($this->image_resource, $x, $y);
                $r += ($rgb >> 16) & 0xFF;
                $g += ($rgb >> 8) & 0xFF;
                $b += $rgb & 0xFF;
            }
        }

        $so = ($position_x2 - $position_x1) * ($position_y2 - $position_y1);

        return [round($r / $so), round($g / $so), round($b / $so)];
    }

    /**
     * @return $this
     */
    public function grayscale()
    {
        $this->actions_to_perform[] = ['grayscale'];

        return $this;
    }

    /**
     * @param string $format
     * @param int    $quality
     */
    public function output(string $format = 'jpeg', int $quality = 80)
    {
        $this->check();
        $this->apply();

        ob_clean();
        switch ($format) {
            default: // JPEG
                header('Content-type: image/jpeg');
                imagejpeg($this->image_resource, NULL, $quality);
                break;
            case 'gif':
                header('Content-type: image/gif');
                imagegif($this->image_resource);
                break;
            case 'png':
                header('Content-type: image/png');
                imagepng($this->image_resource, NULL, abs($quality / 10 - 10));
                break;
        }
    }

    /**
     * @return bool
     */
    public function apply(): bool
    {
        foreach ($this->actions_to_perform as $action) {
            switch ($action[0]) {
                case 'roundedCorners':
                    $params = $action[1];
                    $this->_roundedCorners($params['radius'], $params['background_color'], $params['aa_lvl']);
                    break;

                case 'grayscale':
                    $this->_grayscale();
                    break;

                case 'resize':
                    $params = $action[1];
                    $this->_resize($params['width'], $params['height'], $params['resample']);
                    break;

                case 'resizeFit':
                    $params = $action[1];
                    $this->_resizeFit($params['width'], $params['height'], $params['resample']);
                    break;

                case 'unsharpMask':
                    $params = $action[1];
                    $this->_unsharpMask($params['amount'], $params['radius'], $params['threshold']);
                    break;

                case 'watermark':
                    $params = $action[1];
                    $this->_watermark($params['watermark_image'], $params['position']);
                    break;
            }
        }

        $this->actions_to_perform = [];

        return true;
    }

    /**
     * @param int    $radius
     * @param string $background_color
     * @param int    $aa_level
     *
     * @return $this
     */
    public function _roundedCorners(int $radius, string $background_color, int $aa_level = 4)
    {
        if ($aa_level > 1) {
            $radius *= $aa_level;
        }

        $ih_corner = imagecreatetruecolor($radius, $radius);

        $r_dec = hexdec($background_color[0] . $background_color[1]);
        $g_dec = hexdec($background_color[2] . $background_color[3]);
        $b_dec = hexdec($background_color[4] . $background_color[5]);

        $cr_clear = imagecolorallocate($ih_corner, ($r_dec ? $r_dec - 1 : 1), ($g_dec ? $g_dec - 1 : 1), ($b_dec ? $b_dec - 1 : 1));
        $cr_solid = imagecolorallocate($ih_corner, $r_dec, $g_dec, $b_dec);
        imagecolortransparent($ih_corner, $cr_clear);

        imagefill($ih_corner, 0, 0, $cr_solid);
        imagefilledellipse($ih_corner, $radius, $radius, $radius * 2, $radius * 2, $cr_clear);

        if ($aa_level > 1) {
            $new_radius = $radius / $aa_level;
            $res = imagecreatetruecolor($new_radius, $new_radius);

            if ($this->image_type == 3) {
                imagealphablending($res, false);
                imagesavealpha($res, true);
            }

            imagecolortransparent($res, $cr_clear);
            imagecopyresampled($res, $ih_corner, 0, 0, 0, 0, $new_radius, $new_radius, $radius, $radius);
            imagedestroy($ih_corner);
            $ih_corner = $res;
            $radius = $new_radius;
        }

        $this->image_height = imagesy($this->image_resource);
        $this->image_width = imagesx($this->image_resource);

        imagecopymerge($this->image_resource, $ih_corner, 0, 0, 0, 0, $radius, $radius, 100);

        $ih_corner = imagerotate($ih_corner, 90, 0);
        imagecopymerge($this->image_resource, $ih_corner, 0, $this->image_height - $radius, 0, 0, $radius, $radius, 100);

        $ih_corner = imagerotate($ih_corner, 90, 0);
        imagecopymerge($this->image_resource, $ih_corner, $this->image_width - $radius, $this->image_height - $radius, 0, 0, $radius, $radius, 100);

        $ih_corner = imagerotate($ih_corner, 90, 0);
        imagecopymerge($this->image_resource, $ih_corner, $this->image_width - $radius, 0, 0, 0, $radius, $radius, 100);

        return $this;
    }

    /**
     * @return $this
     */
    private function _grayscale()
    {
        $this->check();

        $height = $this->image_height;
        $width = $this->image_width;
        $res = imagecreatetruecolor($width, $height);

        $palette = [];
        for ($c = 0; $c < 256; ++$c) {
            $palette[$c] = imagecolorallocate($this->image_resource, $c, $c, $c);
        }

        for ($y = 0; $y < $height; ++$y) {
            for ($x = 0; $x < $width; ++$x) {
                $rgb = imagecolorat($this->image_resource, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                imagesetpixel($res, $x, $y, $palette[$r * 0.299 + $g * 0.587 + $b * 0.114]);
            }
        }

        imagedestroy($this->image_resource);
        $this->image_resource = $res;

        return $this;
    }

    /**
     * Resize
     *
     * @param int  $width
     * @param int  $height
     * @param bool $resample
     *
     * @return $this
     * @throws RuntimeException
     */
    private function _resize(int $width, int $height, bool $resample)
    {
        $this->check();

        $src_w = $this->image_width;
        $src_h = $this->image_height;

        $xscale = $src_w / $width;
        $yscale = $src_h / $height;

        if ($yscale > $xscale) {
            $dst_w = (int)round($src_w * (1 / $yscale));
            $dst_h = (int)round($src_h * (1 / $yscale));
        } else {
            $dst_w = (int)round($src_w * (1 / $xscale));
            $dst_h = (int)round($src_h * (1 / $xscale));
        }

        $res = imagecreatetruecolor($dst_w, $dst_h);

        if ($this->image_type == 3) {
            imagealphablending($res, false);
            imagesavealpha($res, true);
        }
        if ($resample) {
            imagecopyresampled($res, $this->image_resource, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
        } else {
            imagecopyresized($res, $this->image_resource, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
        }

        imagedestroy($this->image_resource);

        $this->image_resource = $res;
        $this->image_width = $dst_w;
        $this->image_height = $dst_h;

        return $this;
    }

    /**
     * Resize fit with crop of redundant part
     *
     * @param $width
     * @param $height
     * @param $resample
     *
     * @return $this
     * @throws RuntimeException
     */
    private function _resizeFit(int $width, int $height, bool $resample)
    {
        $this->check();

        $src_w = $this->image_width;
        $src_h = $this->image_height;

        $k = (($src_w / $src_h) > ($width / $height) ? $src_h / $height : $src_w / $width);

        $resW = (int)round($src_w / $k);
        $resH = (int)round($src_h / $k);

        $src_x = (int)(floor(($resW - $width) / 2) * $k);
        $src_y = (int)(floor(($resH - $height) / 2) * $k);

        $res = imagecreatetruecolor($width, $height);

        if ($this->image_type == 3) {
            imagealphablending($res, false);
            imagesavealpha($res, true);
        }

        if ($resample) {
            imagecopyresampled($res, $this->image_resource, 0, 0, $src_x, $src_y, $resW, $resH, $src_w, $src_h);
        } else {
            imagecopyresized($res, $this->image_resource, 0, 0, $src_x, $src_y, $resW, $resH, $src_w, $src_h);
        }

        imagedestroy($this->image_resource);

        $this->image_resource = $res;
        $this->image_width = $resW;
        $this->image_height = $resH;

        return $this;
    }

    /**
     * @param int $amount
     * @param int $radius
     * @param int $threshold
     *
     * @return $this
     */
    private function _unsharpMask(int $amount, int $radius, int $threshold)
    {
        $this->check();

        $img = $this->image_resource;
        // $img is an image that is already created within php using
        // imgcreatetruecolor. No url! $img must be a truecolor image.

        // Attempt to calibrate the parameters to Photoshop:
        if ($amount > 500) {
            $amount = 500;
        }

        $amount = $amount * 0.016;
        $radius = abs($radius);

        if ($radius > 50) {
            $radius = 50;
        }

        $radius = round($radius * 2);

        if ($threshold > 255) {
            $threshold = 255;
        }

        if (!$radius) {
            // No need to modify
            return $this;
        }
        $w = $this->image_width;
        $h = $this->image_height;
        $imgCanvas = imagecreatetruecolor($w, $h);
        $imgBlur = imagecreatetruecolor($w, $h);

        /*
         Gaussian blur matrix:
         1	2	1
         2	4	2
         1	2	1
         */
        if (function_exists('imageconvolution')) { // PHP >= 5.1
            imagecopy($imgBlur, $img, 0, 0, 0, 0, $w, $h);
            imageconvolution($imgBlur, [
                [1, 2, 1],
                [2, 4, 2],
                [1, 2, 1],
            ], 16, 0);
        } else {
            // Move copies of the image around one pixel at the time and merge them with weight according to the matrix. The same matrix is simply repeated for higher radii.
            for ($i = 0; $i < $radius; ++$i) {
                imagecopy($imgBlur, $img, 0, 0, 1, 0, $w - 1, $h); // left
                imagecopymerge($imgBlur, $img, 1, 0, 0, 0, $w, $h, 50); // right
                imagecopymerge($imgBlur, $img, 0, 0, 0, 0, $w, $h, 50); // center
                imagecopy($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h);

                imagecopymerge($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 33.33333); // up
                imagecopymerge($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 25); // down
            }
        }

        if ($threshold) {
            // Calculate the difference between the blurred pixels and the original and set the pixels
            $so = $w - 1;
            for ($x = 0; $x < $so; ++$x) { // each row
                for ($y = 0; $y < $h; ++$y) { // each pixel
                    $rgbOrig = imagecolorat($img, $x, $y);
                    $rOrig = (($rgbOrig >> 16) & 0xFF);
                    $gOrig = (($rgbOrig >> 8) & 0xFF);
                    $bOrig = ($rgbOrig & 0xFF);

                    $rgbBlur = imagecolorat($imgBlur, $x, $y);

                    $rBlur = (($rgbBlur >> 16) & 0xFF);
                    $gBlur = (($rgbBlur >> 8) & 0xFF);
                    $bBlur = ($rgbBlur & 0xFF);

                    // When the masked pixels differ less from the original than the threshold specifies, they are set to their original value.
                    $rNew = (abs($rOrig - $rBlur) >= $threshold) ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig)) : $rOrig;
                    $gNew = (abs($gOrig - $gBlur) >= $threshold) ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig)) : $gOrig;
                    $bNew = (abs($bOrig - $bBlur) >= $threshold) ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig)) : $bOrig;

                    if ($rOrig != $rNew || $gOrig != $gNew || $bOrig != $bNew) {
                        imagesetpixel($img, $x, $y, imagecolorallocate($img, $rNew, $gNew, $bNew));
                    }
                }
            }
        } else {
            for ($x = 0; $x < $w; ++$x) { // each row
                for ($y = 0; $y < $h; ++$y) { // each pixel
                    $rgbOrig = imagecolorat($img, $x, $y);
                    $rOrig = (($rgbOrig >> 16) & 0xFF);
                    $gOrig = (($rgbOrig >> 8) & 0xFF);
                    $bOrig = ($rgbOrig & 0xFF);

                    $rgbBlur = imagecolorat($imgBlur, $x, $y);

                    $rNew = ($amount * ($rOrig - (($rgbBlur >> 16) & 0xFF))) + $rOrig;

                    if ($rNew > 255) {
                        $rNew = 255;
                    } elseif ($rNew < 0) {
                        $rNew = 0;
                    }

                    $gNew = ($amount * ($gOrig - (($rgbBlur >> 8) & 0xFF))) + $gOrig;

                    if ($gNew > 255) {
                        $gNew = 255;
                    } elseif ($gNew < 0) {
                        $gNew = 0;
                    }

                    $bNew = ($amount * ($bOrig - ($rgbBlur & 0xFF))) + $bOrig;

                    if ($bNew > 255) {
                        $bNew = 255;
                    } elseif ($bNew < 0) {
                        $bNew = 0;
                    }

                    imagesetpixel($img, $x, $y, ($rNew << 16) + ($gNew << 8) + $bNew);
                }
            }
        }

        imagedestroy($imgCanvas);
        imagedestroy($imgBlur);

        return $this;
    }

    /**
     *
     * @param string $wm_image - path to watermark file
     * @param string $pos      - position of watermark
     *
     * @return $this
     * @throws RuntimeException
     */
    public function _watermark(string $wm_image, string $pos)
    {
        $this->check();

        $wm_image = DIR_BASE . $wm_image;

        if (!file_exists($wm_image)) {
            throw new RuntimeException('Watermark file not found');
        }

        switch (pathinfo($wm_image, PATHINFO_EXTENSION)) {
            default:
                exit('Unsupported extension');

            case 'png':
                $wm_img = imagecreatefrompng($wm_image);
                break;

            case 'jpg':
            case 'jpeg':
                $wm_img = imagecreatefromjpeg($wm_image);
                break;
        }

        $wm_w = imagesx($wm_img);
        $wm_h = imagesy($wm_img);
        $pos = strtolower($pos);

        if ($pos === 'random') {
            $pos = array_rand(array_flip(['right_bottom', 'left_bottom', 'left_top', 'right_top', 'center']));
        }

        switch (strtolower($pos)) {
            default:
            case 'right_bottom':
                $dest_x = imagesx($this->image_resource) - $wm_w - 5;
                $dest_y = imagesy($this->image_resource) - $wm_h - 5;
                break;

            case 'left_bottom':
                $dest_x = 5;
                $dest_y = imagesy($this->image_resource) - $wm_h - 5;
                break;

            case 'left_top':
                $dest_x = 5;
                $dest_y = 5;
                break;

            case 'right_top':
                $dest_x = imagesx($this->image_resource) - $wm_w - 5;
                $dest_y = 5;
                break;

            case 'center':
                $dest_x = imagesx($this->image_resource) / 2 - $wm_w / 2;
                $dest_y = imagesy($this->image_resource) / 2 - $wm_h / 2;
                break;
        }

        imagecopy($this->image_resource, $wm_img, $dest_x, $dest_y, 0, 0, $wm_w, $wm_h);
        imagedestroy($wm_img);

        return $this;
    }

    /**
     * @param string $dst
     * @param string $format
     * @param int    $quality
     *
     * @return bool
     * @throws RuntimeException
     */
    public function save($dst, $format = 'jpeg', $quality = 80): bool
    {
        $this->check();

        if (!$this->actionsPossible()) {
            throw new RuntimeException('Actions can not be applied.');
        }

        if (!$this->apply()) {
            return false;
        }

        switch (strtolower($format)) {
            case 'jpg':
            case 'jpeg':
                return imagejpeg($this->image_resource, $dst, $quality);

            case 'gif':
                return imagegif($this->image_resource, $dst);

            case 'png':
                return imagepng($this->image_resource, $dst, abs($quality / 10 - 10));

        }

        return false;
    }

    /**
     * @param int $overhead
     *
     * @return bool
     */
    public function actionsPossible(int $overhead = 0): bool
    {
        $w = $this->image_width;
        $h = $this->image_height;
        $maxMemoryUsage = $this->getMemoryUsage($w, $h);

        foreach ($this->actions_to_perform as $action) {
            $action_type = &$action[0];

            switch ($action_type) {
                case 'grayscale':
                    $memNeeded = $this->getMemoryUsage($w, $h);
                    if ($memNeeded > $maxMemoryUsage) {
                        $maxMemoryUsage = $memNeeded;
                    }
                    break;

                case 'resize':
                case 'resizeFit':
                    $params = $action[1];
                    $memNeeded = $this->getMemoryUsage($w, $h);

                    if ($memNeeded > $maxMemoryUsage) {
                        $maxMemoryUsage = $memNeeded;
                    }

                    $w = $params['width'];
                    $h = $params['height'];
                    break;

                case 'unsharpMask':
                    $memNeeded = $this->getMemoryUsage($w, $h, 2.5);

                    if ($memNeeded > $maxMemoryUsage) {
                        $maxMemoryUsage = $memNeeded;
                    }
                    break;
            }
        }
        $maxMemoryUsage = $maxMemoryUsage + $overhead;

        if ($this->checkIfMemoryLimitReached((int)$maxMemoryUsage)) {
            return false;
        }

        return true;
    }

    /**
     * @param int $amount
     * @param int $radius
     * @param int $threshold
     *
     * @return $this
     */
    public function unsharpMask($amount = 80, $radius = 1, $threshold = 1)
    {
        $this->actions_to_perform[] = ['unsharpMask', ['amount' => $amount, 'radius' => $radius, 'threshold' => $threshold]];

        return $this;
    }

    public function __destruct()
    {
        $this->check();

        imagedestroy($this->image_resource);

        $this->image_resource = false;
        $this->image_width = 0;
        $this->image_height = 0;
    }

    /**
     * @param $p_sFile
     *
     * @return resource
     */
    public function imagecreatefrombmp($p_sFile)
    {
        $width = $height = 0;

        //    Load the image into a string
        $file = fopen($p_sFile, "rb");
        $read = fread($file, 10);

        while (!feof($file) && ($read !== "")) {
            $read .= fread($file, 1024);
        }

        $temp = unpack("H*", $read);
        $hex = $temp[1];
        $header = substr($hex, 0, 108);

        //    Process the header
        //    Structure: http://www.fastgraph.com/help/bmp_header_format.html
        if (substr($header, 0, 4) === "424d") {
            //    Cut it in parts of 2 bytes
            $header_parts = str_split($header, 2);

            //    Get the width        4 bytes
            $width = hexdec($header_parts[19] . $header_parts[18]);

            //    Get the height        4 bytes
            $height = hexdec($header_parts[23] . $header_parts[22]);
        }

        //    Define starting X and Y
        $x = 0;
        $y = 1;

        //    Create newimage
        $image = imagecreatetruecolor($width, $height);

        //    Grab the body from the image
        $body = substr($hex, 108);

        //    Calculate if padding at the end-line is needed
        //    Divided by two to keep overview.
        //    1 byte = 2 HEX-chars
        $body_size = (strlen($body) / 2);
        $header_size = ($width * $height);

        //    Use end-line padding? Only when needed
        $usePadding = ($body_size > ($header_size * 3) + 4);

        //    Using a for-loop with index-calculation instead of str_split to avoid large memory consumption
        //    Calculate the next DWORD-position in the body
        for ($i = 0; $i < $body_size; $i += 3) {
            //    Calculate line-ending and padding
            if ($x >= $width) {
                //    If padding needed, ignore image-padding
                //    Shift i to the ending of the current 32-bit-block
                if ($usePadding)
                    $i += $width % 4;

                //    Reset horizontal position
                $x = 0;

                //    Raise the height-position (bottom-up)
                $y++;

                //    Reached the image-height? Break the for-loop
                if ($y > $height)
                    break;
            }

            //    Calculation of the RGB-pixel (defined as BGR in image-data)
            //    Define $i_pos as absolute position in the body
            $i_pos = $i * 2;
            $r = hexdec($body[$i_pos + 4] . $body[$i_pos + 5]);
            $g = hexdec($body[$i_pos + 2] . $body[$i_pos + 3]);
            $b = hexdec($body[$i_pos] . $body[$i_pos + 1]);

            //    Calculate and draw the pixel
            $color = imagecolorallocate($image, $r, $g, $b);
            imagesetpixel($image, $x, $height - $y, $color);

            //    Raise the horizontal position
            $x++;
        }

        //    Return image-object
        return $image;
    }
}
