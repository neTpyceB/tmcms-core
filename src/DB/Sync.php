<?php

namespace TMCms\DB;

use TMCms\Strings\Converter;
use TMCms\Traits\singletonInstanceTrait;

defined('INC') or exit;

/**
 * Class Sync
 * Used to get sql queries to make table changes
 *
 * @example
$sync = new Sync();
 * $structure_1 = DB::getCreateTable('table_1');
 * $structure_2 = DB::getCreateTable('table_2');
 * $res = $sync->getUpdates($structure_1, $structure_2);
 * -----
 * $res == array (
 * [0] => "ALTER TABLE `a` MODIFY `field_name` varchar(255) NOT NULL",
 * ...
 * )
 */
class Sync
{
    use singletonInstanceTrait;

    private $source_structure = ''; // structure dump of the reference database
    private $destination_structure = ''; // structure dump of database to update
    private $config = []; // updater configuration

    /**
     * Constructor
     * @access public
     */
    public function makeItWork()
    {
        $this->init();
    }

    private function init()
    {
        // table operations: create, drop; field operations: add, remove, modify
        $this->config['updateTypes'] = 'create, drop, add, remove, modify';

        // ignores default part in cases like (var)char NOT NULL default '' upon the comparison
        $this->config['varcharDefaultIgnore'] = true;

        // the same for int NOT NULL default 0
        $this->config['intDefaultIgnore'] = true;

        // ignores table autoincrement field value, also remove AUTO_INCREMENT value from the create query if exists
        $this->config['ignoreIncrement'] = true;

        // add 'IF NOT EXIST' to each CREATE TABLE query
        $this->config['forceIfNotExists'] = true;

        // remove 'IF NOT EXIST' if already exists CREATE TABLE dump
        $this->config['ingoreIfNotExists'] = false;
    }

    /**
     * merges current updater config with the given one
     * @param array $config assoc. array new configuration values
     */
    public function setConfig($config = [])
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * Returns array of update SQL with default options, $source, $destination - database structures
     * @access public
     * @param string $source structure dump of database to update
     * @param string $destination structure dump of the reference database
     * @param bool $asString if true - result will be a string, otherwise - array
     * @return array|string update sql statements - in array or string (separated with ';')
     */
    public function getUpdates($source, $destination, $asString = false)
    {
        // Remove double spaces
        $source = Converter::spaces2space($source);
        $destination = Converter::spaces2space($destination);

        // Cut sql to check if structure is same
        $source_cut = strstr($source, 'AUTO_INCREMENT=', true);
        $destination_cut = strstr($source, 'AUTO_INCREMENT=', true);

        // Same structure - return nothing
        if ($source_cut == $destination_cut) {
//            return '';
        }

        $result = $asString ? '' : [];

        $compRes = $this->compare($source, $destination);
        if (empty($compRes)) {
            return $result;
        }

        $compRes = $this->filterDiffs($compRes);
        if (empty($compRes)) {
            return $result;
        }

        $result = $this->getDiffSql($compRes);
        if ($asString) {
            $result = implode(";\r\n", $result) . ';';
        }

        return $result;
    }

    /**
     * Filters comparison result and lefts only sync actions allowed by 'updateTypes' option
     * @param array $compRes
     * @return array
     */
    private function filterDiffs($compRes)
    {
        $result = [];

        if (is_array($this->config['updateTypes'])) {
            $updateActions = $this->config['updateTypes'];
        } else {
            $updateActions = array_map('trim', explode(',', $this->config['updateTypes']));
        }

        $allowedActions = ['create', 'drop', 'add', 'remove', 'modify'];
        $updateActions = array_intersect($updateActions, $allowedActions);

        foreach ($compRes as $table => $info) {
            if ($info['sourceOrphan']) {
                if (in_array('create', $updateActions)) {
                    $result[$table] = $info;
                }
            } elseif ($info['destOrphan']) {
                if (in_array('drop', $updateActions)) {
                    $result[$table] = $info;
                }
            } elseif ($info['differs']) {
                $resultInfo = $info;
                unset($resultInfo['differs']);

                foreach ($info['differs'] as $diff) {
                    if (empty($diff['dest']) && in_array('add', $updateActions)) {
                        $resultInfo['differs'][] = $diff;
                    } elseif (empty($diff['source']) && in_array('remove', $updateActions)) {
                        $resultInfo['differs'][] = $diff;
                    } elseif (in_array('modify', $updateActions)) {
                        $resultInfo['differs'][] = $diff;
                    }
                }

                if (!empty($resultInfo['differs'])) {
                    $result[$table] = $resultInfo;
                }
            }
        }
        return $result;
    }

