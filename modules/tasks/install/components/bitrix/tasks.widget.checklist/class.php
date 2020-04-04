<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Item;

//use Bitrix\Main\Localization\Loc;
//
//Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksWidgetCheckListComponent extends TasksBaseComponent
{
	protected function checkParameters()
	{
		static::tryParseBooleanParameter($this->arParams['CAN_ADD'], false);
		static::tryParseBooleanParameter($this->arParams['CAN_REORDER'], false);
		static::tryParseBooleanParameter($this->arParams['CONFIRM_DELETE'], false);

		if(!Type::isIterable($this->arParams['DATA']))
		{
			$this->arParams['DATA'] = array();
		}
		else
		{
			$parsedData = array();
			foreach($this->arParams['DATA'] as $item)
			{
				$newItem = array();
				if(Item::isA($item))
				{
					$newItem = $item->export();
					$newItem['ACTION'] = array(
						'UPDATE' => $item->canUpdate(),
						'DELETE' => $item->canDelete(),
						'TOGGLE' => $item->canToggle(),
					);
				}
				elseif(is_array($item))
				{
					$newItem = $item;

					if(!array_key_exists('ACTION', $newItem))
					{
						// set default rights
						$newItem['ACTION'] = array(
							'UPDATE' => true,
							'DELETE' => true,
							'TOGGLE' => true,
						);
					}
				}

				// we can meet several variants of 'checked' and 'sort'

				$checked = false;
				if(array_key_exists('CHECKED', $newItem))
				{
					$checked = !!$newItem['CHECKED'];
				}
				elseif(array_key_exists('IS_COMPLETE', $newItem))
				{
					$checked = $newItem['IS_COMPLETE'] == 'Y';
				}
				unset($newItem['IS_COMPLETE']);

				$newItem['CHECKED'] = $checked;

				$sort = 0;
				if(array_key_exists('SORT', $newItem))
				{
					$sort = (int) $newItem['SORT'];
				}
				elseif(array_key_exists('SORT_INDEX', $newItem))
				{
					$sort = (int) $newItem['SORT_INDEX'];
				}
				unset($newItem['SORT_INDEX']);

				$newItem['SORT'] = $sort;
				$newItem['TITLE'] = (string) $newItem['TITLE'];

				$parsedData[] = $newItem;
			}

			$this->arParams['DATA'] = $parsedData;
		}

		return $this->errors->checkNoFatals();
	}
}