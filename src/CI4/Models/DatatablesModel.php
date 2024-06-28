<?php
namespace App\Models;


use App\Libraries\Datatables\Datatables;
use App\Libraries\Datatables\MyBaseBuilder;
use CodeIgniter\Model;

class DatatablesModel extends Model
{
	/**
	 * @var array
	 */
	private static array $params = [];
	private static ?self $instance;

    /**
     * Query Builder object
     *
     * @var MyBaseBuilder|null
     */
    public $builder;


	public function __construct()
	{
	 parent::__construct();
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
	 * @return DatatablesModel
	 */
	public static function queryBuilder(array $params = []): DatatablesModel
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
	public function getParam($key)
	{
		return (isset(self::$params[$key])) ? self::$params[$key] : null;
	}


	public function list($callback = null)
	{

        if ($callback === null) {
            self::getParam('from') && $this->builder = $this->db->table(self::getParam('from'));
            self::getParam('join') && $this->bindJoins();
            self::getParam('where') && $this->builder->where(self::getParam('where'));
            self::getParam('group_by') && $this->builder->groupBy(self::getParam('group_by'));
            if (self::getParam('columns')) {
                Datatables::setColumns(self::getParam('columns'));
            }
            $this->builder = new MyBaseBuilder($this->builder);
        } else {
            $this->builder = new MyBaseBuilder($callback($this));
            Datatables::setColumns($this->builder->getSelect());
        }

        self::setColumnsFromRowDataIfEmpty();
        self::bindSelectIfEmpty();
		self::execute();
	}


	private function bindJoins()
	{
		if (isset(self::$params['join']) && count(self::$params['join']) > 0) {
			foreach (self::$params['join'] as $join) {
                $this->builder->join($join['table'] ?? '', $join['condition'] ?? '', $join['type'] ?? '');
			}
		}
	}

	/**
	 * @return void
	 */
	private  function all()
	{
		self::search();
		foreach (Datatables::getOrders() as $column => $type) {
            $this->builder->orderBy($column, $type);
		}

		$limit = Datatables::limit();
        $this->builder->limit($limit['length'], $limit['start']);
	}

	/**
	 * @return void
	 */
	private  function search(): void
	{
		Datatables::filter();
		if(Datatables::getColumnSearch() && Datatables::getGlobalSearch()) {
			foreach (Datatables::getColumnSearch() as $column => $data) {
                $escapedValue = $this->db->escape($data["value"]);
                if((bool)$data["regex"] === true){
                    $this->builder->where("$column REGEXP ", urldecode($escapedValue));
                }else{
                    $this->builder->where($column, $escapedValue);
                }
			}
			$this->groupStart();
			foreach (Datatables::getGlobalSearch() as $column => $data) {
                $escapedValue =  $this->db->escapeLikeString($data);
                $this->builder->orLike($column, "$escapedValue",'both');
			}
			$this->groupEnd();
		} else {
			foreach (Datatables::getColumnSearch() as $column => $data) {
                $escapedValue = $this->db->escape($data["value"]);
                if((bool)$data["regex"] === true){
                    $this->builder->where("$column REGEXP ", urldecode($escapedValue));
                }else{
                    $this->builder->where($column, $escapedValue);
                }
			}
			foreach (Datatables::getGlobalSearch() as $column => $data) {
                $escapedValue =  $this->db->escapeLikeString($data);
                $this->builder->orLike($column, "$escapedValue",'both');

			}
		}
	}


    private function setColumnsFromRowDataIfEmpty(){
        if (empty(Datatables::getColumns())){
            $data = $this->builder->get(1,0)->getRowArray();
            if (empty($data)) return false;
            Datatables::setColumnsfromRowData($data);
        }
    }

	private function bindSelectIfEmpty()
	{
        if(count($this->builder->getSelect()) === 0 && count(Datatables::getColumns())>0){
            $this->builder->select(self::getColumnsStr(),true);
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


	public function execute()
	{
		Datatables::setRecordsTotal($this->builder->countAllResults(false));

		self::search();
		Datatables::setRecordsFiltered($this->builder->countAllResults(false));

        self::all();
        $records = $this->builder->get()->getResultArray();

		Datatables::setRecords($records);
	}


}


