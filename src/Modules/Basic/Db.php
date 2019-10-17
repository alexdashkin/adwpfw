<?php

namespace AlexDashkin\Adwpfw\Modules\Basic;

use AlexDashkin\Adwpfw\App;

/**
 * Database Helper
 */
class Db extends ModuleWithLogger
{
    /**
     * @var \wpdb
     */
    private $wpdb;

    /**
     * @var string WP DB prefix
     */
    public $prefix;

    /**
     * Db constructor.
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->wpdb = $GLOBALS['wpdb'];
        $this->prefix = $this->wpdb->prefix;
    }

    /**
     * Perform a DB Query.
     *
     * @param string $query SQL Query.
     * @param array $values If passed, $wpdb->prepare() will be called first.
     * @return mixed
     */
    public function query($query, array $values = [])
    {
        $sql = $values ? $this->wpdb->prepare($query, $values) : $query;

        return $this->result($this->wpdb->query($sql));
    }

    /**
     * Insert Data into a table.
     *
     * @param string $table Table Name.
     * @param array $data Data to insert.
     * @param bool $own Is own table?
     * @return int|bool Insert ID or false if failed.
     */
    public function insert($table, array $data, $own = true)
    {
        $t = $this->getTable($table, $own);
//		$data = $this->stripArray($data);

        return $this->result($this->wpdb->insert($t, $data)) ? $this->wpdb->insert_id : false;
    }

    /**
     * Update Data in a table.
     *
     * @param string $table Table Name.
     * @param array $data Data to insert.
     * @param array $where Conditions.
     * @param bool $own Is own table?
     * @return int|bool Insert ID or false if failed.
     */
    public function update($table, array $data, array $where, $own = true)
    {
        $t = $this->getTable($table, $own);

        return $this->result($this->wpdb->update($t, $data, $where));
    }

    /**
     * Insert or Update Data if exists.
     *
     * @param string $table Table Name.
     * @param array $data Data to insert.
     * @param array $where Conditions.
     * @param bool $own Is own table?
     * @return int|bool Insert ID or false if failed.
     */
    public function insertOrUpdate($table, array $data, array $where, $own = true)
    {
        if ($this->getResults($table, [], $where, true, $own)) {
            return $this->update($table, $data, $where, $own);
        }

        return $this->insert($table, $data, $own);
    }

    /**
     * Delete rows from a table.
     *
     * @param string $table Table Name.
     * @param array $where Conditions.
     * @param bool $own Is own table?
     * @return bool Succeed?
     */
    public function delete($table, array $where, $own = true)
    {
        $t = $this->getTable($table, $own);

        return $this->result($this->wpdb->delete($t, $where));
    }

    /**
     * Get Var.
     *
     * @param string $table Table Name.
     * @param string $var Field name.
     * @param array $where Conditions.
     * @param bool $own Is own table?
     * @return mixed
     */
    public function getVar($table, $var, array $where, $own = true)
    {
        $t = $this->getTable($table, $own);
        $whereArr = [];

        foreach ($where as $field => $value) {
            $whereArr[] = '`' . $field . '`=' . '"' . $value . '"';
        }

        $condition = implode(' AND ', $whereArr);
        $query = sprintf('SELECT `%s` FROM `%s` WHERE %s', $var, $t, $condition);

        return $this->result($this->wpdb->get_var($query));
    }

    /**
     * Get Results.
     *
     * @param string $table Table Name.
     * @param array $fields List of Fields.
     * @param array $where Conditions.
     * @param bool $single Get single row?
     * @param bool $own Is own table?
     * @return mixed
     */
    public function getResults($table, array $fields = [], array $where = [], $single = false, $own = true)
    {
        $t = $this->getTable($table, $own);
        $select = implode('`,`', $fields);
        $select = $select ? '`' . $select . '`' : '*';
        $whereArr = [];

        foreach ($where as $field => $value) {
            $whereArr[] = '`' . $field . '`="' . $value . '"';
        }

        $condition = implode(' AND ', $whereArr);
        $query = sprintf('SELECT %s FROM %s', $select, $t);

        if ($where) {
            $query .= " WHERE $condition";
        }

        $results = $this->result($this->wpdb->get_results($query, 'ARRAY_A'));

        return $results && $single ? reset($results) : $results;
    }

    /**
     * Get Results with an arbitrary Query.
     *
     * @param string $query SQL query.
     * @param array $values If passed, $wpdb->prepare() will be executed first.
     * @return mixed
     */
    public function getResultsQuery($query, array $values = [])
    {
        $sql = $values ? $this->wpdb->prepare($query, $values) : $query;

        return $this->result($this->wpdb->get_results($sql, 'ARRAY_A'));
    }

