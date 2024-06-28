<?php

class Test_model extends CI_Model
{

    /**
     * You have to return builder not data.
     * @return mixed
     */
	public function queryBuilder(){
		return $this->db
			->select(
			array(
				'id',
				'name',
				'(Select column_name from table where column_name=column_name LIMIT 1) as alias_name'
			)
		)
			->from('table')
			->join('table','table.column_name = table.column_name','left')
			->group_by('id');
	}



}
