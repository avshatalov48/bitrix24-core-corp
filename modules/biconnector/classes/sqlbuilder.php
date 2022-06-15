<?php

class CBIConnectorSqlBuilder extends CSQLWhere
{
	protected $select;

	public function setSelect($selectedFields, $options = [])
	{
		global $DB;

		$this->select = [];
		foreach ($selectedFields as $fieldName => $fieldInfo)
		{
			if ($fieldInfo['FIELD_TYPE'] === 'datetime' && isset($options['datetime_format']))
			{
				$this->select[] = 'date_format(' . $fieldInfo['FIELD_NAME'] . ',\'' . $DB->forSql($options['datetime_format']) . '\') AS ' . $fieldName;
			}
			elseif ($fieldInfo['FIELD_TYPE'] === 'date' && isset($options['date_format']))
			{
				$this->select[] = 'date_format(' . $fieldInfo['FIELD_NAME'] . ',\'' . $DB->forSql($options['date_format']) . '\') AS ' . $fieldName;
			}
			else
			{
				$this->select[] = $fieldInfo['FIELD_NAME'] . ' AS ' . $fieldName;
			}

			if (!$this->c_joins[$fieldName])
			{
				$this->c_joins[$fieldName]++;
				$this->l_joins[$fieldInfo['TABLE_ALIAS']]++;
			}
		}
	}

	public function getSelect()
	{
		return implode("\n  ,", $this->select);
	}

	public function getJoins()
	{
		$result = array();

		foreach ($this->c_joins as $key => $counter)
		{
			if ($counter > 0)
			{
				$TABLE_ALIAS = $this->fields[$key]['TABLE_ALIAS'];
				if ($this->l_joins[$TABLE_ALIAS])
				{
					if (isset($this->fields[$key]['LEFT_JOIN']))
					{
						$result[$TABLE_ALIAS] = $this->fields[$key]['LEFT_JOIN'];
					}
				}
				else
				{
					if (isset($this->fields[$key]['JOIN']))
					{
						$result[$TABLE_ALIAS] = $this->fields[$key]['JOIN'];
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
