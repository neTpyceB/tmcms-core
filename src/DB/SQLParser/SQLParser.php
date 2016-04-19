<?php

namespace TMCms\DB\SQLParser;

use TMCms\Cache\Cacher;

defined('INC') or exit;

/**
 * Class SQLParser
 */
class SQLParser
{
    private static $flags;
    private static $cache = false;
    private static $func_symbols = 'QWERTYUIOPASDFGHJKLZXCVBNM_qwertyuiopasdfghjklzxcvbnm';

    private $sql;
    private $res = [];

    private static $str_select = [
        [
            'type' => 'word',
            'obligatory' => 1,
            'word' => 'SELECT',
            'desc' => 'sql'
        ],
        [
            'type' => 'word_variations',
            'obligatory' => 0,
            'variations' => ['ALL', 'DISTINCT', 'DISTINCTROW'],
            'desc' => 'flags'
        ],
        ['type' => 'word', 'obligatory' => 0, 'word' => 'HIGH_PRIORITY', 'desc' => 'flags'],
        ['type' => 'word', 'obligatory' => 0, 'word' => 'STRAIGHT_JOIN', 'desc' => 'flags'],
        ['type' => 'word', 'obligatory' => 0, 'word' => 'SQL_SMALL_RESULT', 'desc' => 'flags'],
        ['type' => 'word', 'obligatory' => 0, 'word' => 'SQL_BIG_RESULT', 'desc' => 'flags'],
        ['type' => 'word', 'obligatory' => 0, 'word' => 'SQL_BUFFER_RESULT', 'desc' => 'flags'],
        ['type' => 'word_variations', 'obligatory' => 0, 'variations' => ['SQL_CACHE', 'SQL_NO_CACHE'], 'desc' => 'flags'],
        ['type' => 'word', 'obligatory' => 0, 'word' => 'SQL_CALC_FOUND_ROWS', 'desc' => 'flags'],
        ['type' => 'expression', 'obligatory' => 1, 'expr' => 'select', 'desc' => 'select_expr'],
        ['type' => 'word', 'obligatory' => 0, 'word' => 'FROM', 'desc' => 'sql'],
        ['type' => 'expression', 'obligatory' => 0, 'depends' => 10, 'desc' => 'table_references'],

        /*12*/
        ['type' => 'word_variations', 'obligatory' => 0, 'variations' => ['JOIN', 'LEFT JOIN', 'INNER JOIN', 'RIGHT JOIN'], 'desc' => 'sql'],
        ['type' => 'expression', 'obligatory' => 1, 'depends' => 12, 'desc' => 'join_references'],
        ['type' => 'word', 'obligatory' => 1, 'depends' => 12, 'word' => 'ON'],
        ['type' => 'expression', 'obligatory' => 1, 'depends' => 12, 'go_to' => 12, 'desc' => 'join_conditions'],

        /*16*/
        ['type' => 'word', 'obligatory' => 0, 'word' => 'WHERE', 'desc' => 'sql'],
        ['type' => 'expression', 'obligatory' => 0, 'depends' => 16, 'desc' => 'where_conditions'],

        /*18*/
        ['type' => 'word', 'obligatory' => 0, 'word' => 'GROUP BY', 'desc' => 'sql'],
        ['type' => 'expression', 'obligatory' => 0, 'depends' => 18, 'desc' => 'group_conditions'],

        /*20*/
        ['type' => 'word', 'obligatory' => 0, 'word' => 'HAVING', 'desc' => 'sql'],
        ['type' => 'expression', 'obligatory' => 0, 'depends' => 20, 'desc' => 'having_conditions'],

        /*22*/
        ['type' => 'word', 'obligatory' => 0, 'word' => 'ORDER BY', 'desc' => 'sql'],
        ['type' => 'expression', 'obligatory' => 0, 'depends' => 22, 'desc' => 'order_conditions'],

        /*24*/
        ['type' => 'word', 'obligatory' => 0, 'word' => 'LIMIT', 'desc' => 'sql'],
        ['type' => 'expression', 'obligatory' => 0, 'depends' => 24, 'desc' => 'limit_conditions'],
    ];
    /*
    SELECT
        [ALL | DISTINCT | DISTINCTROW]
          [HIGH_PRIORITY]
          [STRAIGHT_JOIN]
          [SQL_SMALL_RESULT] [SQL_BIG_RESULT] [SQL_BUFFER_RESULT]
          [SQL_CACHE | SQL_NO_CACHE] [SQL_CALC_FOUND_ROWS]
        select_expr [, select_expr ...]
        [FROM table_references
        [WHERE where_condition]
        [GROUP BY {col_name | expr | position}
          [ASC | DESC], ... [WITH ROLLUP]]
        [HAVING where_condition]
        [ORDER BY {col_name | expr | position}
          [ASC | DESC], ...]
        [LIMIT {[offset,] row_count | row_count OFFSET offset}]
        [PROCEDURE procedure_name(argument_list)]
        [INTO OUTFILE 'file_name'
            [CHARACTER SET charset_name]
            export_options
          | INTO DUMPFILE 'file_name'
          | INTO var_name [, var_name]]
        [FOR UPDATE | LOCK IN SHARE MODE]]
    */

