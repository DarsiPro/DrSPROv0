<?php
/**
* @project    DarsiPro CMS
* @package    DrsImg Class
* @url        https://darsi.pro
*/


class DrsImg {

    public function createWaterMark($orig, $watermark) {
        if (!file_exists($orig) || !file_exists($watermark)) return false;

        $hpos = Config::read('watermark_hpos');
        $hpos = ($hpos > 0 && $hpos < 4) ? $hpos : 3;
        $vpos = Config::read('watermark_vpos');
        $vpos = ($vpos > 0 && $vpos < 4) ? $vpos : 3;
        $alpha_level = Config::read('watermark_alpha');
        $alpha_level = ($alpha_level >= 0 && $alpha_level <= 100) ? $alpha_level : 50;

        if (function_exists('exif_imagetype')) {
            $orig_type = exif_imagetype($orig);
            $water_type = exif_imagetype($watermark);
        } else if (function_exists('getimagesize')) {
            $orig_type = getimagesize($orig);
            $water_type = getimagesize($watermark);
            $orig_type = $orig_type['mime'];
            $water_type = $water_type['mime'];
        } else {
            return false;
        }

        if (empty($orig_type) || empty($water_type)) return false;
        if ($orig_type === 1 || $orig_type === 'image/gif') $main_img = imagecreatefromgif($orig);
        else if ($orig_type === 2 || $orig_type === 'image/jpeg') $main_img = imagecreatefromjpeg($orig);
        else if ($orig_type === 3 || $orig_type === 'image/png') $main_img = imagecreatefrompng($orig);

        if ($water_type === 1 || $water_type === 'image/gif') $watermark_img = imagecreatefromgif($watermark);
        else if ($water_type === 2 || $water_type === 'image/jpeg') $watermark_img = imagecreatefromjpeg($watermark);
        else if ($water_type === 3 || $water_type === 'image/png') $watermark_img = imagecreatefrompng($watermark);
        if (empty($main_img) || empty($watermark_img)) return false;

        $alpha_level /= 100;

        // get sizes
        $main_w = imagesx($main_img);
        $main_h = imagesy($main_img);
        $watermark_w = imagesx($watermark_img);
        $watermark_h = imagesy($watermark_img);

        // не создавать ватемарки для маленьких изображений
        if ((Config::read('watermark_min_img') > $main_w) or (Config::read('watermark_min_img') > $main_h)) return;

        $watermark_indent = (Config::read('watermark_indent') >= 0) ? Config::read('watermark_indent') : 10;

        switch ($hpos) {
            case 1:
                $min_x = $watermark_indent;
                $max_x = $min_x + $watermark_w;
                break;
            case 2:
                $min_x = ceil(($main_w - $watermark_w) / 2);
                $max_x = $min_x + $watermark_w;
                break;
            default:
                $max_x = $main_w - $watermark_indent;
                $min_x = $max_x - $watermark_w;
                break;
        }
        switch ($vpos) {
            case 1:
                $min_y = $watermark_indent;
                $max_y = $min_y + $watermark_h;
                break;
            case 2:
                $min_y = ceil(($main_h - $watermark_h) / 2);
                $max_y = $min_y + $watermark_h;
                break;
            default:
                $max_y = $main_h - $watermark_indent;
                $min_y = $max_y - $watermark_h;
                break;
        }

        // create image
        $return_img = imagecreatetruecolor($main_w, $main_h);
        switch ($orig_type) {
                        case 1:
            case 'image/gif':
            case 3:
            case 'image/png':
                imagecolortransparent($return_img, imagecolortransparent($main_img));
                imagealphablending($return_img, false);
                imagesavealpha($return_img, true);
                break;
            default: break;
        }
        imagecopy($return_img, $main_img, 0, 0, 0, 0, $main_w, $main_h);

        $start_x = $min_x < 0 ? 0 : $min_x;
        $start_y = $min_y < 0 ? 0 : $min_y;
        $end_x = $max_x >= $main_w ? $main_w - 1 : $max_x;
        $end_y = $max_y >= $main_h ? $main_h - 1 : $max_y;

        $watermark_tr = imagecolortransparent($watermark_img);

        // Each pixel watermarks image
        for($y = $start_y; $y < $end_y; $y++) {
            for ($x = $start_x; $x < $end_x; $x++) {

                // pixel position
                $watermark_x = $x - $min_x;
                $watermark_y = $y - $min_y;

                // Get color info
                $main_rgb = imagecolorsforindex($main_img, imagecolorat($main_img, $x, $y));

                $watermark_px = imagecolorat($watermark_img, $watermark_x, $watermark_y);
                if ($watermark_px == $watermark_tr) continue;

                $watermark_rbg = imagecolorsforindex($watermark_img, $watermark_px);

                // Alpha chanel
                $watermark_alpha = round(((127-$watermark_rbg['alpha'])/127),2);
                $watermark_alpha = $watermark_alpha * $alpha_level;
                $main_alpha = round(((127-$main_rgb['alpha'])/127),2);

                // Get color in overlay place
                $avg_red = $this->__get_ave_color( $main_rgb['red'],
                           $watermark_rbg['red'], $watermark_alpha );
                $avg_green = $this->__get_ave_color( $main_rgb['green'],
                             $watermark_rbg['green'], $watermark_alpha );
                $avg_blue = $this->__get_ave_color( $main_rgb['blue'],
                            $watermark_rbg['blue'], $watermark_alpha );
                $avg_a = $this->__get_ave_color( $main_rgb['alpha'],
                            $watermark_rbg['alpha'], $watermark_alpha );

                // Index of color
                $return_color = $this->__get_image_color($return_img, $avg_red, $avg_green, $avg_blue, $avg_a);

                // Create new image with new pixels
                imagesetpixel($return_img, $x, $y, $return_color);
            }
        }

        // View image
        $quality_jpeg = Config::read('quality_jpeg');
        if (isset($quality_jpeg)) $quality_jpeg = (intval($quality_jpeg) < 0 || intval($quality_jpeg) > 100) ? 75 : intval($quality_jpeg);
        $quality_png = Config::read('quality_png');
        if (isset($quality_png)) $quality_png = (intval($quality_png) < 0 || intval($quality_png) > 9) ? 9 : intval($quality_png);

        switch ($orig_type) {
            case 1:
            case 'image/gif':
                imagegif($return_img, $orig);
                break;
            case 2:
            case 'image/jpeg':
                imagejpeg($return_img, $orig, $quality_jpeg);
                break;
            case 3:
            case 'image/png':
                imagepng($return_img, $orig, $quality_png);
                break;
            default:
                imagejpeg($return_img, $orig, $quality_jpeg);
                break;
        }


        imagedestroy($return_img);
        imagedestroy($main_img);
        imagedestroy($watermark_img);
    }

