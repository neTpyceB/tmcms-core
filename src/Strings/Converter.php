<?php
declare(strict_types=1);

namespace TMCms\Strings;

defined('INC') or exit;

/**
 * Class Converter
 */
class Converter
{
    /**
     * Plural inflector rules
     *
     * @var array
     */
    private static $plural = [
        '/(s)tatus$/i'                                                       => '\1tatuses',
        '/(quiz)$/i'                                                         => '\1zes',
        '/^(ox)$/i'                                                          => '\1\2en',
        '/([m|l])ouse$/i'                                                    => '\1ice',
        '/(matr|vert|ind)(ix|ex)$/i'                                         => '\1ices',
        '/(x|ch|ss|sh)$/i'                                                   => '\1es',
        '/([^aeiouy]|qu)y$/i'                                                => '\1ies',
        '/(hive)$/i'                                                         => '\1s',
        '/(chef)$/i'                                                         => '\1s',
        '/(?:([^f])fe|([lre])f)$/i'                                          => '\1\2ves',
        '/sis$/i'                                                            => 'ses',
        '/([ti])um$/i'                                                       => '\1a',
        '/(p)erson$/i'                                                       => '\1eople',
        '/(?<!u)(m)an$/i'                                                    => '\1en',
        '/(c)hild$/i'                                                        => '\1hildren',
        '/(buffal|tomat)o$/i'                                                => '\1\2oes',
        '/(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin)us$/i' => '\1i',
        '/us$/i'                                                             => 'uses',
        '/(alias)$/i'                                                        => '\1es',
        '/(ax|cris|test)is$/i'                                               => '\1es',
        '/s$/'                                                               => 's',
        '/^$/'                                                               => '',
        '/$/'                                                                => 's',
    ];

    /**
     * Singular inflector rules
     *
     * @var array
     */
    private static $singular = [
        '/(s)tatuses$/i'                                                          => '\1\2tatus',
        '/^(.*)(menu)s$/i'                                                        => '\1\2',
        '/(quiz)zes$/i'                                                           => '\\1',
        '/(matr)ices$/i'                                                          => '\1ix',
        '/(vert|ind)ices$/i'                                                      => '\1ex',
        '/^(ox)en/i'                                                              => '\1',
        '/(alias)(es)*$/i'                                                        => '\1',
        '/(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin|viri?)i$/i' => '\1us',
        '/([ftw]ax)es/i'                                                          => '\1',
        '/(cris|ax|test)es$/i'                                                    => '\1is',
        '/(shoe)s$/i'                                                             => '\1',
        '/(o)es$/i'                                                               => '\1',
        '/ouses$/'                                                                => 'ouse',
        '/([^a])uses$/'                                                           => '\1us',
        '/([m|l])ice$/i'                                                          => '\1ouse',
        '/(x|ch|ss|sh)es$/i'                                                      => '\1',
        '/(m)ovies$/i'                                                            => '\1\2ovie',
        '/(s)eries$/i'                                                            => '\1\2eries',
        '/([^aeiouy]|qu)ies$/i'                                                   => '\1y',
        '/(tive)s$/i'                                                             => '\1',
        '/(hive)s$/i'                                                             => '\1',
        '/(drive)s$/i'                                                            => '\1',
        '/([le])ves$/i'                                                           => '\1f',
        '/([^rfoa])ves$/i'                                                        => '\1fe',
        '/(^analy)ses$/i'                                                         => '\1sis',
        '/(analy|diagno|^ba|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i'             => '\1\2sis',
        '/([ti])a$/i'                                                             => '\1um',
        '/(p)eople$/i'                                                            => '\1\2erson',
        '/(m)en$/i'                                                               => '\1an',
        '/(c)hildren$/i'                                                          => '\1\2hild',
        '/(n)ews$/i'                                                              => '\1\2ews',
        '/eaus$/'                                                                 => 'eau',
        '/^(.*us)$/'                                                              => '\\1',
        '/s$/i'                                                                   => '',
    ];

