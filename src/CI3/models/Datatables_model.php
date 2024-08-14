<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Datatables_model extends CI_Model
{
    /**
     * @var array
     */
    private static array $params = [];
    private static ?self $instance;
    public static $db;

    public function __construct()
    {
        self::$db = &$this->db;
    }


    /**
     * Initialize function
     *
     * @param array $params
     * @param string[] $params ['columns'] Array, each item must have "name" and optional "as" keys.
     * @param string $params ['from'] Table name.
     * @param array[] $params ['join'] Each item must have "table", "condition" and "type" keys.
     * @param array[] $params ['where'] Each item must have key and value.
     * @param string|string[] $params ['group_by']
     *
     * @return Datatables_model
     */
    public static function queryBuilder(array $params = []): Datatables_model
    {
        self::$params = $params;
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    /**
     * @param $key
     * @return mixed|null
     */
    public function get($key)
    {
        return (isset(self::$params[$key])) ? self::$params[$key] : null;
    }


    public function list($callback = null)
    {
        self::$db->start_cache();
        if ($callback === null) {
            self::get('from') && $this->db->from(self::get('from'));
            self::get('join') && $this->bindJoins();
            self::get('where') && $this->db->where(self::get('where'));
            self::get('group_by') && $this->db->group_by(self::get('group_by'));

            if (self::get('columns')) {
                Datatables::setColumns(self::get('columns'));
            }
        } else {
            $callback($this);
        }

        self::bindSelect();
        self::$db->stop_cache();
        self::execute();
    }


    private function bindJoins()
    {
        if (isset(self::$params['join']) && count(self::$params['join']) > 0) {
            foreach (self::$params['join'] as $join) {
                $this->db->join($join['table'] ?? '', $join['condition'] ?? '', $join['type'] ?? '');
            }
        }
    }

    /**
     * @return void
     */
    private static function all()
    {
        self::search();
        foreach (Datatables::getOrders() as $column => $type) {
            self::$db->order_by($column, $type);
        }

        $limit = Datatables::limit();
        self::$db->limit($limit['length'], $limit['start']);
    }

    /**
     * @return void
     */
    private static function search(): void
    {
        Datatables::filter();
        if (Datatables::getColumnSearch() && Datatables::getGlobalSearch()) {
            foreach (Datatables::getColumnSearch() as $column => $data) {
                $escapedValue = self::$db->escape($data["value"]);
                if ((bool)$data["regex"] === true) {
                    self::$db->where("$column REGEXP ", urldecode($escapedValue), FALSE);
                } else {
                    self::$db->where($column, $escapedValue);
                }
            }
            self::$db->group_start();
            foreach (Datatables::getGlobalSearch() as $column => $data) {
                $escapedValue = self::$db->escape_like_str($data);
                self::$db->or_like($column, $escapedValue, 'both', FALSE);
            }
            self::$db->group_end();
        } else {
            foreach (Datatables::getColumnSearch() as $column => $data) {
                $escapedValue = self::$db->escape($data["value"]);
                if ((bool)$data["regex"] === true) {
                    self::$db->where("$column REGEXP ", urldecode($escapedValue), FALSE);
                } else {
                    self::$db->where($column, $escapedValue);
                }
            }
            self::$db->group_start();
            foreach (Datatables::getGlobalSearch() as $column => $data) {
                $escapedValue = self::$db->escape_like_str($data);
                self::$db->or_like($column, $escapedValue, 'both', FALSE);
            }
            self::$db->group_end();
        }
    }


    private static function bindSelect()
    {
        if (empty(Datatables::getColumns())) {
            $data = self::$db->get()->row_array();
            if (empty($data)) return $data;
            Datatables::setColumnsfromRowData($data);
        } else {
            self::$db->select(self::getColumnsStr());
        }
    }


    /**
     * @return string
     */
    private static function getColumnsStr(): string
    {
        $editedColumn = array_map(function ($column) {
            if (!empty($column["column"]) && key_exists('as', $column) && $column['as'] !== '') {
                $column["column"] = "{$column['column']} as {$column['as']}";
                return $column;
            }
            return $column;
        }, Datatables::getColumns());
        return implode(',', Datatables::pluck($editedColumn, 'column'));
    }


    static function execute()
    {
        Datatables::setRecordsTotal(self::$db->count_all_results());
        self::search();
        Datatables::setRecordsFiltered(self::$db->count_all_results());
        self::all();
        $records = self::$db->get()->result_array();
        self::$db->flush_cache();
        Datatables::setRecords($records);
    }


}