    // Сохраняет картинку, которую следует накладывать на изображения
    public static function saveWaterMarkImage() {
        clearstatcache();
        if (isImageFile($_FILES['watermark_img'])) {
            $ext = strchr($_FILES['watermark_img']['name'], '.');
            if (move_uploaded_file($_FILES['watermark_img']['tmp_name'], ROOT . '/data/img/watermark'.$ext)) {
                return 'watermark'.$ext;
            }
        }

        return null;
    }
    // Показывает сохраненную картинку для накладывания на изображения
    public static function showWaterMarkImage($settings) {
        clearstatcache();
        $params = array(
            'style' => 'max-width:200px; max-height:200px;',
        );
        if (!empty($settings['watermark_img']) && file_exists(ROOT . '/data/img/' . $settings['watermark_img'])) {
            return get_img('/data/img/' . $settings['watermark_img'], $params);
        }
        return '';
    }
    public static function showWaterMarkText() {
        clearstatcache();
        $params = array(
            'style' => 'max-width:200px; max-height:200px;',
        );
        $file = '/data/img/watermark_text.png';
        if (file_exists(ROOT . $file)) {
            return get_img('/data/img/watermark_text.png', $params);
        }
        return '';
    }

    // Сохранает изображение для ватермарка, делая его из текста
    public static function saveWaterMarkText($settings,$fname) {
        clearstatcache();
        $font = ROOT . '/data/fonts/' . $settings['watermark_text_font'];
        $size = isset($settings['watermark_text_size']) && is_numeric($settings['watermark_text_size']) ? intval($settings['watermark_text_size']) : 14;
        $angle = intval($settings['watermark_text_angle']);
        $text = $settings['watermark_text'];

        $delta = round($size / 50 + 1);

        // Вариант 1
        $text_size = imagettfbbox($size, $angle, $font, $text);

        // Вариант 2
        /*
        $text_size = imagettfbbox($size, 0, $font, $text);
        $rangle = deg2rad($angle);
        for ($i = 0; $i < 8; $i = $i + 2) {
            $x = $text_size[$i];
            $y = $text_size[$i + 1];
            $text_size[$i] = round($x * cos($rangle) + $y * sin($rangle));
            $text_size[$i + 1] = round(- $x * sin($rangle) + $y * cos($rangle));
        }
        */

        $x_ar = array($text_size[0], $text_size[2], $text_size[4], $text_size[6]);
        $y_ar = array($text_size[1], $text_size[3], $text_size[5], $text_size[7]);

        unset($text_size);

        $img_w = round((max($x_ar) - min($x_ar)) * 1.025) + 10 * $delta;
        $img_h = round((max($y_ar) - min($y_ar)) * 1.025) + 10 * $delta;

        $x_center = array_sum($x_ar) / 4;
        $y_center = array_sum($y_ar) / 4;

        for ($i = 0; $i < 4; $i++) {
            $x_ar[$i] = round($x_ar[$i] + $img_w / 2 - $x_center);
            $y_ar[$i] = round($y_ar[$i] + $img_h / 2 - $y_center);
        }

        unset($x_center);
        unset($y_center);

        $pos_x = $x_ar[0];
        $pos_y = $y_ar[0];

        unset($x_ar);
        unset($y_ar);

        $img = imagecreatetruecolor($img_w, $img_h);

        $bg_color = imagecolorallocate($img, 254, 254, 254);

        imagecolortransparent($img, $bg_color);
        imagefilledrectangle($img, 0, 0, $img_w - 1, $img_h - 1, $bg_color);

        $color = isset($settings['watermark_text_color']) ? hexdec($settings['watermark_text_color']) : 0xFFFFFF;
        $text_color = imagecolorallocate($img, ($color >> 16) & 0xFF, ($color >> 8) & 0xFF, $color & 0xFF);

        if ($settings['watermark_text_border'] != 'none') {
            $color = isset($settings['watermark_text_border']) ? hexdec($settings['watermark_text_border']) : 0x000000;
            $border_color = imagecolorallocate($img, ($color >> 16) & 0xFF, ($color >> 8) & 0xFF, $color & 0xFF);

            imagettftext($img, $size, $angle, $pos_x+$delta, $pos_y, $border_color, $font, $text);
            imagettftext($img, $size, $angle, $pos_x-$delta, $pos_y, $border_color, $font, $text);
            imagettftext($img, $size, $angle, $pos_x, $pos_y+$delta, $border_color, $font, $text);
            imagettftext($img, $size, $angle, $pos_x, $pos_y-$delta, $border_color, $font, $text);
        }

        imagettftext($img, $size, $angle, $pos_x, $pos_y, $text_color, $font, $text);

        imagepng($img, ROOT . '/data/img/watermark_text.png', 9);
        imagedestroy($img);

        return $settings[$fname];
    }


