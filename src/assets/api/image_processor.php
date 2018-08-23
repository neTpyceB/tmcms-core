<?php
declare(strict_types=1);

use TMCms\Config\Configuration;
use TMCms\Config\Settings;
use TMCms\Files\FileSystem;
use TMCms\Files\Image;
use TMCms\Middleware\MiddlewareHandler;
use TMCms\Modules\ModuleManager;
use TMCms\Orm\Entity;

if (!preg_match('/\.(?:jpg|png|jpeg|gif)&[a-z0-9&=\_]+$/', QUERY)) {
    return;
}

$sep_pos = strpos(QUERY, '&');

$path = explode('/', substr(QUERY, 0, $sep_pos));

foreach ($path as &$dir) {
    if (!FileSystem::checkFileName($dir)) {
        return;
    }
}

$file = array_pop($path);
$path = implode('/', $path);

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

$src_actions = substr(QUERY, $sep_pos + 1);
parse_str($src_actions, $actions);

$src_path = ($path ? $path . '/' : NULL) . $file;

if (!is_file(DIR_BASE . $src_path)) {
    return;
}

ini_set('memory_limit', '512M');

// Rotate before EXIF data
if ($ext == 'jpg' || $ext == 'jpeg') {
    $exif = @exif_read_data(DIR_BASE . $src_path);
    // file may be not a .jpg and this will raise an error
    $img = @imagecreatefromjpeg(DIR_BASE . $src_path);
    if ($img && $exif && isset($exif['Orientation'])) {
        $ort = $exif['Orientation'];

        if ($ort == 6 || $ort == 5)
            $img = imagerotate($img, 270, 0);
        if ($ort == 3 || $ort == 4)
            $img = imagerotate($img, 180, 0);
        if ($ort == 8 || $ort == 7)
            $img = imagerotate($img, 90, 0);

        if ($ort == 5 || $ort == 4 || $ort == 7)
            imageflip($img, IMG_FLIP_HORIZONTAL);

        imagejpeg($img, DIR_BASE . $src_path, 100);
    }
}

$max_w = 2400;
$max_h = 1600;

$image = new Image;

try {
    $image->open($src_path);
} catch (Exception $e) {
    if (!Settings::isProductionState()) {
        exit('Error. Not enough memory to open image "' . $path . $file . '". Exception: ' . $e->getMessage());
    }
    die;
}

// If key is provided - no limitations
$check_for_sizes = !isset($_GET['key']) || $_GET['key'] != Configuration::getInstance()->get('cms')['unique_key'];

$allowed_sizes = Settings::get('image_processor_allowed_sizes');
if (!$allowed_sizes && $check_for_sizes) {
    if (!Settings::isProductionState()) {
        exit('Error. No allowed image sizes set.');
    }
    die;
}
$allowed_sizes = array_flip(explode(',', (string)$allowed_sizes));

$check_size_allowed = function($size) use ($allowed_sizes, $check_for_sizes) {
    if (!$check_for_sizes) {
        return true;
    }

    $size = trim($size);
    if (!isset($allowed_sizes[$size])) {
        if (!Settings::isProductionState()) {
            exit('Error. Not allowed image size: ' . $size . '. Allowed are ' . implode(', ', array_keys($allowed_sizes)));
        }
        die;
    }

    return false;
};

