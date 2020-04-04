<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// make template`s checklist items sutable for bitrix:tasks.task.detail.parts to swallow
if(is_array($arResult['DATA']) && is_array($arResult['DATA']['CHECKLIST_ITEMS']))
{
	foreach($arResult['DATA']['CHECKLIST_ITEMS'] as &$item)
	{
		if(is_array($item))
		{
			$keys = array();
			foreach($item as $fld => $value)
			{
				$keys[] = $fld;
			}

			foreach($keys as $key)
			{
				if(!isset($item['~'.$key]))
					$item['~'.$key] = $item[$key];
			}

			if(!isset($item['ID']))
				$item['ID'] = 'task-detail-checklist-item-xxx_'.rand(0,999999); // newly created item, ID should be defined anyway
		}
	}
	unset($item);
}