    /**
     * Get Results Count.
     *
     * @param string $table Table Name.
     * @param array $where Conditions.
     * @param bool $own Is own table?
     * @return int
     */
    public function getCount($table, array $where = [], $own = true)
    {
        $t = $this->getTable($table, $own);
        $whereArr = [];

        foreach ($where as $field => $value) {
            $whereArr[] = $field . '=' . '"' . $value . '"';
        }

        $condition = implode(' AND ', $whereArr);
        $query = sprintf('SELECT COUNT(*) FROM `%s`', $t);

        if ($where) {
            $query .= " WHERE $condition";
        }

        return $this->result((int)$this->wpdb->get_var($query));
    }

    /**
     * Get Last Insert ID.
     *
     * @return int
     */
    public function insertId()
    {
        return (int)$this->wpdb->insert_id;
    }

    /**
     * Insert Multiple Rows with one query.
     *
     * @param string $table Table Name.
     * @param array $data Data to insert.
     * @param bool $own Is own table?
     * @return bool
     */
    public function insertRows($table, array $data, $own = true)
    {
        $data = array_values($data);
        $t = $this->getTable($table, $own);
        $values = [];
        $counter = 0;

        $firstRow = reset($data);
        $cols = array_keys($firstRow);
        $columns = '`' . implode('`, `', $cols) . '`';
        $placeholders = str_repeat('%s, ', count($firstRow));

        foreach ($data as $index => $row) {
            /*            foreach ($row as $key => &$value) {
                            if (is_string($value)) {
                                $value = $this->stripValue($value); // todo
                            }
                        }*/

            $values = array_merge($values, array_values($row));
            $counter++;
        }

        if (!$counter) {
            $this->log('Nothing to insert, returning');
            return false;
        }

        $this->log("$counter items to insert");
        $columns = '(' . trim($columns, ', ') . ')';
        $placeholders = '(' . trim($placeholders, ', ') . '), ';
        $query = sprintf('INSERT INTO `%s` %s VALUES ', $t, $columns) . trim(str_repeat($placeholders, $counter), ', ');

        return $this->result($this->query($query, $values));
    }

    /**
     * Truncate a table.
     *
     * @param string $table Table Name.
     * @param bool $own Is own table?
     * @return bool
     */
    public function truncateTable($table, $own = true)
    {
        $t = $this->getTable($table, $own);

        return $this->result($this->query('TRUNCATE ' . $t));
    }

    /**
     * Check own tables existence.
     *
     * @param array $tables List of own tables.
     * @return bool
     */
    public function checkTables(array $tables)
    {
        foreach ($tables as $table) {
            $t = $this->getTable($table);
            $query = sprintf('SHOW TABLES LIKE "%s"', $t);
            if (empty($this->query($query))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get table name with all prefixes.
     *
     * @param string $name Table Name.
     * @param bool $own Is own table?
     * @return string
     */
    public function getTable($name, $own = true)
    {
        if (!$own) {
            return $this->prefix . $name;
        }

        if (empty($this->config['tables'][$name])) {
            $this->log("Table $name not found in config");
            return '';
        }

        return $this->prefix . $this->config['prefix'] . '_' . $this->config['tables'][$name];
    }

    /**
     * Remove not allowed chars from array.
     *
     * @param array $array
     * @return array
     */
    private function stripArray(array $array)
    {
        foreach ($array as &$item) {
            if (is_string($item)) {
                $item = $this->stripValue($item);
            }
        }

        return $array;
    }

    /**
     * Remove not allowed chars from string.
     *
     * @param string $value
     * @return string
     */
    private function stripValue($value)
    {
        $regex = '/((?:     [\x00-\x7F]
						|   [\xC2-\xDF][\x80-\xBF]
						|   \xE0[\xA0-\xBF][\x80-\xBF]
						|   [\xE1-\xEC][\x80-\xBF]{2}
						|   \xED[\x80-\x9F][\x80-\xBF]
						|   [\xEE-\xEF][\x80-\xBF]{2}
						|   \xF0[\x90-\xBF][\x80-\xBF]{2}
						|   [\xF1-\xF3][\x80-\xBF]{3}
						|   \xF4[\x80-\x8F][\x80-\xBF]{2}
					)+) | . /x';

        return preg_replace($regex, '$1', $value);
    }

    /**
     * WP DB functions wrapper
     *
     * @param mixed $result
     * @return mixed
     */
    private function result($result)
    {
        if (false === $result) {
            $message = !empty($this->wpdb->last_error) ? 'DB request error: ' . $this->wpdb->last_error : 'Unknown DB request error';
            $this->log($message);
        }

        return $result;
    }
}