    /**
     * Class for parsing SQL string.
     * @param string $sql SQL string to parse
     */
    public function __construct($sql)
    {
        if (!isset(self::$flags)) {
            self::initFlags();
        }

        $this->sql = trim($sql);
        switch (strtoupper(substr($this->sql, 0, 6))) {
            case 'SELECT':
                $this->_select();
                break;
        }
    }

    private static function initFlags()
    {
        foreach (self::$str_select as &$row) {
            if (isset($row['desc']) && $row['desc'] === 'flags') {
                if (isset($row['variations'])) {
                    foreach ($row['variations'] as &$word) {
                        self::$flags[$word] = array_diff($row['variations'], [$word]);
                    }
                } else {
                    self::$flags[$row['word']] = 1;
                }
            }
        }
    }

    /**
     * @param string $flag
     */
    public function addFlag($flag)
    {
        if (!isset(self::$flags[$flag])) {
            trigger_error('Unknown SQL flag: ' . $flag);
        }

        if (is_array(self::$flags[$flag])) {
            foreach ($this->res['flags'] as $k => $v) {
                if (in_array($v, self::$flags[$flag])) {
                    unset($this->res['flags'][$k]);
                }
            }
            $this->res['flags'][$flag] = $flag;
        } else {
            foreach ($this->res['flags'] as $v) {
                if ($v == $flag) {
                    return;
                }
            }
            $this->res['flags'][$flag] = $flag;
        }
    }

    /**
     * @param string $flag
     */
    public function removeFlag($flag)
    {
        unset($this->res['flags'][$flag]);
    }

    public function removeAllFlags()
    {
        unset($this->res['flags']);
    }

    /**
     * @param string $desc
     * @return bool
     */
    public function getPart($desc)
    {
        return isset($this->res[$desc]) ? $this->res[$desc] : false;
    }

    /**
     * @param string $desc
     * @param string $value
     * @param int $index
     */
    public function setPart($desc, $value, $index = 0)
    {
        if (!ctype_digit((string)$index)) {
            trigger_error('Third parameter can be only an integer.');
        }
        $this->res[$desc][$index] = $value;
    }

    /**
     * @param string $desc
     * @param int $index
     */
    public function removePart($desc, $index = 0)
    {
        unset($this->res[$desc][$index]);
    }

    /**
     * @param string $word
     */
    public function removeByWord($word)
    {
        $word = strtoupper($word);
        $dependent_desc = [];

        foreach (self::$str_select as $k => $v) {
            // TO-DO add possibility to search by "word_variations"
            if ($v['type'] !== 'word' || $v['word'] !== $word) {
                continue;
            }

            foreach (self::$str_select as $v_inner) {
                if (isset($v_inner['depends']) && $v_inner['depends'] === $k) {
                    $dependent_desc[$v_inner['desc']] = 1;
                }
            }
            break;
        }
        foreach ($this->res['sql'] as $k => $v) {
            if ($v === $word) {
                unset($this->res['sql'][$k]);
            }
        }
        foreach ($dependent_desc as $k => $v) {
            unset($this->res[$k]);
        }
    }

    /**
     * @param string $desc
     */
    public function removeAllPart($desc)
    {
        unset($this->res[$desc]);
    }

    /**
     * @return string
     */
    private function getFlagsSQL()
    {
        return isset($this->res['flags']) ? implode(' ', $this->res['flags']) : '';
    }