    /**
     * Gets structured general info about the databases diff :
     * array(sourceOrphans=>array(...), destOrphans=>array(...), different=>array(...))
     * @param array $compRes
     * @return array|bool
     */
    public function getDiffInfo($compRes)
    {
        if (!is_array($compRes)) {
            return false;
        }

        $result = ['sourceOrphans' => [], 'destOrphans' => [], 'different' => []];

        foreach ($compRes as $table => $info) {
            if ($info['sourceOrphan']) {
                $result['sourceOrphans'][] = $table;
            } elseif ($info['destOrphan']) {
                $result['destOrphans'][] = $table;
            } else {
                $result['different'][] = $table;
            }
        }

        return $result;
    }

    /**
     * Makes comparison of the given database structures, support some options
     * @access private
     * @param string $source and $dest are strings - database tables structures
     * @param string $destination
     * @return array
     * - table (array)
     *        - destOrphan (boolean)
     *        - sourceOrphan (boolean)
     *        - differs (array) OR (boolean) false if no diffs
     *            - [0](array)
     *                - source (string) structure definition line in the out-of-date table
     *                - dest (string) structure definition line in the reference table
     *            - [1](array) ...
     */
    private function compare($source, $destination)
    {
        $this->source_structure = $source;
        $this->destination_structure = $destination;

        $result = [];
        $destTabNames = $this->getTableList($this->destination_structure);
        $sourceTabNames = $this->getTableList($this->source_structure);

        $common = array_intersect($destTabNames, $sourceTabNames);
        $destOrphans = array_diff($destTabNames, $common);
        $sourceOrphans = array_diff($sourceTabNames, $common);
        $all = array_unique(array_merge($destTabNames, $sourceTabNames));
        sort($all);

        foreach ($all as $tab) {
            $info = ['destOrphan' => false, 'sourceOrphan' => false, 'differs' => false];

            if (in_array($tab, $destOrphans)) {
                $info['destOrphan'] = true;
            } elseif (in_array($tab, $sourceOrphans)) {
                $info['sourceOrphan'] = true;
            } else {
                $destinationSql = $this->getTabSql($this->destination_structure, $tab, true);
                $sourceSql = $this->getTabSql($this->source_structure, $tab, true);
                $diffs = $this->compareSql($sourceSql, $destinationSql);

                if ($diffs === false) {
                    trigger_error('[WARNING] error parsing definition of table "' . $tab . '" - skipped');
                    continue;
                } elseif (!empty($diffs)) // not empty array
                {
                    $info['differs'] = $diffs;
                } else continue;//empty array
            }
            $result[$tab] = $info;
        }

        return $result;
    }

    /**
     * Retrieves list of table names from the database structure dump
     * @access private
     * @param string $structure database structure listing
     * @return array
     */
    private function getTableList($structure)
    {
        $result = [];

        if (preg_match_all('/CREATE(?:\s*TEMPORARY)?\s*TABLE\s*(?:IF NOT EXISTS\s*)?(?:`?(\w+)`?\.)?`?(\w+)`?/i', $structure, $m)) {
            foreach ($m[2] as $match)//m[1] is a database name if any
            {
                $result[] = $match;
            }
        }

        return $result;
    }

