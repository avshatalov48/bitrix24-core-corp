<?php

class CBIConnectorSqlBuilder extends CSQLWhere
{
	protected $select;
	protected array $selectFieldNames = [];

	public function SetSelect($selectedFields, $options = [])
	{
		global $DB;

		$this->select = [];
		foreach ($selectedFields as $fieldName => $fieldInfo)
		{
			$this->selectFieldNames[] = $fieldName;
			if ($fieldInfo['FIELD_TYPE'] === 'datetime' && isset($options['datetime_format']))
			{
				$this->select[] = sprintf(
					'date_format(%s, \'%s\') AS `%s`',
					$fieldInfo['FIELD_NAME'],
					$DB->forSql($options['datetime_format']),
					$fieldName
				);
			}
			elseif ($fieldInfo['FIELD_TYPE'] === 'date' && isset($options['date_format']))
			{
				$this->select[] = sprintf(
					'date_format(%s, \'%s\') AS `%s`',
					$fieldInfo['FIELD_NAME'],
					$DB->forSql($options['date_format']),
					$fieldName
				);
			}
			else
			{
				$this->select[] = sprintf(
					'%s AS `%s`',
					$fieldInfo['FIELD_NAME'],
					$fieldName
				);
			}

			if (!isset($this->c_joins[$fieldName]))
			{
				$this->c_joins[$fieldName] = 1;
			}
			else
			{
				$this->c_joins[$fieldName]++;
			}

			if (isset($fieldInfo['TABLE_ALIAS']))
			{
				if (!isset($this->l_joins[$fieldInfo['TABLE_ALIAS']]))
				{
					$this->l_joins[$fieldInfo['TABLE_ALIAS']] = 1;
				}
				else
				{
					$this->l_joins[$fieldInfo['TABLE_ALIAS']]++;
				}
			}
		}
	}

	public function GetSelect()
	{
		return implode("\n  ,", $this->select);
	}

	public function GetSelectFieldNames()
	{
		return $this->selectFieldNames;
	}

	public function GetJoins()
	{
		$result = [];

		foreach ($this->c_joins as $fieldName => $counter)
		{
			if ($counter > 0)
			{
				$TABLE_ALIAS = $this->fields[$fieldName]['TABLE_ALIAS'];
				if (isset($this->l_joins[$TABLE_ALIAS]) && $this->l_joins[$TABLE_ALIAS])
				{
					$resultJoin = $this->fields[$fieldName]['LEFT_JOIN'] ?? false;
				}
				else
				{
					$resultJoin = $this->fields[$fieldName]['JOIN'] ?? false;
				}

				if ($resultJoin)
				{
					if (is_array($resultJoin))
					{
						foreach ($resultJoin as $join)
						{
							$result[$join] = $join;
						}
					}
					else
					{
						$result[$resultJoin] = $resultJoin;
					}
				}
			}
		}

		if ($result)
		{
			return implode("\n  ", $result);
		}
		else
		{
			return '';
		}
	}
}