    /**
     * @param array $sql_array
     * @return array
     */
    private static function sortSqlArray(array $sql_array)
    {
        $res = [];
        for ($i = 0, $so = count(self::$str_select); $i < $so; $i++) {
            $row =& self::$str_select[$i];
            if (isset($row['desc']) && $row['desc'] !== 'sql') {
                continue;
            }
            foreach ($sql_array as $k => &$v) {
                if (isset($row['word'])) {
                    if ($v === $row['word']) {
                        $res[] = $row['word'];
                        unset($sql_array[$k]);
                    }
                } elseif (isset($row['variations'])) {
                    foreach ($row['variations'] as &$w) {
                        if ($w == $v) {
                            $res[] = $w;
                            unset($sql_array[$k]);
                        }
                    }
                } else trigger_error('Error');
            }
        }
        return $res;
    }

    /**
     * Return SQL string
     * @return string
     */
    public function toSQL()
    {
        $this->res['sql'] = self::sortSqlArray($this->res['sql']);

        $res = $this->res['sql'][0] . ' ' . $this->getFlagsSQL() . ' ' . $this->res['select_expr'][0];
        $join_references_i = $join_conditions_i = 0;

        if (isset($this->res['where_conditions']) && $this->res['where_conditions'] && !in_array('WHERE', $this->res['sql'])) {
            trigger_error('Where conditions were set manually, but WHERE construction was not found in initial query. If you need to add conditions, WHERE construction should be in your query added manually.');
        }

        if (isset($this->res['order_conditions']) && $this->res['order_conditions'] && !in_array('ORDER BY', $this->res['sql'])) {
            trigger_error('Order conditions were set manually, but ORDER BY construction was not found in initial query. If you need to add conditions, ORDER BY construction should be in your query added manually.');
        }

        if (isset($this->res['group_conditions']) && $this->res['group_conditions'] && !in_array('GROUP BY', $this->res['sql'])) {
            trigger_error('Group conditions were set manually, but GROUP BY construction was not found in initial query. If you need to add conditions, GROUP BY construction should be in your query added manually.');
        }

        if (isset($this->res['having_conditions']) && $this->res['having_conditions'] && !in_array('HAVING', $this->res['sql'])) {
            trigger_error('Having conditions were set manually, but HAVING construction was not found in initial query. If you need to add conditions, HAVING construction should be in your query added manually.');
        }

        if (isset($this->res['limit_conditions']) && $this->res['limit_conditions'] && !in_array('LIMIT', $this->res['sql'])) {
            trigger_error('Limit conditions were set manually, but LIMIT construction was not found in initial query. If you need to add conditions, LIMIT construction should be in your query added manually.');
        }

        for ($i = 1, $so = count($this->res['sql']); $i < $so; $i++) {
            switch ($this->res['sql'][$i]) {
                default:
                    $res .= $this->res['sql'][$i] . ' ';
                    break;
                case 'FROM':
                    if (!isset($this->res['table_references'][0])) {
                        break;
                    }
                    $res .= ' FROM ' . $this->res['table_references'][0];
                    break;
                case 'JOIN':
                case 'LEFT JOIN':
                case 'RIGHT JOIN':
                case 'INNER JOIN':
                    $res .= ' ' . $this->res['sql'][$i] . ' ' . $this->res['join_references'][$join_references_i++] . ' ON ' . $this->res['join_conditions'][$join_conditions_i++]->toSQL();
                    break;
                case 'WHERE':
                    if (!isset($this->res['where_conditions'][0])) {
                        break;
                    }
                    $res .= ' WHERE ' . $this->res['where_conditions'][0]->toSQL();
                    break;
                case 'HAVING':
                    if (!isset($this->res['having_conditions'][0])) {
                        break;
                    }
                    $res .= ' HAVING ' . $this->res['having_conditions'][0];
                    break;
                case 'ORDER BY':
                    if (!isset($this->res['order_conditions'][0])) {
                        break;
                    }
                    $res .= ' ORDER BY ' . $this->res['order_conditions'][0];
                    break;
                case 'GROUP BY':
                    if (!isset($this->res['group_conditions'][0])) {
                        break;
                    }
                    $res .= ' GROUP BY ' . $this->res['group_conditions'][0];
                    break;
                case 'LIMIT':
                    if ($this->res['limit_conditions'][0]->isEmpty()) {
                        break;
                    }
                    $res .= ' LIMIT ' . $this->res['limit_conditions'][0];
                    break;
            }
        }
        return $res;
    }

