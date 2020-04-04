<?php

IncludeModuleLangFile(__FILE__);

class CCrmGridOptions extends CGridOptions
{
	function __construct($grid_id, array $filterPresets = array())
	{
		parent::__construct($grid_id, $filterPresets);
	}

	static public function GetDefaultGrigID($entityTypeID)
	{
		$entityTypeID = (int)$entityTypeID;
		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			return '';
		}

		if($entityTypeID === CCrmOwnerType::Lead)
		{
			return 'CRM_LEAD_LIST_V12';
		}
		elseif($entityTypeID === CCrmOwnerType::Deal)
		{
			return 'CRM_DEAL_LIST_V12';
		}
		elseif($entityTypeID === CCrmOwnerType::Contact)
		{
			return 'CRM_CONTACT_LIST_V12';
		}
		elseif($entityTypeID === CCrmOwnerType::Company)
		{
			return 'CRM_COMPANY_LIST_V12';
		}

		return '';
	}

	static public function AddVisibleColumn($gridID, $columnName)
	{
		if($gridID == '' || $columnName == '')
		{
			return false;
		}

		$item = new CCrmGridOptions($gridID);
		$columns = $item->GetVisibleColumns();
		if(empty($columns) || in_array($columnName, $columns, true))
		{
			return false;
		}

		$columns[] = $columnName;
		$item->SetVisibleColumns($columns);
		return true;
	}

	static public function RemoveVisibleColumn($gridID, $columnName)
	{
		if($gridID == '' || $columnName == '')
		{
			return false;
		}

		$item = new CCrmGridOptions($gridID);
		$columns = $item->GetVisibleColumns();
		if(empty($columns))
		{
			return false;
		}

		$index = array_search($columnName, $columns, true);
		if($index === false)
		{
			return false;
		}

		array_splice($columns, $index, 1);
		$item->SetVisibleColumns($columns);
		return true;
	}

	public function SetVisibleColumns($arColumns)
	{
		$this->options['columns'] = implode(',', $arColumns);
		$aOptions = CUserOptions::GetOption('main.interface.grid', $this->grid_id, array());
		if (!is_array($aOptions['views']))
			$aOptions['views'] = array();
		if (!is_array($aOptions['filters']))
			$aOptions['filters'] = array();
		if (!array_key_exists('default', $aOptions['views']))
			$aOptions['views']['default'] = array('columns'=>'');
		if ($aOptions['current_view'] == '' || !array_key_exists($aOptions['current_view'], $aOptions['views']))
			$aOptions['current_view'] = 'default';

		$aOptions['views'][$aOptions['current_view']]['columns'] = $this->options['columns'];
		CUserOptions::SetOption('main.interface.grid', $this->grid_id, $aOptions);
	}

	static public function SetTabNames($form_id, $tabs)
	{
		$aOptions = CUserOptions::GetOption('main.interface.form', $form_id, array());
		if(!is_array($aOptions['tabs']))
			$aOptions['tabs'] = array();

		foreach ($tabs as $tab) 
		{
			reset($aOptions['tabs']);
			foreach ($aOptions['tabs'] as $k => $aOpTab)
			{
				if ($tab['id'] == $aOpTab['id'])
				{
					$aOptions['tabs'][$k]['name'] = $tab['name'];
					break ;
				}
			}
		}

		CUserOptions::SetOption('main.interface.form', $form_id, $aOptions);
	}
}

class CCrmGridContext
{
	private static $ITEMS = array();
	public static function Set($id, $data)
	{
		self::$ITEMS[$id] = $data;
	}
	public static function Get($id)
	{
		return isset(self::$ITEMS[$id]) ? self::$ITEMS[$id] : array();
	}
	public static function Parse(&$values)
	{
		return array(
			'FILTER_INFO' =>
				array(
					'ID' => isset($values['GRID_FILTER_ID']) ? $values['GRID_FILTER_ID'] : '',
					'IS_APPLIED' => isset($values['GRID_FILTER_APPLIED']) ? $values['GRID_FILTER_APPLIED'] : false
				)
		);
	}
	public static function GetEmpty()
	{
		return array('FILTER_INFO' => array('ID' => '', 'IS_APPLIED' => false));
	}
}