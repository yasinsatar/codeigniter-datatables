<?php

namespace App\Libraries\Datatables;

use CodeIgniter\Database\BaseBuilder;

class MyBaseBuilder extends BaseBuilder
{

    public function __construct(BaseBuilder $builder)
    {
        parent::__construct($builder->getTable(), $builder->db(),get_object_vars($builder));
    }

    public function getSelect(): array
    {
        $editedColumns = [];
        foreach ($this->QBSelect as &$field) {
            $parsedField = $this->parseSubQuery($field);
            if ($parsedField) {
                $editedColumns[] = ["column"=>$parsedField['subquery'], "as" => $parsedField['alias']];
            }else{
                $editedColumns[] = ["column" => str_replace('`','',$field)];
            }
        }

        return $editedColumns;
    }

    /**
     * Parse a field to extract subquery and alias
     *
     * @param string $field
     * @return array|null
     */
    protected function parseSubQuery($field): ?array
    {
        $pattern = '/\(([^()]*)\)\s+as\s+(\w+)/i';

        if (preg_match($pattern, $field, $matches)) {
            return [
                'subquery' => '(' . $matches[1] . ')',
                'alias' => $matches[2]
            ];
        }
        return null;
    }

}