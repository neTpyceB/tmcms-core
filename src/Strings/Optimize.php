<?php
declare(strict_types=1);

namespace TMCms\Strings;

defined('INC') || exit;

/**
 * Class Optimize
 */
class Optimize
{
    /**
     * Removes all spaces in HTML output
     *
     * @param string $html
     *
     * @return string
     */
    public static function Html($html): string
    {
        // Remove newlines, tabs, zero
        $html = str_replace(["\0", "\t", "\r"], '', $html);

        // Remove spaces
        while (strpos($html, '  ') !== false) {
            $html = str_replace('  ', ' ', $html);
        }

        // Remove spaces after new lines
        $html = str_replace("\n ", "\n", $html);

        // Can optimize only without textarea, because in have to keep formatting
        $textarea = strpos($html, '<textarea');
        if ($textarea !== false) {
            // Remove double newlines
            while (strpos($html, "\n\n") !== false) {
                $html = str_replace("\n\n", "\n", $html);
            }
        }

        $fl = substr($html, 0, strpos($html, "\n")); // First line
        if (stripos($fl, 'strict') === false && substr($fl, 0, 5) !== '<?xml') {
            $html = str_replace('/>', '>', $html);
            if ($textarea === false) {
                $html = preg_replace('/([a-zA-Z0-9]+="[^"]+") /', '\\1', $html);
            }
        }
        $html = str_replace([' />'], ['/>'], $html);

        if ($textarea === false) {
            $html = str_replace("\n", '', $html);
        }

        return $html;
    }
}