<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

//Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksWidgetButtonsComponent extends TasksBaseComponent
{
	protected function checkParameters()
	{
		static::tryParseArrayParameter($this->arParams['SCHEME'], []);
		static::tryParseBooleanParameter($this->arParams['IS_SCRUM_TASK']);

		$this->arResult['TASK_LIMIT_EXCEEDED'] = static::tryParseBooleanParameter(
			$this->arParams['TASK_LIMIT_EXCEEDED']
		);
		$this->arResult['TEMPLATE_SUBTASK_LIMIT_EXCEEDED'] = static::tryParseBooleanParameter(
			$this->arParams['TEMPLATE_SUBTASK_LIMIT_EXCEEDED']
		);

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
				if(!($metGroups[$button['GROUP']] ?? null))
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