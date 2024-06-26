<?php
namespace DavidFricker\DataAbstracter\Adapter;

use \PDO;
use DavidFricker\DataAbstracter\Interfaces\InterfaceDatabaseWrapper;

/**
 * A wrapper around a DB driver to expose a uniform interface
 *
 * Basically an abstraction over the complexity of the PDO class, but by design this could wrap any structured storage mechanism
 * In addition, this class provides helper functions to make common queries quick and simple to perform
 */
class MySQLDatabaseWrapper extends \PDO implements InterfaceDatabaseWrapper {
    private $handle;
    private $error_str = '';

    private $default_options = array(
        1002 => 'SET NAMES utf8mb4',
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => false
    );

    /**
     * Wrapper constructor
     *
     * @throws PDOException if the database connection cannot be opened
     * @param string $dsn      dsn for PDO
     * @param string $username MySQL username
     * @param string $password MySQL password
     * @param array $options  PDO options, defaults provided
     */
    public function __construct($dsn, $username, $password, $options=null) {
        if ($options == null) {
            $options = $this->default_options;
        }

        parent::__construct($dsn, $username, $password, $options);
    }

    /**
     * Remove one or more rows
     *
     * As this is intended to be a simple helper function the only 'glue' to hold together the where clauses is 'AND' more complex delete statements should be performed using run()
     *
     * @param  string $table name of table in the database
     * @param  array  $where optional, key:value pairs - column and expected value to filter by
     * @param  boolean $limit optional, integer describing the amount of matching rows to delete
     * @return mixed see return value of run
     */
    public function delete($table, $where=[], $limit=false) {
        $sql = '';
        $bind_values = [];

        if (!is_array($where) || empty($where)) {
            $sql = 'DELETE FROM `' . $table . '`';
        } else {
            $bind_values = array_values($where);
            $sql = 'DELETE FROM  `' . $table . '` WHERE ' . $this->prepareBinding($where, ' AND ');
        }

        if ($limit && is_int($limit)) {
            $sql .= ' LIMIT '. $limit;
        }

        return $this->run($sql, $bind_values);
    }

    /**
     * Update one or more rows
     *
     * As this is intended to be a simple helper function the only 'glue' to hold together the where clauses is 'AND' more complex update statements should be performed using run()
     *
     * @param  string $table name of table in the database
     * @param  array $data  key:value pairs, key is the column and value is the new value for each affected row
     * @param  array $where optional, key:value pairs - column and expected value to filter by
     * @param  boolean $limit optional, integer describing the amount of matching rows to update
     * @return mixed see return value of run
     */
    public function update($table, $data, $where=[], $limit=false) {
        if (!is_array($data) || empty($data)) {
            return false;
        }

        $bind_values = array_values($data);
        $sql = 'UPDATE `' . $table . '` SET ' . $this->prepareBinding($data, ', ');

        if (is_array($where) && !empty($where)) {
            $sql .= ' WHERE ' . $this->prepareBinding($where, ' AND ');
            $bind_values = array_merge($bind_values, array_values($where));
        }

        if ($limit && is_int($limit)) {
            $sql .= ' LIMIT '. $limit;
        }

        return $this->run($sql, $bind_values);
    }

    /**
     * Pull one or more rows
     *
     * As this is intended to be a simple helper function the only 'glue' to hold together the where clauses is 'AND' more complex update statements should be performed using run()
     * IMPORTANT: Ensure the $columns variable does not contain user input as it is inserted as-is into the statement - vulnerable to SQL injection
     *
     * @param  string $table name of table in the database
     * @param  array $data  key:value pairs, key is the column and value is the new value for each affected row
     * @param  array $where optional, key:value pairs - column and expected value to filter by
     * @param  boolean $limit optional, integer describing the amount of matching rows to fetch
     * @return mixed see return value of run
     */
    public function fetch($table, $columns, $where=[], $limit=false, $order_by=false) {
        if (empty($columns)) {
            return false;
        }

        $bind_values = [];
        $sql = 'SELECT ' . implode(', ',$columns) . ' FROM `' . $table . '`';

        if (is_array($where) && !empty($where)) {
            $sql .= ' WHERE ' . $this->prepareBinding($where, ' AND ');
            $bind_values = array_merge($bind_values, array_values($where));
        }

        if ($order_by) {
            $sql .= ' ORDER BY '. $order_by;
        }

        if ($limit && is_int($limit)) {
            $sql .= ' LIMIT '. $limit;
        }

        if ($limit && is_array($limit)) {
            $sql .= ' LIMIT '. $limit[0] . ', ' . $limit[1];
        }

        return $this->run($sql, $bind_values);
    }