    /**
     * Retrieves table structure definition from the database structure dump
     * @access private
     * @param string $struct database structure listing
     * @param string $tab table name
     * @param bool $removeDatabase - either to remove database name in "CREATE TABLE database.tab"-like declarations
     * @return string table structure definition
     */
    private function getTabSql($struct, $tab, $removeDatabase = true)
    {
        $result = '';
        $database = '';
        $tableDef = '';

        /* create table should be single line in this case*/
        // 1 - part before database, 2-database name, 3 - part after database
        if (preg_match('/(CREATE(?:\s*TEMPORARY)?\s*TABLE\s*(?:IF NOT EXISTS\s*)?)(?:`?(\w+)`?\.)?(`?(' . $tab . ')`?(\W|$))/i', $struct, $m, PREG_OFFSET_CAPTURE)) {
            $tableDef = $m[0][0];
            $start = $m[0][1];
            $database = $m[2][0];
            $offset = $start + strlen($m[0][0]);
            $end = $this->getDelimiterPos($struct, $offset);
            if ($end === false) {
                $result = substr($struct, $start);
            } else {
                $result = substr($struct, $start, $end - $start);//already without ';'
            }
        }

        $result = trim($result);
        if ($database && $removeDatabase) {
            $result = str_replace($tableDef, $m[1][0] . $m[3][0], $result);
        }

        return $result;
    }

    /**
     * Splits table sql into indexed array
     * @param string $sql
     * @return array|bool
     */
    private function splitTabSql($sql)
    {
        $result = [];
        //find opening bracket, get the prefix along with it
        $openBracketPos = $this->getDelimiterPos($sql, 0, '(');

        if ($openBracketPos === false) {
            trigger_error('[WARNING] can not find opening bracket in table definition');
            return false;
        }

        $prefix = substr($sql, 0, $openBracketPos + 1); // prefix can not be empty, so do not check it, just trim
        $result[] = trim($prefix);
        $body = substr($sql, strlen($prefix)); // fields, indexes and part after closing bracket

        //split by commas, get part by part
        while (($commaPos = $this->getDelimiterPos($body, 0, ',', true)) !== false) {
            $part = trim(substr($body, 0, $commaPos + 1));//read another part and shorten $body
            if ($part) {
                $result[] = $part;
            }
            $body = substr($body, $commaPos + 1);
        }

        // here we have last field (or index) definition + part after closing bracket (ENGINE, ect)
        $closeBracketPos = $this->getDelimiterRightPosition($body, 0, ')');
        if ($closeBracketPos === false) {
            trigger_error('[WARNING] can not find closing bracket in table definition');
            return false;
        }

        // get last field / index definition before closing bracket
        $part = substr($body, 0, $closeBracketPos);
        $result[] = trim($part);

        // get the suffix part along with the closing bracket
        $suffix = substr($body, $closeBracketPos);
        $suffix = trim($suffix);
        if ($suffix) {
            $result[] = $suffix;
        }

        return $result;
    }

