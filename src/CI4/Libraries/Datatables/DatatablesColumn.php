<?php
namespace App\Libraries\Datatables;

class DatatablesColumn
{
	private static ?self $instance;
	private static string $column = '';
	private static string $as = '';
	private static bool $custom = false;

	public function __construct()
	{
		// Private constructor
	}

	public static function column($column,$custom = false): DatatablesColumn
	{
		self::reset();
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}

		self::$column = $column;
		self::$custom = $custom;
		return self::$instance;
	}

	public function as(string $as): DatatablesColumn
	{
		self::$as = $as;

		return $this;
	}

	public function add()
	{
		$column = [];
		$column['column'] = self::$column;
		$column['as'] = self::$as;
		$column['custom'] = self::$custom;

		$currentColumns = Datatables::getColumns();
		$currentColumns[] = $column;
		Datatables::setColumns($currentColumns);
	}

	private static function reset()
	{
		self::$instance = null;
		self::$column = '';
		self::$as = '';
		self::$custom = '';

	}


	/**
     * You can formating data and use another column from data. Or you can create any column without from data column.
	 * @param null $callback
	 * @return DatatablesColumn
	 */
	public function formatter($callback = null): DatatablesColumn
	{
		if (is_callable($callback)) {
			foreach (Datatables::getRecords() as &$row) {
				$columnData = &$row[self::$column];
				if ($columnData) {
					$row[self::$column] = call_user_func($callback, $columnData, $row);
				} else {
					$key = (self::$as) ?: self::$column;
					$row[$key] = call_user_func($callback, null, $row);
					//$columnData = call_user_func($callback, null, $row);
				}
			}
		} else {
			foreach (Datatables::getRecords() as &$row) {
					$key = (self::$as) ?: self::$column;
					$row[$key] = $callback;
			}
		}
		return $this;
	}

}
