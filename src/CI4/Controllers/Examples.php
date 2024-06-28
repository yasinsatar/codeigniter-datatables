<?php

namespace App\Controllers;


use App\Libraries\Datatables\Datatables;
use App\Libraries\Datatables\DatatablesColumn;
use App\Models\DatatablesModel;
use CodeIgniter\Database\Database;

class Examples extends BaseController
{


    public function basicQuery()
    {
        if ($this->request->isAJAX()) {

            Datatables::setRequest($this->request->getGetPost());
            //If you don't want to set columns data, column data is prepared from the data.
            //However, this is not recommended. Because, structure need to run one more SQL.
            $params = array(
                'columns' => [
                    ["column" => 'id'], //If you have join and same column name in different tables. You can use table_name.column_name
                    ["column" => 'name'],
                    ["column" => '(Select column_name from table where table.column_name=table2.column_name LIMIT 1)', 'as' => 'alias_name'],
                ],
                'from' => 'table_name',
                'join' => [
                    ['table' => 'table_name', 'condition' => 'table_name.column_name = table2.column_name', 'type' => 'left']
                ],
                'group_by' => 'column'
            );
            DatatablesModel::queryBuilder($params)->list();

            // If you set column an existing column, you can format the data. $data holds value of the existing column.
            // If you set column an unavailable column, you can create extra column, and you can only use $row.
            // You can reach other value of existing columns with $row.
            DatatablesColumn::column('columnName')->formatter(function ($data, $row) {
                return $row['id'];
            })->add();

            return $this->response->setJSON(Datatables::prepareOutput());
        }
    }

    public function customQuery()
    {
        if ($this->request->isAJAX()) {

            $TestModel = model('App\Models\TestModel');
            Datatables::setRequest($this->request->getGetPost());
            DatatablesModel::queryBuilder()->list(function () use ($TestModel) {
                return $TestModel->queryBuilder();
            });
            DatatablesColumn::column('columnName')->formatter(function ($data, $row) {
                return $row['id'];
            })->add();

            return $this->response->setJSON(Datatables::prepareOutput());
        }
    }

    public function customData()
    {
        if ($this->request->isAJAX()) {

            Datatables::setRequest($this->request->getGetPost());

            $arr = [
                ['id'=>1,'name'=> 'Rick Sanchez', 'statu' => true],
                ['id'=>2,'name'=> 'Morty Jr.', 'statu' => false],
                ['id'=>3,'name'=> 'Jerry Smith', 'statu' => true],
            ];
            Datatables::setRecords($arr);
            Datatables::setRecordsTotal(count($arr));
            Datatables::setColumns(array(
                ['column' => 'id'],
                ['column' => 'name'],
                ['column' => 'statu']
            ));
            //If you don't want to set columns data, you can use setColumnsfromRowData method.
            //Datatables::setColumnsfromRowData(reset($arr));


            foreach (Datatables::getOrders() as $column => $type) {
                if ($type === "DESC") {
                    rsort($arr);
                    Datatables::setRecords($arr);
                }
            }

            Datatables::filter();
            if ($searchValue = Datatables::getSearchValue()) {
                $arrFiltered = array_filter($arr, function ($item) use ($searchValue) {
                    foreach ($item as $itemValue) {
                        if (strpos(var_export($itemValue, true), $searchValue) !== false) {
                            return true;
                        }
                    }
                    return false;
                });
                Datatables::setRecords(array_values($arrFiltered));
            }
            Datatables::setRecordsFiltered(count(Datatables::getRecords()));

            DatatablesColumn::column('columnName')->formatter(function ($data, $row) {
                return $row['id'];
            })->add();

            return $this->response->setJSON(Datatables::prepareOutput());
        }
    }

}