    /**
     * merge 2 colors with alpha chanel
     */
    private function __get_ave_color($color_a, $color_b, $watermark_alpha) {
        return round($color_a*(1-$watermark_alpha)+($color_b*$watermark_alpha));
    }


    /**
     * return RGB color
     */
    private function __get_image_color($im, $r, $g, $b, $a) {
        $c = imagecolorallocatealpha($im, $r, $g, $b, $a);
        if ($c != -1) return $c;
        $c = imagecolorexact($im, $r, $g, $b);
        if ($c != -1) return $c;
        return imagecolorclosest($im, $r, $g, $b);
    }


    public function resampleImage($path, $new_path, $sizew, $sizeh = false) {
        if (false == $sizeh) $sizeh = $sizew;

        if (!isset($sizew) || $sizew < 150) $sizew = 150;
        if (!isset($sizeh) || $sizeh < 150) $sizeh = 150;

        $itype = 2;
        if (function_exists('exif_imagetype')) {
            $itype = exif_imagetype($path);
            switch ($itype) {
                case IMAGETYPE_JPEG: $img = imagecreatefromjpeg($path); break;
                case IMAGETYPE_GIF: $img = imagecreatefromgif($path); break;
                case IMAGETYPE_PNG: $img = imagecreatefrompng($path); break;
                case IMAGETYPE_BMP: $img = imagecreatefromwbmp($path); break;
                default: return false;
            }
            if(!$img) return false;
        } else if (function_exists('getimagesize')) {
            @$info = getimagesize($path);
            if (!$info || empty($info['mime'])) return false;
            $itype = $info['mime'];
            switch ($itype) {
                case 'image/jpeg': $img = imagecreatefromjpeg($path); break;
                case 'image/gif': $img = imagecreatefromgif($path); break;
                case 'image/png': $img = imagecreatefrompng($path); break;
                case 'image/bmp': $img = imagecreatefromwbmp($path); break;
                default: return false;
            }
        } else {
            $img = imagecreatefromjpeg($path);
        }
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w < $sizew && $h < $sizeh) {
            $nw = $w;
            $nh = $h;
        } else {
            if (($w / $sizew) < ($h / $sizeh)) {
                $nw = intval($w * $sizeh / $h);
                $nh = $sizeh;
            } else {
                $nw = $sizew;
                $nh = intval($h * $sizew / $w);
            }
        }

