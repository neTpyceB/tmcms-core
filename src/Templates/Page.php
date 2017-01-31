<?php

namespace TMCms\Templates;

defined('INC') or exit;

/**
 * Class Page
 */
class Page {
    /**
     * @var PageHead $head
     */
    private static $head;
	/**
	 * @var PageBody
	 */
	private static $body;
	/**
	 * @var PageTail
	 */
	private static $tail;

    /**
     * @param PageHead $head
     */
    public static function setHead(PageHead $head) {
		self::$head = $head;
	}

    /**
     * @param PageBody $body
     */
    public static function setBody(PageBody $body) {
		self::$body = $body;
	}

	/**
	 * @param PageTail $tail
	 */
    public static function setTail(PageTail $tail) {
		self::$tail = $tail;
	}

    /**
     * @return PageHead
     */
    public static function getHead() {
		return self::$head;
	}

    /**
     * @return PageTail
     */
    public static function getTail() {
		return self::$tail;
	}

	/**
	 * @return PageBody
	 */
	public static function getBody() {
		return self::$body;
	}

    /**
	 * </html> tag at the end is required, start tag is in PageHead
     * @return string
     */
    public static function getHTML() {
        $classes = PageHead::getInstance()->getBodyCssClasses();
		return self::getHead() . '<body' . ($classes ? ' class="' . implode(' ', $classes) . '"' : '') . '>' . self::getBody() . self::getTail() . '</body></html>';
	}
}