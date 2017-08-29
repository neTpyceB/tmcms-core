<?php
declare(strict_types=1);

namespace TMCms\Strings;

use TMCms\Admin\Structure\Entity\Translation;
use TMCms\Admin\Structure\Entity\TranslationRepository;
use TMCms\Routing\Languages;

defined('INC') or exit;

/**
 * Class Translations
 */
class Translations
{
    private static $cached_data;

    /**
     * @param int $id
     */
    public static function delete($id)
    {
        Translation::getInstance($id)->deleteObject();
    }

    /**
     * Update translation
     *
     * @param array  $s
     * @param string $id
     *
     * @return string
     */
    public static function update(array $s, string $id): string
    {
        $languages = Languages::getPairs();
        if (!$languages) {
            // No languages
            return '';
        }

        if (!is_array($s) || !$s) {
            // No string supplied
            return '';
        }

        if ($id && ($translation_object = TranslationRepository::findOneEntityById($id))) {
            // Found existing translation
            $data = [];

            foreach ($s as $k => $v) {
                if (!isset($languages[$k])) {
                    // No language
                    return '';
                }

                $data[$k] = $v;
            }

            $translation_object
                ->loadDataFromArray($data)
                ->save();

            return $id;
        }

        // Need to create new
        return self::save($s);
    }

    /**
     * Create Translation
     *
     * @param array $s
     *
     * @return string
     */
    public static function save(array $s): string
    {
        $languages = Languages::getPairs();
        if (!$languages) {
            // No languages
            return '';
        }

        if (!is_array($s) || !$s) {
            // No string supplied
            return '';
        }

        $data = [];

        foreach ($s as $k => $v) {
            if (!isset($languages[$k])) {
                // No language with this key
                return '';
            }

            $data[$k] = $v;
        }

        return Translation::getInstance()
            ->loadDataFromArray($data)
            ->save()
            ->getId();
    }

    /**
     * Get translation for selected language
     *
     * @param string $id
     * @param string $lng
     *
     * @return string|array|NULL
     */
    public static function get(string $id, string $lng = '')
    {
        if (!isset(self::$cached_data[$id])) {
            $translation_obj = TranslationRepository::findOneEntityById($id);
            if ($translation_obj) {
                self::$cached_data[$id] = $translation_obj->getAsArray();
            }
        }

        if (!$lng) {
            return self::$cached_data[$id] ?? NULL;
        }

        return isset(self::$cached_data[$id], self::$cached_data[$id][$lng]) ? self::$cached_data[$id][$lng] : NULL;
    }
}