        $dest = imagecreatetruecolor($nw, $nh);
        switch ($itype) {
            case 1:
            case 'image/gif':
            case 3:
            case 'image/png':
                imagecolortransparent($dest, imagecolortransparent($img));
                imagealphablending($dest, false);
                imagesavealpha($dest, true);
                break;
            default: break;
        }
        imagecopyresampled($dest, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

        $quality_jpeg = Config::read('quality_jpeg');
        if (isset($quality_jpeg)) $quality_jpeg = (intval($quality_jpeg) < 0 || intval($quality_jpeg) > 100) ? 75 : intval($quality_jpeg);
        $quality_png = Config::read('quality_png');
        if (isset($quality_png)) $quality_png = (intval($quality_png) < 0 || intval($quality_png) > 9) ? 9 : intval($quality_png);

        switch ($itype) {
            case 1:
            case 'image/gif':
                imagegif($dest, $new_path);
                break;
            case 2:
            case 'image/jpeg':
                imagejpeg($dest, $new_path, $quality_jpeg);
                break;
            case 3:
            case 'image/png':
                imagepng($dest, $new_path, $quality_png);
                break;
            case 4:
            case 'image/bmp':
                imagewbmp($dest, $new_path);
                break;
            default:
                imagejpeg($dest, $new_path, $quality_jpeg);
                break;
        }
        imagedestroy($img);
        imagedestroy($dest);
        return true;
    }

    public function returnImage($module, $name, $size=false) {

        $ext = strtolower(strrchr($name, '.'));
        $mime = 'image/'.substr($ext, 1);
        $dest_path = R.'data/images/'.$module.'/'.$name;

        if (!file_exists($dest_path) or !isImageFile($dest_path)) {
            http_response_code(404);
            include_once R.'sys/inc/error.php';
            die();
        }

        if ($size !== false) {
            $size = explode('x', $size);
            if (count($size)!=2) {
                http_response_code(404);
                include_once R.'sys/inc/error.php';
                die();
            }
            $size_x = $size[0];
            $size_y = $size[1];

            if (!$this->checkSize($size_x, $size_y, $module)) {
                http_response_code(404);
                include_once R.'sys/inc/error.php';
                die();
            }

            // Путь до папки с изображением
            $imgdir = R.'data/images/' . $module . '/'.$size_x.'x'.$size_y. '/';
            if (!file_exists($imgdir)) mkdir($imgdir, 0777, true);

            if (!file_exists($imgdir . $name)) {
                $this->resampleImage($dest_path, $imgdir . $name, $size_x, $size_y);
            }

            if (!file_exists($imgdir . $name)) {
                $imgdir = R.'data/images/' . $module . '/';
            }

        } else {
            $imgdir = R.'data/images/' . $module . '/';
        }

        // если файл не изменился то пусть берётся из кэша браузера
        $etag = md5_file($imgdir . $name);
        if((isset($_SERVER['HTTP_IF_NONE_MATCH'])) && (stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) == $etag)) {
            header("HTTP/1.1 304 Not Modified", TRUE, 304);
            exit();
        }
        header("Etag: $etag");
        header('Cache-Control: must-revalidate');
        header('Content-type: '.$mime);

        echo file_get_contents($imgdir . $name);
    }

    function checkSize($size_x, $size_y, $module) {
        if (Config::read('use_local_preview', $module)) {
            if ($size_x == Config::read('img_size_x', $module) and $size_y == Config::read('img_size_y', $module)) {
                return true;
            }
        } else {
            if ($size_x == Config::read('img_size_x') and $size_y == Config::read('img_size_y')) {
                return true;
            }
        }
        return false;
    }
}


?>