    /**
     * @param int $i
     * @return array
     */
    private function _makeStopWordsVariations($i)
    {
        $res = [];
        $so = count(self::$str_select);
        for (; $i < $so; $i++) {
            $str =& self::$str_select[$i];
            switch ($str['type']) {
                case 'word':
                    $res[] = $str['word'];
                    break;
                case 'word_variations':
                    $res = array_merge($res, $str['variations']);
                    break;
                default:
                    continue 2;
            }
//			if ($str['obligatory']) break;
        }
        return $res;
    }

    /**
     * @return array|mixed
     */
    private function _select()
    {
        if (self::$cache) {
            $data = Cacher::getInstance()->getDefaultCacher()->get('sql_parser' . md5($this->sql));
            if ($data) {
                $this->res = unserialize($data);
                return $this->res;
            }
        }
        $pos = 0;
        $str_matched = [];
        $patient = $this->sql;

        for ($i = 0, $so = count(self::$str_select); $i < $so; $i++) {
            $str =& self::$str_select[$i];
            if (isset($str['depends']) && $str['depends'] && !$str_matched[$str['depends']]) {
                continue;
            }
            switch ($str['type']) {
                case 'word':
                    $pos = self::_parserCheckFragment($patient, $str['word']);
                    if ($pos === false && $str['obligatory']) {
                        trigger_error('Required fragment "' . $str['word'] . '" not found.');
                    }
                    break;
                case 'word_variations':
                    $found = 0;
                    foreach ($str['variations'] as &$v) {
                        $pos = self::_parserCheckFragment($patient, $v);
                        if ($pos !== false) {
                            $found = 1;
                            break;
                        }
                    }
                    if (!$found && $str['obligatory']) {
                        trigger_error('Required fragment "' . implode(', ', $str['variations']) . '" not found.');
                    }
                    unset($v, $found);
                    break;
                case 'expression':
                    $variations = [];
                    if (isset($str['go_to'])) {
                        $next_o =& self::$str_select[$str['go_to']];
                        switch ($next_o['type']) {
                            case 'word':
                                $variations[] = $next_o['word'];
                                break;
                            case 'word_variations':
                                $variations = $next_o['variations'];
                                break;
                            default:
                                exit('Internal error. Invalid type in element "' . $next_o . '". Expression should be always followed by "word" or "word_variations" type or be the last in structure.');
                        }
                        unset($next_o);
                    }
                    $next = $i + 1;
                    if (isset(self::$str_select[$next])) {
                        $next_o =& self::$str_select[$next];

                        switch ($next_o['type']) {
                            case 'word':
                            case 'word_variations':
                                $variations = array_merge($variations, $this->_makeStopWordsVariations($i));
                                break;
                            default:
                                exit('Internal error. Invalid type in element "' . $next . '". Expression should be always followed by "word" or "word_variations" type or be the last in structure.');
                        }
                        unset($next_o);
                    }
                    $pos = self::_parserPrepareExpression($patient, $variations ? array_unique($variations) : NULL);
                    unset($next);
                    break;
                default:
                    exit('Internal error. Unknown structure type "' . $str['type'] . '".');
            }

            //order_conditions
            if (ctype_digit((string)$pos)) {
                if (isset($str['desc']) && !isset($this->res[$str['desc']])) {
                    $this->res[$str['desc']] = [];
                }
                $fragment = substr($patient, 0, $pos);
                if (isset($str['desc']) && ($str['desc'] === 'join_conditions' || $str['desc'] === 'where_conditions')) {
                    $this->res[$str['desc']][] = new SQLWhereParser($fragment);
                } elseif (isset($str['desc']) && $str['desc'] === 'limit_conditions') {
                    $this->res[$str['desc']][] = new SQLLimitParser($fragment);
                } elseif (isset($str['desc'])) {
                    $this->res[$str['desc']][] = $fragment;
                }

                $patient = substr($patient, $pos);
                $str_matched[$i] = 1;
                unset($fragment);
            } else $str_matched[$i] = 0;

            // WTF
//			$patient = substr($patient, self::_skipShittySymbols($patient));
//			echo '<span style="color:', (ctype_digit((string)$pos) ? 'green' : 'red') ,'">', $str['type'];
//			switch ($str['type']) {
//				case 'word': echo ' "', $str['word'] ,'"'; break;
//				case 'word_variations': echo ' "', implode(', ', $str['variations']) ,'"'; break;
//			}
//			echo '<br><small><i>',
//					$str['desc'] ,'</i></small><br><b>', $fragment ,'</b> ',
//					$patient ,'<br><small>', (microtime(1) - $step_ts) ,'</small></span><hr>';

            if (isset($str['go_to'])) {
                $i = $str['go_to'] - 1;
//				echo 'Going to ', $i ,'<hr>';
            }
        }
        if (!isset($this->res['flags'])) {
            $this->res['flags'] = [];
        }
        if (!in_array('LIMIT', $this->res['sql'])) {
            $this->res['sql'][] = 'LIMIT';
            $this->res['limit_conditions'] = [new SQLLimitParser('')];
        }

        if (self::$cache) {
            Cacher::getInstance()->getDefaultCacher()->set('sql_parser' . md5($this->sql), serialize($this->res));
        }

        return $this->res;
    }