    /**
     * Pull one or more rows with general search query
     *
     * As this is intended to be a simple helper function the only 'glue' to hold together the where clauses is 'AND' more complex update statements should be performed using run()
     * IMPORTANT: Ensure the $columns variable does not contain user input as it is inserted as-is into the statement - vulnerable to SQL injection
     *
     * @param  string $table name of table in the database
     * @param  array $data  key:value pairs, key is the column and value is the new value for each affected row
     * @param  array $where optional, key:value pairs - column and expected value to filter by
     * @param  boolean $limit optional, integer describing the amount of matching rows to fetch
     * @return mixed see return value of run
     */
    public function fetchWithSearch($search, $table, $columns, $where=[], $limit=false, $order_by=false) {
        if (empty($columns)) {
            return false;
        }

        $bind_values = [];
        $sql = 'SELECT ' . implode($columns, ', ') . ' FROM `' . $table . '`';

        if (is_array($where) && !empty($where)) {
            $sql .= ' WHERE ' . $this->prepareBinding($where, ' AND ');
            $bind_values = array_merge($bind_values, array_values($where));


            $sql .= ' AND concat(' . implode(', \'\',', $columns) . ') like "%'.$search.'%"';
            // $sql .= ' AND MATCH (' . implode(',', $columns) . ') AGAINST("'.$search.'")';
        }else{

            $sql .= ' WHERE concat(' . implode(', \'\',', $columns) . ') like "%'.$search.'%"';
            //$sql .= ' WHERE MATCH (' . implode(',', $columns) . ') AGAINST("'.$search.'")';
        }
        //$searchQuery = [];
        //foreach ($columns as $key) {
        //   $searchQuery[] = "$key";
        //}

        // $sql .= ' AND concat(' . implode(', \'\',', $columns) . ') like "%'.$search.'%"';
//shipping_name, billing_name, email) AGAINST
        if ($order_by) {
            $sql .= ' ORDER BY '. $order_by;
        }

        if ($limit && is_int($limit)) {
            $sql .= ' LIMIT '. $limit;
        }

        if ($limit && is_array($limit)) {
            $sql .= ' LIMIT '. $limit[0] . ', ' . $limit[1];
        }

        return $this->run($sql, $bind_values);
    }

    /**
     * Pull one or more rows with general search query
     *
     * As this is intended to be a simple helper function the only 'glue' to hold together the where clauses is 'AND' more complex update statements should be performed using run()
     * IMPORTANT: Ensure the $columns variable does not contain user input as it is inserted as-is into the statement - vulnerable to SQL injection
     *
     * @param  string $table name of table in the database
     * @param  array $data  key:value pairs, key is the column and value is the new value for each affected row
     * @param  array $where optional, key:value pairs - column and expected value to filter by
     * @param  boolean $limit optional, integer describing the amount of matching rows to fetch
     * @return mixed see return value of run
     */
    public function countWithSearch($search, $table, $columns, $where=[]) {
        $bind_values = [];
        $sql = 'SELECT COUNT(*) as theCount FROM `' . $table . '`';

        if (is_array($where) && !empty($where)) {
            $sql .= ' WHERE ' . $this->prepareBinding($where, ' AND ');
            $bind_values = array_merge($bind_values, array_values($where));

            $sql .= ' AND concat(' . implode(', \'\',', $columns) . ') like "%'.$search.'%"';
        }else{
            $sql .= ' WHERE concat(' . implode(', \'\',', $columns) . ') like "%'.$search.'%"';
        }

        $result = $this->run($sql, $bind_values);
        if (!$result) {
            return $result;
        }

        return $result[0]['theCount'];
    }

