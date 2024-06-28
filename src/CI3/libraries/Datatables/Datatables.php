<?php

class Datatables
{
	private CI_Controller $CI ;
	private static array $columns = [];
	private static array $request = [];
	private static array $records = [];
	private static int $recordsTotal = 0;
	private static int $recordsFiltered = 0;
	private static array $globalSearch = [];
	private static array $columnSearch = [];

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->library('Datatables/DatatablesColumn');
		$this->CI->load->model('Datatables_model');
	}

	/**
	 * @return array
	 */
	public static function &getColumns(): array
	{
		return self::$columns;
	}

	public static function setColumns(array $columns)
	{
		foreach ($columns as &$column){
			$columnSplit = explode(".", $column['column']);
			$columnName = end($columnSplit);
			$columnData = [
				'column' => $column['column'],
				'as' => key_exists('as', $column) ? $column['as'] : $columnName,
			];
			$column = $columnData;
		}
		self::$columns = $columns;
	}

	public static function setColumnsfromRowData($data)
	{
		$columns = [];
		$columnsName = array_keys($data);
		foreach ($columnsName as $columnName) {
			$columns[] = ["column" => $columnName, "as" => $columnName];
		}
		self::$columns = $columns;
	}

	/**
	 * @return array
	 */
	public static function getRequest(): array
	{
		return self::$request;
	}

	/**
	 * @param array $request
	 * @return void
	 */
	public static function setRequest(array $request)
	{
		self::$request = $request;
	}

	/**
	 * @return array
	 */
	public static function &getRecords(): array
	{
		return self::$records;
	}

	/**
	 * @param array $records
	 */
	public static function setRecords(array $records): void
	{
		self::$records = $records;
	}

	/**
	 * @return int
	 */
	public static function getRecordsTotal(): int
	{
		return self::$recordsTotal;
	}

	/**
	 * @param int $recordsTotal
	 */
	public static function setRecordsTotal(int $recordsTotal): void
	{
		self::$recordsTotal = $recordsTotal;
	}

	/**
	 * @return int
	 */
	public static function getRecordsFiltered(): int
	{
		return self::$recordsFiltered;
	}

	/**
	 * @param int $recordsFiltered
	 */
	public static function setRecordsFiltered(int $recordsFiltered): void
	{
		self::$recordsFiltered = $recordsFiltered;
	}

	/**
	 * @return array
	 */
	public static function getGlobalSearch(): array
	{
		return self::$globalSearch;
	}

	/**
	 * @param array $globalSearch
	 */
	public static function setGlobalSearch(array $globalSearch): void
	{
		self::$globalSearch = $globalSearch;
	}

	/**
	 * @return array
	 */
	public static function getColumnSearch(): array
	{
		return self::$columnSearch;
	}

	/**
	 * @param array $columnSearch
	 */
	public static function setColumnSearch(array $columnSearch): void
	{
		self::$columnSearch = $columnSearch;
	}


	public static function filter()
	{
		$globalSearch = array();
		$columnSearch = array();
		$asColumns = self::pluck(self::getColumns(), 'as');

		if(!self::getColumns()) return false;

		if (isset(self::getRequest()['search']) && self::getRequest()['search']['value'] != '') {
			$str = self::getRequest()['search']['value'];

			for ($i = 0, $ien = count(self::getRequest()['columns']); $i < $ien; $i++) {
				$requestColumn = self::getRequest()['columns'][$i];

				$columnIdx = array_search($requestColumn['data'], $asColumns);
				$column = self::getColumns()[$columnIdx];
				if ((bool)$requestColumn['searchable'] === true && $str != '') {
					if (!empty($column['column'])) {
						$globalSearch[$column['column']] = $str;
					}
				}
			}
		}

		if (isset(self::getRequest()['columns'])) {
			for ($i = 0, $ien = count(self::getRequest()['columns']); $i < $ien; $i++) {
				$requestColumn = self::getRequest()['columns'][$i];

				$columnIdx = array_search($requestColumn['data'], $asColumns);
				$column = self::getColumns()[$columnIdx];

				$str = $requestColumn['search']['value'];

				if ((bool)$requestColumn['searchable'] === true && $str != '') {
					if (!empty($column['column'])) {
						$columnSearch[$column['column']] = $str;
					}
				}
			}
		}

		if ($globalSearch) {
			self::setGlobalSearch($globalSearch);
		}

		if ($columnSearch) {
			self::setColumnSearch($columnSearch);
		}

	}

	public static function getOrders(): array
	{
		$order = [];

		if (self::getColumns() && isset(self::getRequest()['order']) && count(self::getRequest()['order'])) {
			$asColumns = self::pluck(self::getColumns(), 'as');

			for ($i = 0, $ien = count(self::getRequest()['order']); $i < $ien; $i++) {
				// Convert the column index into the column data property
				$columnIdx = intval(self::getRequest()['order'][$i]['column']);
				$requestColumn = self::getRequest()['columns'][$columnIdx];

				$columnIdx = array_search($requestColumn['data'], $asColumns);
				$column = self::getColumns()[$columnIdx];

				if ((bool)$requestColumn['orderable'] === true) {
					$dir = self::getRequest()['order'][$i]['dir'] === 'asc' ?
						'ASC' :
						'DESC';

					$order[$column['column']] = $dir;
				}
			}
		}

		return $order;
	}

	/**
	 * @return array
	 */
	public static function limit(): array
	{
		$limit = ['start' => 0, 'length' => 0];

		if (isset(self::getRequest()['start']) && self::getRequest()['length'] != -1) {
			$limit['start'] = intval(self::getRequest()['start']);
			$limit['length'] = intval(self::getRequest()['length']);
		}

		return $limit;
	}

	/**
	 * @param $a
	 * @param $prop
	 * @return array
	 */
	public static function pluck($a, $prop)
	{
		$out = array();

		for ($i = 0, $len = count($a); $i < $len; $i++) {
			if (empty($a[$i][$prop])) {
				continue;
			}
			$out[$i] = $a[$i][$prop];
		}

		return $out;
	}



	/**
	 * @return string
	 */
	public static function getSearchValue(): string
	{
		$value = "";
		if (isset(self::getRequest()['search']) && self::getRequest()['search']['value'] != '') {
			$value = self::getRequest()['search']['value'];
		}
		return $value;
	}

	/**
	 * @param $data
	 * @return array
	 */
	private static function data_output(): array
	{
		$records = Datatables::getRecords();
		$out = array();
		for ($i = 0, $ien = count($records); $i < $ien; $i++) {
			$row = array();

			for ($j = 0, $jen = count(Datatables::getColumns()); $j < $jen; $j++) {
				$column = Datatables::getColumns()[$j];

				$columnName = $column['as'] ?: $column['column'];
				$row[$columnName] = $records[$i][$columnName];

				/*if (!empty($column['column']) && preg_match('/^\(.+\)$/u', $columnName)) {
					$row[$column['as']] = $records[$i][$columnName];
				}
				if (key_exists('as',$column) && key_exists($column['as'], $records[$i]) && $columnName !== $column['as']) {
					$row[$column['as']] = $records[$i][$column['as']];
				}
				if (!empty($column['column']) && key_exists('custom', $column)) {
					$row[$column['column']] = $records[$i][$columnName];
				}*/

			}
			$out[] = $row;
		}
		return $out;
	}

	/**
	 * @return array
	 */
	static function prepareOutput(): array
	{
		return array(
			"draw" => isset (self::getRequest()['draw']) ?
				intval(self::getRequest()['draw']) :
				0,
			"recordsTotal" => self::getRecordsTotal(),
			"recordsFiltered" => self::getRecordsFiltered(),
			"data" => self::data_output()
		);
	}



}
