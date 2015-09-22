<?php

namespace neTpyceB\Tests\TMCms\Network;

use neTpyceB\TMCms\Network\SearchEngines;

define('SearchEngines_TEST_URL', 'http://www.subsub.subdomain.google.lv/search?sourceid=chrome&ie=UTF-8&q=whaka+whaka');

class SearchEnginesTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSearchWord() {
        $res = SearchEngines::getSearchWord(SearchEngines_TEST_URL);

        $this->assertEquals('whaka whaka', $res);
    }
}