    public function count($table, $where=[]) {
        $bind_values = [];
        $sql = 'SELECT COUNT(*) as theCount FROM `' . $table . '`';

        if (is_array($where) && !empty($where)) {
            $sql .= ' WHERE ' . $this->prepareBinding($where, ' AND ');
            $bind_values = array_merge($bind_values, array_values($where));
        }

        $result = $this->run($sql, $bind_values);
        if(!$result) {
            return $result;
        }

        return $result[0]['theCount'];
    }

    /**
     * Create a new row
     *
     * @param  string $table name of table in the database
     * @param  array $data  key:value pairs, key is the column and value is the value to set for the new row
     * @return mixed see return value of run
     */
    public function insert($table, $data) {
        if (!is_array($data)) {
            return false;
        }

        $fragment_sql = $this->prepareBinding($data, ', ');
        $bind_values = array_values($data);
        $query = 'INSERT `'. $table.'` SET ' . $fragment_sql;

        return $this->run($query, $bind_values);
    }

    /**
     * Execute any SQL query
     *
     * To ensure your query is safe from first order SQL injection attacks pass all values via the $bind array
     *
     * @param  string $query MySQL query
     * @param  array $bind  key:value pairs where the key is a bind identifier and value is to be inserted at that location
     * @return mixed see example
     * @example depending on the type of input query the returned result can be an affected row count or a result set, the type of which is specified in the options passed to the constructor, defaulting to an assoc array
     * @example $query = 'SELECT * FROM table_name WHERE col_id = :BindColID'; $bind = [':BindColID' => 12];
     */
    public function run ($query, $bind=[]) {
        try {
            $this->handle = $this->prepare($query);
            $this->handle->execute($bind);

            // check what the query begins with
            if (preg_match('/^(select|describe|pragma)/i', $query)) {
                // return a result set
                return $this->handle->fetchAll();
            }

            if (preg_match('/^(delete|insert|update)/i', $query)) {
                // return the affected row count
                return $this->rowCount();
            }

            // default to simply indicating success
            return true;
        } catch (\PDOException $e) {
            $this->error_str = $e->getMessage();
            return false;
        }
    }

    /**
     * Fetches the Row ID for the last inserted record
     *
     * @return int Returns the integer ID of the last inserted row
     */
    public function getLastInsertID() {
        if ($a=$this->run('SELECT LAST_INSERT_ID() as ID')) {
            return $a[0]['ID'];
        } else {
            return false;
        }
    }

    /**
     * Fetch the number of rows returned from previous query
     *
     * @return int affected row count of last query
     */
    public function rowCount() {
        return $this->handle->rowCount();
    }

    /**
     * Fetch last error message
     *
     * @return string last caught message from a PDO exception
     */
    public function getLastError() {
        return $this->error_str;
    }

    /**
     * Make key:value pairs SQL safe
     *
     * Separates out the indexes of the data to transmit to the DB (the column names) to return a safe raw SQL query fragment
     *
     * @param  string $data key:value pairs of column:data structure
     * @param  string $glue to be put in-between each column:data pair in the result
     * @return string final SQL fragment, injection safe as long as the keys in $data are safe
     */
    private function prepareBinding(array $data, string $glue): string
    {
        $binding = '';
        foreach (array_keys($data) as $column_name) {
            $binding .= '`' . $column_name . '` = ? '.$glue;
        }

        return rtrim($binding, $glue);
    }

    /*
    public function beginTransaction() {
        return $this->beginTransaction();
    }

    public function rollBack() {
        return $this->rollBack();
    }
    */
}