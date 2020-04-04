<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

//Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksWidgetButtonsComponent extends TasksBaseComponent
{
	protected function checkParameters()
	{
		$this->tryParseArrayParameter($this->arParams['SCHEME'], array());

		$buttons = array();
		$groups = array();
		$metGroups = array();
		foreach($this->arParams['SCHEME'] as $button)
		{
			if(!array_key_exists('ACTIVE', $button))
			{
				$button['ACTIVE'] = true;
			}
			if(!array_key_exists('TYPE', $button))
			{
				$button['TYPE'] = 'button';
			}
			else
			{
				if($button['TYPE'] == 'link' && !array_key_exists('URL', $button))
				{
					$button['URL'] = 'javascript:void();';
				}
			}

			if(array_key_exists('GROUP', $button) && $button['GROUP'] != '')
			{
				if(!$metGroups[$button['GROUP']])
				{
					// add new group button at the place of the first occurrence
					$buttons[] = array(
						'CODE' => 'GROUP_'.$button['GROUP'],
						'TYPE' => 'group',
						'TITLE' => $button['GROUP'] == 'MORE' ? Loc::getMessage('TASKS_COMMON_MORE') : '???',
						'ACTIVE' => true,
					);

					$metGroups[$button['GROUP']] = true;
				}

				$groups[$button['GROUP']][] = $button;
			}
			else
			{
				$buttons[] = $button;
			}
		}

		$this->arResult['BUTTONS'] = $buttons;
		$this->arResult['GROUPS'] = $groups;

		return $this->errors->checkNoFatals();
	}
}