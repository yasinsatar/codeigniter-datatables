<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Examples extends CI_Controller
{

	public function basicQuery()
	{
		if ($this->input->is_ajax_request()) {
			$this->load->library('Datatables/Datatables');

			Datatables::setRequest($this->input->post());

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
			Datatables_model::queryBuilder($params)->list();

            // If you set column an existing column, you can format the data. $data holds value of the existing column.
            // If you set column an unavailable column, you can create extra column, and you can use $row.
            // You can reach other value of existing columns with $row.
			DatatablesColumn::column('columnName')->formatter(function($data,$row){
				return $row['id'];
			})->add();

			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode(Datatables::prepareOutput()));
		}
	}

    public function customQuery()
    {
        if ($this->input->is_ajax_request()) {
            $this->load->library('Datatables/Datatables');

            Datatables::setRequest($this->input->post());
            $this->load->model('Test_model');
            Datatables_model::queryBuilder()->list(function (){
                return $this->Test_model->queryBuilder();
            });

            DatatablesColumn::column('columnName')->formatter(function($data,$row){
                return $row['id'];
            })->add();

            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(Datatables::prepareOutput()));
        }
    }

    public function customData()
    {
        if ($this->input->is_ajax_request()) {
            $this->load->library('Datatables/Datatables');

            Datatables::setRequest($this->input->post());

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
                if($type=== "DESC"){
                    rsort($arr);
                    Datatables::setRecords($arr);
                }
            }

            Datatables::filter();
            if($searchValue = Datatables::getSearchValue()){
                $arrFiltered =  array_filter($arr, function ($item) use ($searchValue) {
                    foreach ($item as $itemValue) {
                        if (strpos(var_export($itemValue,true),$searchValue) !== false) {
                            return true;
                        }
                    }
                    return false;
                });
                Datatables::setRecords(array_values($arrFiltered));
            }
            Datatables::setRecordsFiltered(count(Datatables::getRecords()));

            DatatablesColumn::column('columnName')->formatter(function($data,$row){
                return $row['id'];
            })->add();

            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(Datatables::prepareOutput()));
        }
    }
}