    /**
     * returns array of fields or keys definitions that differs in the given tables structure
     * @access private
     * @param string $sourceSql table structure
     * @param string $destinationSql right table structure
     * supports some $options
     * @return array
     *    - [0]
     *        - source (string) out-of-date table field definition
     *        - dest (string) reference table field definition
     *    - [1]...
     */
    private function compareSql($sourceSql, $destinationSql)//$sourceSql, $destSql
    {
        $result = [];
        //split with comma delimiter, not line breaks
        $sourceParts = $this->splitTabSql($sourceSql);

        if ($sourceParts === false) // error parsing sql
        {
            trigger_error('[WARNING] error parsing source sql');
            return false;
        }

        $destinationParts = $this->splitTabSql($destinationSql);
        if ($destinationParts === false) {
            trigger_error('[WARNING] error parsing destination sql');
            return false;
        }

        $sourcePartsIndexed = [];
        $destinationPartsIndexed = [];

        foreach ($sourceParts as $line) {
            $lineInfo = $this->processLine($line);
            if (!$lineInfo) continue;
            $sourcePartsIndexed[$lineInfo['key']] = $lineInfo['line'];
        }

        foreach ($destinationParts as $line) {
            $lineInfo = $this->processLine($line);
            if (!$lineInfo) continue;
            $destinationPartsIndexed[$lineInfo['key']] = $lineInfo['line'];
        }

        $sourceKeys = array_keys($sourcePartsIndexed);
        $destinationKeys = array_keys($destinationPartsIndexed);
        $all = array_unique(array_merge($sourceKeys, $destinationKeys));
        sort($all); // fields first, then indexes - because fields are prefixed with '!'

        foreach ($all as $key) {
            $info = ['source' => '', 'dest' => ''];
            $inSource = in_array($key, $sourceKeys);
            $inDestination = in_array($key, $destinationKeys);
            $sourceOrphan = $inSource && !$inDestination;
            $destinationOrphan = $inDestination && !$inSource;
            $different = $inSource && $inDestination && strcasecmp($this->normalizeString($destinationPartsIndexed[$key]), $this->normalizeString($sourcePartsIndexed[$key]));

            if ($sourceOrphan) {
                $info['source'] = $sourcePartsIndexed[$key];
            } elseif ($destinationOrphan) {
                $info['dest'] = $destinationPartsIndexed[$key];
            } elseif ($different) {
                $info['source'] = $sourcePartsIndexed[$key];
                $info['dest'] = $destinationPartsIndexed[$key];
            } else continue;

            $result[] = $info;
        }

        return $result;
    }

    /**
     * Transforms table structure definition line into key=>value pair where the key is a string that uniquely
     * defines field or key described
     * @access private
     * @param string $line field definition string
     * @return array array with single key=>value pair as described in the description
     * implements some options
     */
    private function processLine($line)
    {
        $options = $this->config;
        $result = ['key' => '', 'line' => ''];
        $line = rtrim(trim($line), ',');

        if (preg_match('/^(CREATE\s+TABLE)|(\) ENGINE=)/i', $line)) // first or last table definition line
        {
            return false;
        }

        // if (preg_match('/^(PRIMARY KEY)|(((UNIQUE )|(FULLTEXT ))?KEY `?\w+`?)/i', $line, $m))//key definition

        if (preg_match('/^(PRIMARY\s+KEY)|(((UNIQUE\s+)|(FULLTEXT\s+))?KEY\s+`?\w+`?)/i', $line, $m))//key definition
        {
            $key = $m[0];
        } elseif (preg_match('/^`?\w+`?/i', $line, $m))//field definition
        {
            $key = '!' . $m[0];//to make sure fields will be synchronised before the keys
        } else {
            return false;//line has no valuable info (empty or comment)
        }

        // $key = str_replace('`', '', $key);
        if (!empty($options['varcharDefaultIgnore'])) {
            $line = preg_replace("/(var)?char\(([0-9]+)\)\s+NOT\s+NULL\s+default\s+''/i", '$1char($2) NOT NULL', $line);
        }
        if (!empty($options['intDefaultIgnore'])) {
            $line = preg_replace("/((?:big)|(?:tiny))?int\(([0-9]+)\)\s+NOT\s+NULL\s+default\s+'0'/i", '$1int($2) NOT NULL', $line);
        }
        if (!empty($options['ignoreIncrement'])) {
            $line = preg_replace("/ AUTO_INCREMENT=[0-9]+/i", '', $line);
        }

        $result['key'] = $this->normalizeString($key);
        $result['line'] = $line;

        return $result;
    }