    /**
     * @param string $patient
     * @param array $stop_words
     * @return bool|int
     */
    private static function _parserPrepareExpression($patient, $stop_words = [])
    {
        $sw_1st_symb = $sw_so = $stop_word = '';
        if (isset($stop_words) && $stop_words) {
            if (count($stop_words) === 1) {
                $stop_word = $stop_words[0];
                $sw_1st_symb = $stop_word[0];
                $sw_so = strlen($stop_word);
                $stop = 1;
            } else {
                $sw_so = $sw_1st_symb = [];
                foreach ($stop_words as &$v) {
                    $sw_so[] = strlen($v);
                    $sw_1st_symb[$v[0]] = 0;
                }
                $stop = 2;
            }
        } else {
            $stop = false;
        }

        //	Because of the nesting of data between special symbols we have to use flags for all of them separately
        $in_backtick = $in_quote = $in_dquote = $in_brackets = 0;
        for ($i = 0, $so = strlen($patient); $i < $so; ++$i) {
            if ($patient[$i] === ')') {
                $in_brackets--;
            }
            if ($patient[$i] === '(') {
                $in_brackets++;
            }
            if ($in_brackets > 0) {
                continue;
            }
            if ($in_brackets < 0) {
                return false;
            }
            if ($patient[$i] === '\'') {
                $in_quote = !$in_quote;
            }
            if ($in_quote) {
                continue;
            }
            if ($patient[$i] === '"') {
                $in_dquote = !$in_dquote;
            }
            if ($in_dquote) {
                continue;
            }
            if ($patient[$i] === '`') {
                $in_backtick = !$in_backtick;
            }
            if ($in_backtick) {
                continue;
            }

            //	We are going to the next step in case we are not searching for stop words
            if ($stop === false) {
                continue;
            }
            $word_so = strspn($patient, self::$func_symbols, $i);
            if (!$word_so) {
                continue;
            }
            if ($stop === 1) {
                //	If there is only one stopword then checking directly
                if ($patient[$i] === $sw_1st_symb && substr($patient, $i, $sw_so) === $stop_word && !strspn($patient[$i + $sw_so], self::$func_symbols)) {
                    break;
                }
            } elseif (isset($sw_1st_symb[$patient[$i]])) {
                //	Otherwise searching in whole data set
                foreach ($stop_words as $k => &$v) {
                    if (substr($patient, $i, $sw_so[$k]) === $v && !strspn($patient[$i + $sw_so[$k]], self::$func_symbols)) {
                        break 2;
                    }
                }
            }
            $i += $word_so - 1; // - 1 is a fix for $in_brackets
        }
        return $in_backtick || $in_quote || $in_dquote || $in_brackets !== 0 ? false : $i;
    }

    /**
     * @param string $patient
     * @param string $fragment
     * @return int
     */
    private static function _parserCheckFragment($patient, $fragment)
    {
        $so = strlen($fragment);
        /*$symb_after = strtolower($patient[$so]);*/
        return substr($patient, 0, $so) === $fragment/* && !strspn($symb_after, 'qwertyuiopasdfghjklzxcvbnm_1234567890')*/ ? $so : false;
    }
    /*
        private static function _skipShittySymbols($patient) {
            return strspn($patient, " \r\n\t\0");
        }
     */

    /**
     * @return SQLLimitParser
     */
    public function getLimit()
    {
        return $this->res['limit_conditions'][0];
    }

    /**
     * @return SQLWhereParser
     */
    public function getWhere()
    {
        if (isset($this->res['where_conditions'][0])) {
            return $this->res['where_conditions'][0];
        }

        $this->res['where_conditions'][0] = new SQLWhereParser('1');
        $this->res['sql'][] = 'WHERE';
        return $this->res['where_conditions'][0];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->res;
    }
}