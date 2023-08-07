<?php

class CPerfomanceSchema
{
	public $data_relations = null;
	public $data_actions = null;
	public $data_attributes = null;

	function addModuleSchema(array $arModuleSchema)
	{
		foreach ($arModuleSchema as $module_id => $arModuleTables)
		{
			if (!array_key_exists($module_id, $this->data_relations))
			{
				$this->data_relations[$module_id] = array();
			}

			foreach ($arModuleTables as $parent_table_name => $arParentColumns)
			{
				if (!array_key_exists($parent_table_name, $this->data_relations[$module_id]))
				{
					$this->data_relations[$module_id][$parent_table_name] = array();
				}

				foreach ($arParentColumns as $parent_column => $arChildren)
				{
					if ($parent_column === '~actions')
					{
						if (!array_key_exists($module_id, $this->data_actions))
						{
							$this->data_actions[$module_id] = array();
						}
						if (!array_key_exists($parent_table_name, $this->data_actions[$module_id]))
						{
							$this->data_actions[$module_id][$parent_table_name] = array();
						}
						$this->data_actions[$module_id][$parent_table_name] = array_merge(
							$this->data_actions[$module_id][$parent_table_name],
							$arChildren
						);
					}
					else
					{
						if (!array_key_exists($parent_column, $this->data_relations[$module_id][$parent_table_name]))
						{
							$this->data_relations[$module_id][$parent_table_name][$parent_column] = array();
						}

						foreach ($arChildren as $child_table_name => $child_column)
						{
							if (preg_match("#^~(.+)$#", $child_table_name, $m))
							{
								$this->data_attributes[$module_id][$parent_table_name][$parent_column][$m[1]] = $child_column;
							}
							else
							{
								$this->data_relations[$module_id][$parent_table_name][$parent_column][$child_table_name] = $child_column;
							}
						}
					}
				}
			}
		}
	}

	function Init()
	{
		if (!isset($this->data_relations))
		{
			$this->data_relations = array();
			$this->data_actions = array();
			$this->data_attributes = array();
			foreach (GetModuleEvents("perfmon", "OnGetTableSchema", true) as $arEvent)
			{
				$arModuleSchema = ExecuteModuleEventEx($arEvent);
				if (is_array($arModuleSchema))
				{
						$this->addModuleSchema($arModuleSchema);
				}
			}
		}
	}

	function GetAttributes($table_name)
	{
		$this->Init();
		foreach ($this->data_attributes as $module_id => $arModuleTables)
		{
			if (isset($arModuleTables[$table_name]))
			{
				return $arModuleTables[$table_name];
			}
		}
		return array();
	}

	function GetRowActions($table_name)
	{
		$this->Init();
		foreach ($this->data_actions as $module_id => $arModuleTables)
		{
			if (isset($arModuleTables[$table_name]))
			{
				return $arModuleTables[$table_name];
			}
		}
		return array();
	}

	function GetChildren($table_name)
	{
		$this->Init();
		$result = array();
		foreach ($this->data_relations as $module_id => $arModuleTables)
		{
			if (array_key_exists($table_name, $arModuleTables))
				$key = $table_name;
			elseif (array_key_exists(mb_strtolower($table_name), $arModuleTables))
				$key = mb_strtolower($table_name);
			elseif (array_key_exists(mb_strtoupper($table_name), $arModuleTables))
				$key = mb_strtoupper($table_name);
			else
				$key = '';

			if ($key)
			{
				foreach ($arModuleTables[$key] as $parent_column => $arChildren)
				{
					foreach ($arChildren as $child_table_name => $child_column)
						$result[] = array(
							"PARENT_COLUMN" => $parent_column,
							"CHILD_TABLE" => trim($child_table_name, "^"),
							"CHILD_COLUMN" => $child_column,
						);
				}
			}
		}

		uasort($result, array("CPerfomanceSchema", "_sort"));
		return $result;
	}

	function GetParents($table_name)
	{
		$this->Init();
		$result = array();
		foreach ($this->data_relations as $module_id => $arModuleTables)
		{
			foreach ($arModuleTables as $parent_table_name => $arParentColumns)
			{
				foreach ($arParentColumns as $parent_column => $arChildren)
				{
					foreach ($arChildren as $child_table_name => $child_column)
					{
						$child_table_name = trim($child_table_name, "^");
						if (
							$child_table_name === $table_name
							|| $child_table_name === mb_strtolower($table_name)
							|| $child_table_name === mb_strtoupper($table_name)
						)
							$result[$child_column] = array(
								"PARENT_TABLE" => $parent_table_name,
								"PARENT_COLUMN" => $parent_column,
							);
					}
				}
			}
		}

		uasort($result, array("CPerfomanceSchema", "_sort"));
		return $result;
	}

	private function _sort($a, $b)
	{
		if (isset($a["CHILD_TABLE"]))
			return strcmp($a["CHILD_TABLE"], $b["CHILD_TABLE"]);
		else
			return strcmp($a["PARENT_TABLE"], $b["PARENT_TABLE"]);
	}
}