foreach ($actions as $action => $params) {
    $sep_pos = strpos($action, '_');
    if ($sep_pos !== false) {
        $action = trim(substr($action, 0, $sep_pos));
    }

    switch ($action) {
        case 'height':
            if (!$params) break;
            if (!preg_match('/^[0-9]+$/', $params)) {
                if (!Settings::isProductionState()) {
                    exit('Error processing params for action "height". Example: 640');
                }
                die;
            }
            $check_size_allowed($params);
            list($width, $height) = getimagesize($src_path);
            $h = $params;

            $ratio = $height / $h;
            $w = $width / $ratio;

            $w = (int)$w;
            $h = (int)$h;
            $image->resize($w, $h);

            break;

        case 'width':
            if (!$params) break;
            if (!preg_match('/^[0-9]+$/', $params)) {
                if (!Settings::isProductionState()) {
                    exit('Error processing params for action "width". Example: 640');
                }
                die;
            }
            $check_size_allowed($params);
            list($width, $height) = getimagesize($src_path);
            $w = $params;

            $ratio = $width / $w;
            $h = $height / $ratio;

            $w = (int)$w;
            $h = (int)$h;
            $image->resize($w, $h);

            break;

        case 'resize':
            if (!$params) break;
            if (!preg_match('/^[0-9]+x[0-9]+$/', $params)) {
                if (!Settings::isProductionState()) {
                    exit('Error processing params for action "resize". Example: 640x480');
                }
                die;
            }
            $check_size_allowed($params);
            list($w, $h) = explode('x', $params);
            if ($w > $max_w) $w = $max_w;
            if ($h > $max_h) $h = $max_h;

            $w = (int)$w;
            $h = (int)$h;
            $image->resize($w, $h);

            break;

        case 'resizefit':
            if (!$params) break;
            if (!preg_match('/^[0-9]+x[0-9]+$/', $params)) {
                if (!Settings::isProductionState()) {
                    exit('Error processing params for action "resizefit". Example: 640x480');
                }
                die;
            }
            $check_size_allowed($params);
            list($w, $h) = explode('x', $params);
            if ($w > $max_w) $w = $max_w;
            if ($h > $max_h) $h = $max_h;

            $w = (int)$w;
            $h = (int)$h;
            $image->resizeFit($w, $h);

            break;

        case 'grayscale':
            if (!$params) break;
            $check_size_allowed($params);
            $image->grayscale();

            break;

        case 'sharpen':
            if (!$params) break;
            $check_size_allowed($params);
            $image->unsharpMask();

            break;

        case 'interlace':
            $check_size_allowed($params);
            $image->interlace((bool)$params);

            break;

        case 'fill':
            if (!$params) break;
            $check_size_allowed($params);
            if (!preg_match('/^[0-9]+x[0-9]+x[0-9a-f]{6}$/i', $params)) {
                if (!Settings::isProductionState()) {
                    exit('Error processing params for action "fill". Example: 32x32xff00cc');
                }
                die;
            }
            $check_size_allowed($params);
            list($x, $y, $color) = explode('x', $params);

            $image->apply();

            $ih = $image->getHandler();
            $color = imagecolorallocate($ih, hexdec($color[0] . $color[1]), hexdec($color[2] . $color[3]), hexdec($color[4] . $color[5]));
            imagefilledrectangle($ih, $x, $y, imagesx($ih), imagesy($ih), $color);
            unset($ih);

            break;

        case 'rectangle':
            if (!$params) break;
            $check_size_allowed($params);
            if (!preg_match('/^[0-9]+x[0-9]+x[0-9]+x[0-9]+x[0-9a-f]{6}$/i', $params)) {
                if (!Settings::isProductionState()) {
                    exit('Error processing params for action "rectangle". Example: 32x32x64x64xff00cc');
                }
                die;
            }
            list($x1, $y1, $x2, $y2, $color) = explode('x', $params);

            $image->apply();

            $ih = $image->getHandler();
            $color = imagecolorallocate($ih, hexdec($color[0] . $color[1]), hexdec($color[2] . $color[3]), hexdec($color[4] . $color[5]));
            imagefilledrectangle($ih, $x1, $y1, $x2, $y2, $color);
            unset($ih);

            break;

        case 'roundaa':
            if (!$params) break;
            $check_size_allowed($params);
            if (!preg_match('/^[0-9]+x[0-9a-f]{6}x[0-9]{1}$/i', $params)) {
                if (!Settings::isProductionState()) {
                    exit('Error processing params for action "roundaa". Example: 7xff00ccx4');
                }
                die;
            }
            list($radius, $color, $aa_level) = explode('x', $params);

            $radius = (int)$radius;
            $image->roundedCorners($radius, $color, $aa_level);

            break;

        case 'round':
            if (!$params) break;
            $check_size_allowed($params);
            if (!preg_match('/^[0-9]+x[0-9a-f]{6}$/i', $params)) {
                if (!Settings::isProductionState()) {
                    exit('Error processing params for action "round". Example: 7xff00cc');
                }
                die;
            }
            list($radius, $color) = explode('x', $params);

            $image->roundedCorners((int)$radius, $color, 0);

            break;

        case 'saveas':
            if (!$params) break;
            $check_size_allowed($params);
            if ($params !== 'jpg' && $params !== 'png' && $params !== 'gif' && $params !== 'jpeg') {
                if (!Settings::isProductionState()) {
                    exit('Error processing params for action "saveas". Possible values are: jpg, jpeg, png, gif');
                }
                die;
            }

            $save_ext = $params;

            break;

        case 'watermark':
            if (!$params) break;
            $check_size_allowed($params);
            if (!preg_match('/^[0-9]+$/', $params)) {
                if (!Settings::isProductionState()) {
                    exit('Error processing params for action "watermark". Example: 1 or main');
                }
                die;
            }

            // TODO predefine available list of watermark images and positions in admin panel and supply only id
//            $data = q_assoc_row('SELECT `image`, `image_position` FROM `cms_images_rules` WHERE `rule` = "watermark" LIMIT 1');
//            if (!$data || !$data['image'] || !$data['image_position']) {
//                if (!Settings::isProductionState()) {
//                    dump('Incorrect parameters for action "watermark"');
//                }
//                die;
//            }

            $image->watermark($data['image'], $data['image_pos']);

            break;
    }
}

