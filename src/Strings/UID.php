<?php
declare(strict_types=1);

namespace TMCms\Strings;

defined('INC') or exit;

/**
 * Class UID
 */
class UID
{
    private static $alphanum_chars = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
    private static $hex_chars = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'a', 'b', 'c', 'd', 'e', 'f'];

    /**
     * Generate random string
     *
     * @return string md5 hash
     */
    public static function uid32(): string
    {
        return md5('&QSsaxwp9fd f_ ' . uniqid((string)mt_rand(), true));
    }

    /**
     * Generate simple random string, useful for hex names
     *
     * @return string
     */
    public static function uid10(): string
    {
        $converted = base_convert(self::$hex_chars[random_int(0, 15)] . uniqid('', true) . self::$hex_chars[random_int(0, 15)], 16, 10);
        $res = '';

        for ($i = floor(log10($converted) / 1.7923916894983); $i >= 0; --$i) {
            // Random index
            $j = 62 ** $i;
            $a = (int)floor($converted / $j);
            $res .= self::$alphanum_chars[$a];

            $converted -= $a * $j;
        }

        return sprintf('%010s', $res);
    }

    /**
     * Replace non-latin chars to latin pairs
     *
     * @param string $original_text
     * @param int    $max_length maximum length of converted string
     *
     * @return string
     *
     */
    public static function text2uid($original_text, $max_length = 30): string
    {
        $original_text = mb_strtolower($original_text, 'utf-8');

        // Changes from east language
        $original_text = str_replace(
            [' - ', ' ', '&', ':', 'æ', 'ǽ', 'é', 'è', 'ê', 'ē', 'ë', 'ĕ', 'ė', 'ę', 'ě', 'е', 'э', 'ё', 'ø', 'œ', 'ö', 'ó', 'ò', 'ô', 'о', 'ä', 'â', 'å', 'à', 'á', 'ā', 'ã', 'ǻ', 'ă', 'ą', 'ǎ', 'ª', 'а', 'ă', 'ü', 'ū', 'у', 'і', 'ī', 'ï', 'î', 'ì', 'í', 'ï', 'ĭ', 'ǐ', 'ı', 'į', 'ы', 'и', 'š', 'ш', 'щ', 'ģ', 'ĝ', 'ğ', 'ġ', 'ґ', 'г', 'ķ', 'к', 'ļ', 'ĺ', 'ľ', 'ŀ', 'ł', 'л', 'ž', 'ж', 'č', 'ч', 'ņ', 'ñ', 'ń', 'ņ', 'ň', 'ŉ', 'н', 'й', 'ц', 'з', 'ĥ', 'ħ', 'х', 'ф', 'в', 'п', 'р', 'д', 'я', 'с', 'ș', 'м', 'т', 'ț', 'б', 'ю', '.', ',', 'ъ', 'ь', 'ç', 'ć', 'ć', 'ć', 'ð', 'ð', 'đ', 'ĵ'],
            ['-', '-', '-and-', '-', 'ae', 'ae', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'oe', 'o', 'o', 'o', 'o', 'o', 'o', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'u', 'u', 'u', 'i', 'i', 'i', 'i', 'i', 'i', 'i', 'i', 'i', 'i', 'i', 'i', 'i', 'sh', 'sh', 'sh', 'g', 'g', 'g', 'g', 'g', 'g', 'k', 'k', 'l', 'l', 'l', 'l', 'l', 'l', 'zh', 'zh', 'ch', 'ch', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'y', 'c', 'z', 'h', 'h', 'h', 'f', 'v', 'p', 'r', 'd', 'ya', 's', 's', 'm', 't', 't', 'b', 'yu', '-', '-', '', '', 'c', 'c', 'c', 'c', 'd', 'd', 'd', 'j'],
            $original_text
        );

        // Changes from west languages
        $transliteration = [
            'ä' => 'ae',
            'æ' => 'ae',
            'ǽ' => 'ae',
            'ö' => 'oe',
            'œ' => 'oe',
            'ü' => 'ue',
            'Ä' => 'Ae',
            'Ü' => 'Ue',
            'Ö' => 'Oe',
            'À' => 'A',
            'Á' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Å' => 'A',
            'Ǻ' => 'A',
            'Ā' => 'A',
            'Ă' => 'A',
            'Ą' => 'A',
            'Ǎ' => 'A',
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'å' => 'a',
            'ǻ' => 'a',
            'ā' => 'a',
            'ă' => 'a',
            'ą' => 'a',
            'ǎ' => 'a',
            'ª' => 'a',
            'Ç' => 'C',
            'Ć' => 'C',
            'Ĉ' => 'C',
            'Ċ' => 'C',
            'Č' => 'C',
            'ç' => 'c',
            'ć' => 'c',
            'ĉ' => 'c',
            'ċ' => 'c',
            'č' => 'c',
            'Ð' => 'D',
            'Ď' => 'D',
            'Đ' => 'D',
            'ð' => 'd',
            'ď' => 'd',
            'đ' => 'd',
            'È' => 'E',
            'É' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Ē' => 'E',
            'Ĕ' => 'E',
            'Ė' => 'E',
            'Ę' => 'E',
            'Ě' => 'E',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ē' => 'e',
            'ĕ' => 'e',
            'ė' => 'e',
            'ę' => 'e',
            'ě' => 'e',
            'Ĝ' => 'G',
            'Ğ' => 'G',
            'Ġ' => 'G',
            'Ģ' => 'G',
            'Ґ' => 'G',
            'ĝ' => 'g',
            'ğ' => 'g',
            'ġ' => 'g',
            'ģ' => 'g',
            'ґ' => 'g',
            'Ĥ' => 'H',
            'Ħ' => 'H',
            'ĥ' => 'h',
            'ħ' => 'h',
            'І' => 'I',
            'Ì' => 'I',
            'Í' => 'I',
            'Î' => 'I',
            'Ї' => 'Yi',
            'Ï' => 'I',
            'Ĩ' => 'I',
            'Ī' => 'I',
            'Ĭ' => 'I',
            'Ǐ' => 'I',
            'Į' => 'I',
            'İ' => 'I',
            'і' => 'i',
            'ì' => 'i',
            'í' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ї' => 'yi',
            'ĩ' => 'i',
            'ī' => 'i',
            'ĭ' => 'i',
            'ǐ' => 'i',
            'į' => 'i',
            'ı' => 'i',
            'Ĵ' => 'J',
            'ĵ' => 'j',
            'Ķ' => 'K',
            'ķ' => 'k',
            'Ĺ' => 'L',
            'Ļ' => 'L',
            'Ľ' => 'L',
            'Ŀ' => 'L',
            'Ł' => 'L',
            'ĺ' => 'l',
            'ļ' => 'l',
            'ľ' => 'l',
            'ŀ' => 'l',
            'ł' => 'l',
            'Ñ' => 'N',
            'Ń' => 'N',
            'Ņ' => 'N',
            'Ň' => 'N',
            'ñ' => 'n',
            'ń' => 'n',
            'ņ' => 'n',
            'ň' => 'n',
            'ŉ' => 'n',
            'Ò' => 'O',
            'Ó' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ō' => 'O',
            'Ŏ' => 'O',
            'Ǒ' => 'O',
            'Ő' => 'O',
            'Ơ' => 'O',
            'Ø' => 'O',
            'Ǿ' => 'O',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ō' => 'o',
            'ŏ' => 'o',
            'ǒ' => 'o',
            'ő' => 'o',
            'ơ' => 'o',
            'ø' => 'o',
            'ǿ' => 'o',
            'º' => 'o',
            'Ŕ' => 'R',
            'Ŗ' => 'R',
            'Ř' => 'R',
            'ŕ' => 'r',
            'ŗ' => 'r',
            'ř' => 'r',
            'Ś' => 'S',
            'Ŝ' => 'S',
            'Ş' => 'S',
            'Ș' => 'S',
            'Š' => 'S',
            'ẞ' => 'SS',
            'ś' => 's',
            'ŝ' => 's',
            'ş' => 's',
            'ș' => 's',
            'š' => 's',
            'ſ' => 's',
            'Ţ' => 'T',
            'Ț' => 'T',
            'Ť' => 'T',
            'Ŧ' => 'T',
            'ţ' => 't',
            'ț' => 't',
            'ť' => 't',
            'ŧ' => 't',
            'Ù' => 'U',
            'Ú' => 'U',
            'Û' => 'U',
            'Ũ' => 'U',
            'Ū' => 'U',
            'Ŭ' => 'U',
            'Ů' => 'U',
            'Ű' => 'U',
            'Ų' => 'U',
            'Ư' => 'U',
            'Ǔ' => 'U',
            'Ǖ' => 'U',
            'Ǘ' => 'U',
            'Ǚ' => 'U',
            'Ǜ' => 'U',
            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'ũ' => 'u',
            'ū' => 'u',
            'ŭ' => 'u',
            'ů' => 'u',
            'ű' => 'u',
            'ų' => 'u',
            'ư' => 'u',
            'ǔ' => 'u',
            'ǖ' => 'u',
            'ǘ' => 'u',
            'ǚ' => 'u',
            'ǜ' => 'u',
            'Ý' => 'Y',
            'Ÿ' => 'Y',
            'Ŷ' => 'Y',
            'ý' => 'y',
            'ÿ' => 'y',
            'ŷ' => 'y',
            'Ŵ' => 'W',
            'ŵ' => 'w',
            'Ź' => 'Z',
            'Ż' => 'Z',
            'Ž' => 'Z',
            'ź' => 'z',
            'ż' => 'z',
            'ž' => 'z',
            'Æ' => 'AE',
            'Ǽ' => 'AE',
            'ß' => 'ss',
            'Ĳ' => 'IJ',
            'ĳ' => 'ij',
            'Œ' => 'OE',
            'ƒ' => 'f',
            'Þ' => 'TH',
            'þ' => 'th',
            'Є' => 'Ye',
            'є' => 'ye',
        ];

        $original_text = str_replace(array_keys($transliteration), array_values($transliteration), $original_text);

        $original_text = preg_replace('/[^a-z0-9\-\_]+/i', '', $original_text);

        while (strpos($original_text, '--') !== false) {
            $original_text = str_replace('--', '-', $original_text);
        }

        // Removed odd char from name ends
        $original_text = trim($original_text, '-');

        return substr($original_text, 0, $max_length);
    }

    /**
     * Replaces non-latin chars to latin pairs, with JS event handlers
     *
     * @param bool  $wrap_js_tags if need to use Javascript wrap
     * @param array $link_ids     list of elements to link with in HTML
     * @param int   $max_length
     * @param bool  $run_on_page_load
     * @param bool  $run_on_input_change
     */
    public static function textToUidJs($wrap_js_tags = true, array $link_ids = [], $max_length = 99, $run_on_page_load = true, $run_on_input_change = true)
    {
        // Js inner part
        echo self::uidFromTextJsFunction($wrap_js_tags);

        if ($link_ids) {
            if ($wrap_js_tags) {
                echo '<script>';
            }

            $auto_run = $run_on_page_load || $run_on_input_change;

            foreach ($link_ids as $id_src => $id_dst) {
                echo '
                function uidFromTextEvent_', $id_src, '_', $id_dst, '() {
                    document.getElementById("', $id_dst, '").value = uidFromText(document.getElementById("', $id_src, '").value, ', $max_length, ');
                }',
                ($auto_run ? '$(function() {' : ''),
                ($run_on_page_load ? 'uidFromTextEvent_' . $id_src . '_' . $id_dst . '();' : ''),
                ($run_on_input_change ? 'document.getElementById("' . $id_src . '").onchange = uidFromTextEvent_' . $id_src . '_' . $id_dst . '; document.getElementById("' . $id_src . '").onkeyup = uidFromTextEvent_' . $id_src . '_' . $id_dst . ';' : ''),
                ($auto_run ? '});' : '');
            }

            if ($wrap_js_tags) {
                echo '</script>';
            }
        }
    }

    /**
     * Echoes js part for uid to text2uid
     *
     * @param bool $wrap
     */
    private static function uidFromTextJsFunction($wrap = false)
    {
        if ($wrap) {
            echo '<script>';
        }
        // Part for Javascript
        ?>
        function uidFromText(txt, max_uid_l) {
        txt = txt.toLowerCase().replace(/ - /gm, '-').replace(/ /gm, '-').replace(/\&/g,'-and-').replace(/\:/g,'-').replace(/æ/gm, 'ae').replace(/é|è|ê|ē|е|э|ё/gm, 'e').replace(/ø|ó|ò|ô|о/gm, 'o').replace(/â|å|ā|ă|а/gm, 'a').replace(/ū|у/gm, 'u').replace(/ī|î|ï|ы|и/gm, 'i').replace(/š|ш|щ/gm, 'sh').replace(/ģ|г/gm, 'g').replace(/ķ|к/gm, 'k').replace(/ļ|л/gm, 'l').replace(/ž|ж/gm, 'zh').replace(/č|ч/gm, 'ch').replace(/ņ|н/gm, 'n').replace(/й/gm, 'y').replace(/ц/gm, 'c').replace(/з/gm, 'z').replace(/х/gm, 'h').replace(/ф/gm, 'f').replace(/в/gm, 'v').replace(/п/gm, 'p').replace(/р/gm, 'r').replace(/д/gm, 'd').replace(/я/gm, 'ya').replace(/ș|с/gm, 's').replace(/м/gm, 'm').replace(/ț|т/gm, 't').replace(/б/gm, 'b').replace(/ю/gm, 'yu').replace(/\.|\,/gm, '-').replace(/ъ|ь/gm, '');
        txt = txt.replace(/[^a-z0-9\-\_]+/gmi, '');
        while (txt.indexOf('--') !== -1) {
        txt = txt.replace('--', '-');
        }
        if (txt.charAt(0) === '-') {
        txt = txt.substr(1);
        }
        if (txt.charAt(txt.length - 1) === '-') {
        txt = txt.substr(0, txt.length - 1);
        }

        return txt.substr(0, max_uid_l);
        }
        <?php
        if ($wrap) {
            echo '</script>';
        }
    }
}