    /**
     * Takes an output of compare() method to generate the set of sql needed to update source table to make it
     * look as a destination one
     * @access private
     * @param array $diff compare() method output
     * @return array list of sql statements
     * supports query generation options
     */
    private function getDiffSql($diff)//maybe add option to omit or force 'IF NOT EXISTS', skip autoincrement
    {
        $options = $this->config;
        $sql_all = [];

        if (!is_array($diff) || empty($diff)) {
            return $sql_all;
        }

        foreach ($diff as $tab => $info) {
            if ($info['sourceOrphan'])//delete it
            {
                $sql_all[] = 'DROP TABLE `' . $tab . '`';
            } elseif ($info['destOrphan']) // create destination table in source
            {
                $database = '';
                $destinationSql = $this->getTabSql($this->destination_structure, $tab, $database);

                if (!empty($options['ignoreIncrement'])) {
                    $destinationSql = preg_replace("/\s*AUTO_INCREMENT=[0-9]+/i", '', $destinationSql);
                }
                if (!empty($options['ingoreIfNotExists'])) {
                    $destinationSql = preg_replace("/IF NOT EXISTS\s*/i", '', $destinationSql);
                }
                if (!empty($options['forceIfNotExists'])) {
                    $destinationSql = preg_replace('/(CREATE(?:\s*TEMPORARY)?\s*TABLE\s*)(?:IF\sNOT\sEXISTS\s*)?(`?\w+`?)/i', '$1IF NOT EXISTS $2', $destinationSql);
                }

                $sql_all[] = $destinationSql;
            } else {
                foreach ($info['differs'] as $diff_info) {
                    $inDestination = !empty($diff_info['dest']);
                    $inSource = !empty($diff_info['source']);
                    if ($inSource && !$inDestination) {
                        $sql = $diff_info['source'];
                        $action = 'drop';
                    } elseif ($inDestination && !$inSource) {
                        $sql = $diff_info['dest'];
                        $action = 'add';
                    } else {
                        $sql = $diff_info['dest'];
                        $action = 'modify';
                    }

                    $sql = $this->getActionSql($action, $tab, $sql);
                    $sql_all[] = $sql;
                }
            }
        }

        return $sql_all;
    }

    /**
     * Compiles update sql
     * @access private
     * @param string $action - 'drop', 'add' or 'modify'
     * @param string $tab table name
     * @param string $sql definition of the element to change
     * @return string update sql
     */
    private function getActionSql($action, $tab, $sql)
    {
        $result = 'ALTER TABLE `' . $tab . '` ';
        $action = strtolower($action);
        $keyField = '`?\w`?(?:\(\d+\))?';//matches `name`(10)
        $keyFieldList = '(?:' . $keyField . '(?:,\s?)?)+';//matches `name`(10),`desc`(255)

        if (preg_match('/((?:PRIMARY )|(?:UNIQUE )|(?:FULLTEXT ))?KEY `?(\w+)?`?\s(\(' . $keyFieldList . '\))/i', $sql, $m)) {   //key and index operations
            $type = strtolower(trim($m[1]));
            $name = trim($m[2]);
            $fields = trim($m[3]);

            switch ($action) {
                case 'drop':
                    if ($type == 'primary') {
                        $result .= 'DROP PRIMARY KEY';
                    } else {
                        $result .= 'DROP INDEX `' . $name . '`';
                    }
                    break;
                case 'add':
                    if ($type == 'primary') {
                        $result .= 'ADD PRIMARY KEY ' . $fields;
                    } elseif ($type == '') {
                        $result .= 'ADD INDEX `' . $name . '` ' . $fields;
                    } else {
                        $result .= 'ADD ' . strtoupper($type) . ' `' . $name . '` ' . $fields;//fulltext or unique
                    }
                    break;
                case 'modify':
                    if ($type == 'primary') {
                        $result .= 'DROP PRIMARY KEY, ADD PRIMARY KEY ' . $fields;
                    } elseif ($type == '') {
                        $result .= 'DROP INDEX `' . $name . '`, ADD INDEX `' . $name . '` ' . $fields;
                    } else {
                        $result .= 'DROP INDEX `' . $name . '`, ADD ' . strtoupper($type) . ' `' . $name . '` ' . $fields;//fulltext or unique
                    }
                    break;

            }
        } else // fields operations
        {
            $sql = rtrim(trim($sql), ',');
            $result .= strtoupper($action);
            if ($action == 'drop') {
                $spacePos = strpos($sql, ' ');
                $result .= ' ' . substr($sql, 0, $spacePos);
            } else {
                $result .= ' ' . $sql;
            }
        }

        return $result;
    }