    /**
     * Irregular rules
     *
     * @var array
     */
    private static $irregular = [
        'atlas'     => 'atlases',
        'beef'      => 'beefs',
        'brief'     => 'briefs',
        'brother'   => 'brothers',
        'cafe'      => 'cafes',
        'child'     => 'children',
        'cookie'    => 'cookies',
        'corpus'    => 'corpuses',
        'cow'       => 'cows',
        'criterion' => 'criteria',
        'ganglion'  => 'ganglions',
        'genie'     => 'genies',
        'genus'     => 'genera',
        'graffito'  => 'graffiti',
        'hoof'      => 'hoofs',
        'loaf'      => 'loaves',
        'man'       => 'men',
        'money'     => 'monies',
        'mongoose'  => 'mongooses',
        'move'      => 'moves',
        'mythos'    => 'mythoi',
        'niche'     => 'niches',
        'numen'     => 'numina',
        'occiput'   => 'occiputs',
        'octopus'   => 'octopuses',
        'opus'      => 'opuses',
        'ox'        => 'oxen',
        'penis'     => 'penises',
        'person'    => 'people',
        'sex'       => 'sexes',
        'soliloquy' => 'soliloquies',
        'testis'    => 'testes',
        'trilby'    => 'trilbys',
        'turf'      => 'turfs',
        'potato'    => 'potatoes',
        'hero'      => 'heroes',
        'tooth'     => 'teeth',
        'goose'     => 'geese',
        'foot'      => 'feet',
        'foe'       => 'foes',
        'sieve'     => 'sieves',
    ];

    /**
     * Words that should not be inflected
     *
     * @var array
     */
    private static $uninflected = [
        '.*[nrlm]ese', '.*data', '.*deer', '.*fish', '.*measles', '.*ois',
        '.*pox', '.*sheep', 'people', 'feedback', 'stadia', '.*?media',
        'chassis', 'clippers', 'debris', 'diabetes', 'equipment', 'gallows',
        'graffiti', 'headquarters', 'information', 'innings', 'news', 'nexus',
        'pokemon', 'proceedings', 'research', 'sea[- ]bass', 'series', 'species', 'weather',
    ];

    /**
     * @var array
     */
    private static $inflectorCache = [];

    /**
     * @var array
     */
    private static $textToHtmlAttributeCache = [];

