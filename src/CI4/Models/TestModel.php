<?php
namespace App\Models;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

class TestModel extends Model{

    public function queryBuilder():BaseBuilder{

        return $this->db
            ->table('table')
            ->select('id, name, (Select column_name from table where table.column_name=table.column_name LIMIT 1) as alias_name',
            )
            ->join('table','table.aciga_alim_column_name = table.column_name','left')
            ->groupBy('id');
    }

}