    /**
     * Searches for the position of the next delimiter which is not inside string literal like 'this ; ' or
     * like "this ; ".
     *
     * Handles escaped \" and \'. Also handles sql comments.
     * Actualy it is regex-based Finit State Machine (FSN)
     * @param string $string
     * @param int $offset
     * @param string $delimiter
     * @param bool $skipInBrackets
     * @return bool
     */
    private function getDelimiterPos($string, $offset = 0, $delimiter = ';', $skipInBrackets = false)
    {
        $stack = [];
        $rbs = '\\\\';    //reg - escaped backslash
        $regPrefix = "(?<!$rbs)(?:$rbs{2})*";
        $reg = $regPrefix . '("|\')|(/\\*)|(\\*/)|(-- )|(\r\n|\r|\n)|';

        if ($skipInBrackets) {
            $reg .= '(\(|\))|';
        } else {
            $reg .= '()';
        }

        $reg .= '(' . preg_quote($delimiter) . ')';

        while (preg_match('%' . $reg . '%', $string, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $offset = $m[0][1] + strlen($m[0][0]);
            if (end($stack) == '/*') {
                if (!empty($m[3][0])) {
                    array_pop($stack);
                }
                continue;//here we could also simplify regexp
            }
            if (end($stack) == '-- ') {
                if (!empty($m[5][0])) {
                    array_pop($stack);
                }
                continue;//here we could also simplify regexp
            }

            if (!empty($m[7][0]))// ';' found
            {
                if (empty($stack)) {
                    return $m[7][1];
                } else {
                    //var_dump($stack, substr($string, $offset-strlen($m[0][0])));
                }
            }
            if (!empty($m[6][0]))// '(' or ')' found
            {
                if (empty($stack) && $m[6][0] == '(') {
                    array_push($stack, $m[6][0]);
                } elseif ($m[6][0] == ')' && end($stack) == '(') {
                    array_pop($stack);
                }
            } elseif (!empty($m[1][0]))// ' or " found
            {
                if (end($stack) == $m[1][0]) {
                    array_pop($stack);
                } else {
                    array_push($stack, $m[1][0]);
                }
            } elseif (!empty($m[2][0])) // opening comment / *
            {
                array_push($stack, $m[2][0]);
            } elseif (!empty($m[4][0])) // opening comment --
            {
                array_push($stack, $m[4][0]);
            }
        }

        return false;
    }

    /**
     * works the same as getDelimiterPos except returns position of the first occurrence of the delimiter starting from
     * the end of the string
     * @param string $string
     * @param int $offset
     * @param string $delimiter
     * @param bool $skipInBrackets
     * @return bool
     */
    private function getDelimiterRightPosition($string, $offset = 0, $delimiter = ';', $skipInBrackets = false)
    {
        $pos = $this->getDelimiterPos($string, $offset, $delimiter, $skipInBrackets);
        if ($pos === false) {
            return false;
        }

        do {
            $newPos = $this->getDelimiterPos($string, $pos + 1, $delimiter, $skipInBrackets);
            if ($newPos !== false) {
                $pos = $newPos;
            }
        } while ($newPos !== false);

        return $pos;
    }

    /**
     * Converts string to lowercase and replaces repeated spaces with the single one -
     * to be used for the comparison purposes only
     * @param string $str string to normaize
     * @return string
     */
    private function normalizeString($str)
    {
        $str = strtolower($str);
        $str = preg_replace('/\s+/', ' ', $str);

        return $str;
    }
}