    /**
     * @param string $text
     *
     * @return string
     */
    public static function textareaDataToHtml(string $text): string
    {
        return str_replace(["\r", "\n", "\t"], ['', '<br>', '&nbsp;&nbsp;&nbsp;&nbsp;'], $text);
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public static function removeDuplicateSpaces(string $text): string
    {
        while (strpos($text, '  ') !== false) {
            $text = str_replace('  ', ' ', $text);
        }

        return $text;
    }

    /**
     * @param string $text
     * @param array  $skip_symbols
     *
     * @return string
     */
    public static function removeOddFileNameSymbols(string $text, array $skip_symbols = []): string
    {
        return str_replace(array_diff(['`', '~', '!', '@', '#', '$', '%', '^', '&', '*', '-', '_', '+', '=', '(', ')', '[', ']', '{', '}', '.', ',', '/', '\\', '\'', '"', ':', ';', '?', '>', '<'], $skip_symbols), ' ', $text);
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public static function nameToHtmlAttribute(string $text): string
    {
        if (isset(self::$textToHtmlAttributeCache[$text])) {
            return self::$textToHtmlAttributeCache[$text];
        }

        return self::$textToHtmlAttributeCache[$text] = preg_replace('/[^a-zA-Z0-9\_]+/', '_', $text);
    }

    /**
     * @param int  $number
     * @param int  $after
     * @param bool $use_thousand_separator
     *
     * @return string
     */
    public static function numberToPrice(int $number = 0, int $after = 2, bool $use_thousand_separator = false): string
    {
        $number = str_replace(',', '.', $number);

        if (!preg_match('/^[.0-9]+$/', $number)) {
            return $number;
        }

        if ($use_thousand_separator) {
            return number_format($number, $after);
        }

        return number_format($number, $after, '.', '');
    }

    /**
     * @param string $s
     *
     * @return string
     */
    public static function charsToNormalTitle(string $s): string
    {
        if (is_array($s)) {
            $res = [];

            foreach ($s as $k => $v) {
                $res[$k] = strtr(ucfirst(strtolower($v)), ['_' => ' ']);
            }

            $s = $res;
        } elseif (is_string($s)) {
            $s = strtr(ucfirst(strtolower($s)), ['_' => ' ']);
        }

        return $s;
    }

    /**
     * Get size from formatted ini params string
     *
     * @param string $res
     *
     * @return int - size in bytes
     */
    public static function formatIniParamSize(string $res): int
    {
        $res = trim($res);
        $measure = strtolower($res[strlen($res) - 1]);
        $res = (int)$res;

        // No breaks in switch, because we need multiple calculations
        switch ($measure) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case 'g':
                $res *= 1024;
            /** @noinspection PhpMissingBreakStatementInspection */
            case 'm':
                $res *= 1024;
            case 'k':
                $res *= 1024;
        }

        return $res;
    }

    /**
     * Converts file size from bytes to readable format
     *
     * @param int $b - File size
     *
     * @return string $res - Size in readable format
     */
    public static function formatDataSizeFromBytes(int $b): string
    {
        $unit = '';
        foreach (['B', 'KB', 'MB', 'GB', 'TB'] as $unit) {
            if ($b < 1024) break;
            $b = round($b / 1024, 1);
        }

        return $b . ' ' . $unit;
    }

    /**
     * Creates twitter and facebook like date str (e.g. "2 дня назад")
     *
     * @param int    $timestamp
     * @param string $lng
     *
     * @return string
     */
    public static function getTimeFromEventAgo(int $timestamp, string $lng = LNG)
    {
        if (!$timestamp) {
            return '';
        }

        $periods = [
            'ru' => [
                ['секунду', 'секунды', 'секунд'],
                ['минуту', 'минуты', 'минут'],
                ['час', 'часа', 'часов'],
                ['день', 'дня', 'дней'],
                ['неделю', 'недели', 'недель'],
                ['месяц', 'месяца', 'месяцев'],
                ['год', 'года', 'лет'],
                ['десятилетие', 'десятилетий', 'десятилетий'],
            ],
            'en' => [
                ['second', 'seconds', 'seconds'],
                ['minute', 'minutes', 'minutes'],
                ['hour', 'hours', 'hours'],
                ['day', 'days', 'days'],
                ['week', 'weeks', 'weeks'],
                ['month', 'months', 'months'],
                ['year', 'years', 'years'],
                ['decade', 'decades', 'decades'],
            ],
        ];

        $periods = isset($periods[$lng]) ? $periods[$lng] : $periods['en'];
        $lengths = ['60', '60', '24', '7', '4.35', '12', '10'];
        $cases = [2, 0, 1, 1, 1, 2];

        $difference = time() - $timestamp;
        for ($j = 0; $difference >= $lengths[$j]; $j++) {
            $difference /= $lengths[$j];
        }
        $difference = round($difference);

        $text = $periods[$j][($difference % 100 > 4 && $difference % 100 < 20) ? 2 : $cases[min($difference % 10, 5)]];

        return $difference . ' ' . $text . ' назад';
    }

    /**
     * Converts numbers like 2.36 to words like Two eur and 36 cents
     *
     * @param float  $sum
     * @param string $lng
     * @param string $currency
     *
     * @return string
     */
    public static function numberToWords(float $sum, string $lng = LNG, $currency = 'eur'): string
    {
        $res = '';
        $sum = number_format($sum, 2, '.', '');
        if ($lng == 'lv') {

            if ($currency == 'eur') {
                $language['parts'][0] = 'centi';
                $language['part'][0] = 'cents';
                $language['parts'][2] = 'euro';
                $language['part'][2] = 'euro';
            } elseif ($currency == 'ls') {
                $language['parts'][0] = 'santīmi';
                $language['part'][0] = 'santīms';
                $language['parts'][2] = 'lati';
                $language['part'][2] = 'lats';
            }

            $language['parts'][3] = 'tūkstoši';
            $language['part'][3] = 'tūkstotis';
            $language['parts'][4] = 'miljoni';
            $language['part'][4] = 'miljons';
            $language['parts'][5] = 'miljardi';
            $language['part'][5] = 'miljards';

            $language['parts']['hundred'] = 'simti';
            $language['part']['hundred'] = 'simts';

            $language['tens'] = [-1 => '', "Desmit", "Divdesmit", "Trīsdesmit", "Četrdesmit", "Piecdesmit", "Sešdesmit", "Septiņdesmit", "Astoņdesmit", "Deviņdesmit"];
            $language['teens'] = ["Vienpadsmit", "Divpadsmit", "Trīspadsmit", "Četrpadsmit", "Piecpadsmit", "Sešpadsmit", "Septiņpadsmit", "Astoņpadsmit", "Deviņpadsmit"];
            $language['ones'] = ["Viens", "Divi", "Trīs", "Četri", "Pieci", "Seši", "Septiņi", "Astoņi", "Deviņi"];

            $say = '';
            $number = $sum;

            $number = explode('.', $number);

            $big = $number[0];
            $small = $number[1];

            while (strlen($big)) {
                $bigs[] = substr($big, -3, 3);
                $big = substr($big, 0, -3);
            }
            krsort($bigs);

            foreach ($bigs as $key => & $value) {

                $teens = false;

                while (strlen($value) < 3) $value = ' ' . $value;

                if ($value[0] != ' ') {
                    if (isset($language['ones'][$value[0] - 1])) {
                        $say .= $language['ones'][$value[0] - 1] . ' ' . $language['part' . ($value[0] == '1' ? '' : 's')]['hundred'] . ' ';
                    }
                }

                if ($value[1] != ' ') {
                    if (isset($language['tens'][$value[1] - 1])) {
                        if ($value[1] == 1) $teens = true;
                        else $say .= $language['tens'][$value[1] - 1] . ' ';
                    }
                }

                if ($teens) {
                    if ($value[2] == 0 && $value[1] == 1) {
                        $say .= $language['tens'][0] . ' ';
                    } else {
                        $say .= $language['teens'][$value[2] - 1] . ' ';
                    }
                } else $say .= (isset($language['ones'][$value[2] - 1]) ? $language['ones'][$value[2] - 1] : '') . ' ';


                if ($value[2] == 1 && !$teens) $say .= $language['part'][$key + 2] . ' ';
                else $say .= $language['parts'][$key + 2] . ' ';
            }

            $say = trim($say);

            $say .= ', ' . $small . ' ' . $language['parts'][0];

            $res = mb_substr($say, 0, 1) . mb_strtolower(mb_substr($say, 1), 'UTF-8');


        } elseif ($lng == 'en') {

            if ($currency == 'eur') {
                $language['parts'][0] = 'cents';
                $language['part'][0] = 'cent';
                $language['parts'][2] = 'euro';
                $language['part'][2] = 'euro';
            } elseif ($currency == 'ls') {
                $language['parts'][0] = 'santīmi';
                $language['part'][0] = 'santīms';
                $language['parts'][2] = 'lati';
                $language['part'][2] = 'lats';
            }

            $language['parts'][3] = 'thousands';
            $language['part'][3] = 'thousand';
            $language['parts'][4] = 'millions';
            $language['part'][4] = 'million';
            $language['parts'][5] = 'billions';
            $language['part'][5] = 'billion';

            $language['parts']['hundred'] = 'hundreds';
            $language['part']['hundred'] = 'hundred';

            $language['tens'] = [-1 => '', "Ten", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];
            $language['teens'] = ["Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", "Nineteen"];
            $language['ones'] = ["One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine"];

            $say = '';
            $number = $sum;

            $number = explode('.', $number);

            $big = $number[0];
            $small = $number[1];

            while (strlen($big)) {
                $bigs[] = substr($big, -3, 3);
                $big = substr($big, 0, -3);
            }
            krsort($bigs);

            foreach ($bigs as $key => & $value) {

                $teens = false;

                while (strlen($value) < 3) $value = ' ' . $value;

                if ($value[0] != ' ') {
                    if (isset($language['ones'][$value[0] - 1])) {
                        $say .= $language['ones'][$value[0] - 1] . ' ' . $language['part' . ($value[0] == '1' ? '' : 's')]['hundred'] . ' ';
                    }
                }

                if ($value[1] != ' ') {
                    if (isset($language['tens'][$value[1] - 1])) {
                        if ($value[1] == 1) $teens = true;
                        else $say .= $language['tens'][$value[1] - 1] . ' ';
                    }
                }

                if ($teens) {
                    if ($value[2] == 0 && $value[1] == 1) {
                        $say .= $language['tens'][0] . ' ';
                    } else {
                        $say .= $language['teens'][$value[2] - 1] . ' ';
                    }
                } else $say .= (isset($language['ones'][$value[2] - 1]) ? $language['ones'][$value[2] - 1] : '') . ' ';


                if ($value[2] == 1 && !$teens) $say .= $language['part'][$key + 2] . ' ';
                else $say .= $language['parts'][$key + 2] . ' ';
            }

            $say = trim($say);

            $say .= ', ' . $small . ' ' . $language['parts'][0];

            $res = mb_substr($say, 0, 1) . mb_strtolower(mb_substr($say, 1), 'UTF-8');


        } elseif ($lng == 'ru') {
            /**
             * Склоняем словоформу
             * @ author runcore
             *
             * @param $n
             * @param $f1
             * @param $f2
             * @param $f5
             *
             * @return mixed
             */
            $morph = function($n, $f1, $f2, $f5) {
                $n = abs(intval($n)) % 100;
                if ($n > 10 && $n < 20) return $f5;
                $n = $n % 10;
                if ($n > 1 && $n < 5) return $f2;
                if ($n == 1) return $f1;

                return $f5;
            };

            $nul = 'ноль';
            $ten = [
                ['', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'],
                ['', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'],
            ];
            $a20 = ['десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать'];
            $tens = [2 => 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто'];
            $hundred = ['', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот'];
            $unit = [ // Units
                ['евроцент', 'евроцента', 'евроцентов', 1],
                ['евро', 'евро', 'евро', 0],
                ['тысяча', 'тысячи', 'тысяч', 1],
                ['миллион', 'миллиона', 'миллионов', 0],
                ['миллиард', 'милиарда', 'миллиардов', 0],
            ];
            //
            list($rub, $kop) = explode('.', sprintf("%015.2f", floatval($sum)));
            $out = [];
            if (intval($rub) > 0) {
                foreach (str_split($rub, 3) as $uk => $v) { // by 3 symbols
                    if (!intval($v)) continue;
                    $uk = sizeof($unit) - $uk - 1; // unit key
                    $gender = $unit[$uk][3];
                    list($i1, $i2, $i3) = array_map('intval', str_split($v, 1));
                    // mega-logic
                    $out[] = $hundred[$i1]; # 1xx-9xx
                    if ($i2 > 1) $out[] = $tens[$i2] . ' ' . $ten[$gender][$i3]; # 20-99
                    else $out[] = $i2 > 0 ? $a20[$i3] : $ten[$gender][$i3]; # 10-19 | 1-9
                    // units without rub & kop
                    if ($uk > 1) $out[] = $morph($v, $unit[$uk][0], $unit[$uk][1], $unit[$uk][2]);
                } //foreach
            } else $out[] = $nul;
            $out[] = $morph(intval($rub), $unit[1][0], $unit[1][1], $unit[1][2]); // rub
            $out[] = $kop . ' ' . $morph($kop, $unit[0][0], $unit[0][1], $unit[0][2]); // kop
            $res = trim(preg_replace('/ {2,}/', ' ', join(' ', $out)));
        }

        return $res;
    }

    /**
     * Cuts long sentences so only first few words are returned
     *
     * @param string $sentence
     * @param int    $max_length
     * @param string $separator
     * @param string $postfix to add after sentence is cut
     *
     * @return string
     */
    public static function cutLongStrings(string $sentence, int $max_length = 50, string $separator = ' ', string $postfix = '...'): string
    {
        // Return actual string if have nothing to cut
        if (mb_strlen($sentence, 'utf-8') <= $max_length) {
            return $sentence;
        }

        $words = mb_split("[" . $separator . "]+", $sentence);
        $res = '';
        $count_of_words = count($words);

        for ($i = 0; $i < $count_of_words; $i++) {
            if (mb_strlen($res . $words[$i] . ' ', 'utf-8') < $max_length) {
                // Add to returned string
                $res .= $words[$i] . ' ';
            } else {
                // Enough
                break;
            }
        }

        return mb_substr($res, 0, -1, 'utf-8') . $postfix;
    }

    /**
     * @param string $input
     * @param string $delimiter
     *
     * @return string
     */
    public static function fromCamelCase(string $input, string $delimiter = "_"): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];

        // Change every part by reference
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }

        return implode($delimiter, $ret);
    }

    /**
     * @param string $input
     * @param string $delimiter
     *
     * @return string
     */
    public static function toCamelCase(string $input, string $delimiter = '_'): string
    {
        $str = [];

        foreach (explode($delimiter, $input) as $v) {
            $str[] = ucfirst($v);
        }

        return implode('', $str);
    }

    /**
     * @param object $object
     *
     * @return string
     */
    public static function classWithNamespaceToUnqualifiedShort($object): string
    {
        $reflection = new \ReflectionClass($object);

        return $reflection->getShortName();
    }

    /**
     * @param object $object
     *
     * @return string
     */
    public static function getPathToClassFile($object): string
    {
        $reflector = new \ReflectionClass($object);

        return $reflector->getFileName();
    }

    /**
     * Returns same text with changed link word to full hrefs
     *
     * @param string $text
     *
     * @return string
     */
    public static function replaceLinksInTextsWithHrefs(string $text): string
    {
        $text = preg_replace('#(script|about|applet|activex|chrome):#is', "\\1:", $text);

        $ret = ' ' . $text;

        // Replace links with http://
        $ret = preg_replace("#(^|[\n ])([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*)#is", "\\1<a href=\"\\2\" target=\"_blank\" rel=\"nofollow\">\\2</a>", $ret);

        // Replace links without http://
        $ret = preg_replace("#(^|[\n ])((www|ftp)\.[\w\#$%&~/.\-;:=,?@\[\]+]*)#is", "\\1<a href=\"http://\\2\" target=\"_blank\" rel=\"nofollow\">\\2</a>", $ret);

        // Replace email addresses
        $ret = preg_replace("#(^|[\n ])([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i", "\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>", $ret);
        $ret = substr($ret, 1);

        return $ret;
    }

    /**
     * @param int $time
     *
     * @return string
     */
    public static function secondsToPeriod(int $time): string
    {
        $seconds_in_minute = 60;
        $seconds_in_hour = 3600;
        $seconds_in_day = 86400;

        // Timing
        $days = floor($time / $seconds_in_day);
        $time -= ($seconds_in_day * $days);

        $hours = floor($time / $seconds_in_hour);
        $time -= ($seconds_in_hour * $hours);

        $minutes = floor($time / $seconds_in_minute);
        $time -= ($seconds_in_minute * $minutes);

        $seconds = $time;

        // Format
        $out = '';
        if ($days) {
            $out .= $days . ' ';
        }
//        if ($hours) {
        $hours = str_pad($hours, 2, '0', STR_PAD_LEFT);
        $out .= $hours . ':';
//        }
//        if ($minutes) {
        $minutes = str_pad($minutes, 2, '0', STR_PAD_LEFT);
        $out .= $minutes . ':';
//        }
        $seconds = str_pad($seconds, 2, '0', STR_PAD_LEFT);
        $out .= $seconds;

        return $out;
    }

    /**
     * @param string $startDate YYYY-MM-DD
     * @param string $endDate   YYYY-MM-DD
     * @param array  $holidays  of days that are required to be skipped
     *
     * @return float|int
     */
    public static function calculateBusinessDaysBetweenTwoDates($startDate, $endDate, $holidays = [])
    {
        // Do strtotime calculations just once
        $endDate = strtotime($endDate);
        $startDate = strtotime($startDate);

        // The total number of days between the two dates. We compute the amount of seconds and divide it to 60*60*24
        // We add one ыусщтв to include both dates in the interval
        $days = ($endDate - $startDate) / 86400 + 1;

        $no_full_weeks = floor($days / 7);
        $no_remaining_days = fmod($days, 7);

        //It will return 1 if it's Monday,.. ,7 for Sunday
        $the_first_day_of_week = date("N", $startDate);
        $the_last_day_of_week = date("N", $endDate);

        // The two can be equal in leap years when february has 29 days, the equal sign is added here
        // In the first case the whole interval is within a week, in the second case the interval falls in two weeks.
        if ($the_first_day_of_week <= $the_last_day_of_week) {
            if ($the_first_day_of_week <= 6 && 6 <= $the_last_day_of_week) {
                $no_remaining_days--;
            }
            if ($the_first_day_of_week <= 7 && 7 <= $the_last_day_of_week) {
                $no_remaining_days--;
            }
        } else {
            // Fix an edge case where the start day was a Sunday and the end day was NOT a Saturday)

            // The day of the week for start is later than the day of the week for end
            if ($the_first_day_of_week == 7) {
                // if the start date is a Sunday, then we definitely subtract 1 day
                $no_remaining_days--;

                if ($the_last_day_of_week == 6) {
                    // if the end date is a Saturday, then we subtract another day
                    $no_remaining_days--;
                }
            } else {
                // the start date was a Saturday (or earlier), and the end date was (Mon..Fri)
                // so we skip an entire weekend and subtract 2 days
                $no_remaining_days -= 2;
            }
        }

        //The amount of business days is: (number of weeks between the two dates) * (5 working days) + the remainder
        // February in none leap years gave a remainder of 0 but still calculated weekends between first and last day, this is one way to fix it
        $workingDays = $no_full_weeks * 5;
        if ($no_remaining_days > 0) {
            $workingDays += $no_remaining_days;
        }

        //We subtract the holidays
        foreach ($holidays as $holiday) {
            $time_stamp = strtotime($holiday);
            // If the holiday doesn't fall in weekend
            if ($startDate <= $time_stamp && $time_stamp <= $endDate && date("N", $time_stamp) != 6 && date("N", $time_stamp) != 7) {
                $workingDays--;
            }
        }

        return $workingDays;
    }

    /**
     * @param string $word
     *
     * @return string
     */
    public static function pluralize(string $word): string
    {
        if (isset(static::$inflectorCache['pluralize'][$word])) {
            return static::$inflectorCache['pluralize'][$word];
        }

        if (!isset(static::$inflectorCache['irregular']['pluralize'])) {
            static::$inflectorCache['irregular']['pluralize'] = '(?:' . implode('|', array_keys(static::$irregular)) . ')';
        }

        if (preg_match('/(.*?(?:\\b|_))(' . static::$inflectorCache['irregular']['pluralize'] . ')$/i', $word, $regs)) {
            static::$inflectorCache['pluralize'][$word] = $regs[1] . substr($regs[2], 0, 1) .
                substr(static::$irregular[strtolower($regs[2])], 1);

            return static::$inflectorCache['pluralize'][$word];
        }

        if (!isset(static::$inflectorCache['uninflected'])) {
            static::$inflectorCache['uninflected'] = '(?:' . implode('|', static::$uninflected) . ')';
        }

        if (preg_match('/^(' . static::$inflectorCache['uninflected'] . ')$/i', $word, $regs)) {
            static::$inflectorCache['pluralize'][$word] = $word;

            return $word;
        }

        foreach (static::$plural as $rule => $replacement) {
            if (preg_match($rule, $word)) {
                static::$inflectorCache['pluralize'][$word] = preg_replace($rule, $replacement, $word);

                return static::$inflectorCache['pluralize'][$word];
            }
        }

        return $word;
    }

    /**
     * Return $word in singular form.
     *
     * @param string $word Word in plural
     *
     * @return string Word in singular
     *
     * @link http://book.cakephp.org/3.0/en/core-libraries/inflector.html#creating-plural-singular-forms
     */
    public static function singularize(string $word): string
    {
        if (isset(static::$inflectorCache['singularize'][$word])) {
            return static::$inflectorCache['singularize'][$word];
        }

        if (!isset(static::$inflectorCache['irregular']['singular'])) {
            static::$inflectorCache['irregular']['singular'] = '(?:' . implode('|', static::$irregular) . ')';
        }

        if (preg_match('/(.*?(?:\\b|_))(' . static::$inflectorCache['irregular']['singular'] . ')$/i', $word, $regs)) {
            static::$inflectorCache['singularize'][$word] = $regs[1] . substr($regs[2], 0, 1) .
                substr(array_search(strtolower($regs[2]), static::$irregular), 1);

            return static::$inflectorCache['singularize'][$word];
        }

        if (!isset(static::$inflectorCache['uninflected'])) {
            static::$inflectorCache['uninflected'] = '(?:' . implode('|', static::$uninflected) . ')';
        }

        if (preg_match('/^(' . static::$inflectorCache['uninflected'] . ')$/i', $word, $regs)) {
            static::$inflectorCache['pluralize'][$word] = $word;

            return $word;
        }

        foreach (static::$singular as $rule => $replacement) {
            if (preg_match($rule, $word)) {
                static::$inflectorCache['singularize'][$word] = preg_replace($rule, $replacement, $word);

                return static::$inflectorCache['singularize'][$word];
            }
        }

        static::$inflectorCache['singularize'][$word] = $word;

        return $word;
    }

    /**
     * @param int    $month_number
     * @param string $lng
     * @param int    $type 1 or 2
     *
     * @return string
     */
    public static function getMonthNameByNumber(int $month_number, string $lng = LNG, int $type = 1): string
    {
        $data = [
            'ru' => [
                1 => [
                    'январь',
                    'февраль',
                    'март',
                    'апрель',
                    'май',
                    'июнь',
                    'июль',
                    'август',
                    'сентябрь',
                    'октябрь',
                    'ноябрь',
                    'декабрь',
                ],
                2 => [
                    'января',
                    'февраля',
                    'марта',
                    'апреля',
                    'мая',
                    'июня',
                    'июля',
                    'августа',
                    'сентября',
                    'октября',
                    'ноября',
                    'декабря',
                ],
            ],
        ];

        if (!isset($data[$lng])) {
            dump('Months not set for language "' . $lng . '"');
        }

        if (!isset($data[$lng][$type])) {
            dump('Type "' . $type . '" not set for language "' . $lng . '"');
        }

        return $data[$lng][$type][$month_number - 1];
    }

    /**
     * @param int $code
     *
     * @return string
     */
    public static function headerHttpCodeToString(int $code): string
    {
        $status_codes = [
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            426 => 'Upgrade Required',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates',
            507 => 'Insufficient Storage',
            509 => 'Bandwidth Limit Exceeded',
            510 => 'Not Extended',
        ];

        return $status_codes[$code];
    }
}