// Create directory for cached file
FileSystem::mkDir(DIR_IMAGE_CACHE . $path);

// Save end file for web
$destination_path = DIR_IMAGE_CACHE . QUERY;
if (!$image->save($destination_path, $ext, 100) && !Settings::isProductionState()) {
    dump('Not enough memory to resize and sharpen image "' . $path . $file . '".');
}

MiddlewareHandler::getInstance()->runHandlersFromType('after_image_processor', [
    'src_original' => $src_path,
    'src_saved'    => $destination_path,
]);

// Run file optimizers
// TODO move these optimizers to middleware, and possibly enable in admin panel or check during run
$tinypng = Configuration::getInstance()->get('tinypng');
if (class_exists('\Tinify\Tinify') && !empty($tinypng) && !empty($tinypng['key'])) {
    try {
        \Tinify\setKey($tinypng['key']);
        $source = \Tinify\fromFile($destination_path);
        $source->toFile($destination_path);
    } catch (Exception $e) {
        // If exception occurs saves to table for future processing
        ModuleManager::requireModule('tinify');
        if (class_exists('\TMCms\Modules\Tinify\Entity\TinifyEntity')) {
            /** @var Entity $tini */
            $tini = \TMCms\Modules\Tinify\Entity\TinifyEntityRepository::getInstance()->setWherePath(QUERY)->getFirstObjectFromCollection();
            if (!empty($tini)) {
                $tini->loadDataFromArray(['exception' => get_class($e), 'attempt_date' => date("Y-m-d H:i:s")])->save();
            } else {
                $tini = new \TMCms\Modules\Tinify\Entity\TinifyEntity();
                $tini->loadDataFromArray(['path' => QUERY, 'exception' => get_class($e)])->save();
            }
        }
    }

} else {
    $path_for_exec = str_replace(['&', '=', ' ', '(', ')'], ['\&', '\=', '\ ', '\(', '\)'], QUERY);
    if ($ext == 'jpg') {
        $sizes = getimagesize(DIR_IMAGE_CACHE . QUERY);
        $size_proposal = $sizes[0] * $sizes[1] / 6000; // In kb by three
        exec('jpegoptim --size=' . $size_proposal . 'kb --strip-all ' . DIR_IMAGE_CACHE . $path_for_exec . '  2>&1');
    } elseif ($ext == 'png') {
        exec('optipng ' . DIR_IMAGE_CACHE . $path_for_exec . '  2>&1');
    }
}

go('/' . QUERY);
