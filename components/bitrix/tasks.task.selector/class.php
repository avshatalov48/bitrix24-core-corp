<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

/**
 * Class TasksTaskSelectorComponent
 */
class TasksTaskSelectorComponent extends TasksBaseComponent
{
	/**
	 * @return bool
	 */
	protected function checkParameters()
	{
		static::tryParseBooleanParameter($this->arParams['MULTIPLE']);
		static::tryParseBooleanParameter($this->arParams['HIDE_ADD_REMOVE_CONTROLS']);
		static::tryParseStringParameter($this->arParams['NAME']);
		static::tryParseArrayParameter($this->arParams['SELECT']);
		static::tryParseArrayParameter($this->arParams['FILTER']);
		static::tryParseArrayParameter($this->arParams['LAST_TASKS']);
		static::tryParseArrayParameter($this->arParams['CURRENT_TASKS']);

		$this->arParams['FORM_NAME'] = (
			preg_match('/^[a-zA-Z0-9_-]+$/', ($this->arParams['FORM_NAME'] ?? ''))
				? $this->arParams['FORM_NAME']
				: false
		);
		$this->arParams['INPUT_NAME'] = (
			preg_match('/^[a-zA-Z0-9_-]+$/', ($this->arParams['INPUT_NAME'] ?? ''))
				? $this->arParams['INPUT_NAME']
				: false
		);
		$this->arParams['SITE_ID'] = ($this->arParams['SITE_ID'] ?? SITE_ID);

		if (!is_array($this->arParams['VALUE']))
		{
			if ($this->arParams['VALUE'] == '')
			{
				$this->arParams['VALUE'] = [];
			}
			else
			{
				$this->arParams['VALUE'] = explode(',', $this->arParams['VALUE']);
			}
		}

		foreach ($this->arParams['VALUE'] as $key => $id)
		{
			$this->arParams['VALUE'][$key] = intval(trim($id));
		}
		$this->arParams['VALUE'] = array_unique($this->arParams['VALUE']);

		$this->arResult["NAME"] = $this->arParams["NAME"];

		return parent::checkParameters();
	}

	/**
	 * @throws TasksException
	 */
	protected function getData()
	{
		$this->arResult['LAST_TASKS'] = $this->arParams['LAST_TASKS'];
		$this->arResult['CURRENT_TASKS'] = $this->arParams['CURRENT_TASKS'];

		if (empty($this->arResult['LAST_TASKS']))
		{
			$this->arResult['LAST_TASKS'] = $this->getLastTasks();
		}

		if (empty($this->arResult['CURRENT_TASKS']) && sizeof($this->arParams['VALUE']))
		{
			$this->arResult['CURRENT_TASKS'] = $this->getCurrentTasks();
		}
	}

	/**
	 * @return array
	 * @throws TasksException
	 */
	public function getLastTasks()
	{
		$lastTasks = [];

		$order = ['STATUS' => 'ASC', 'DEADLINE' => 'DESC', 'PRIORITY' => 'DESC', 'ID' => 'DESC'];
		$filter = array_merge(
			[
				'DOER' => User::getId(),
				'STATUS' => [
					CTasks::METASTATE_VIRGIN_NEW,
					CTasks::METASTATE_EXPIRED,
					CTasks::STATE_NEW,
					CTasks::STATE_PENDING,
					CTasks::STATE_IN_PROGRESS
				]
			],
			$this->arParams['FILTER']
		);
		$select = $this->arParams['SELECT'];
		$params = [
			'NAV_PARAMS' => ['nTopCount' => 15]
		];

		$tasksDdRes = CTasks::GetList($order, $filter, $select, $params);
		while ($task = $tasksDdRes->Fetch())
		{
			if (array_key_exists('TITLE', $task))
			{
				$task['TITLE'] = \Bitrix\Main\Text\Emoji::decode($task['TITLE']);
			}
			if (array_key_exists('DESCRIPTION', $task) && $task['DESCRIPTION'] !== '')
			{
				$task['DESCRIPTION'] = \Bitrix\Main\Text\Emoji::decode($task['DESCRIPTION']);
			}
			$lastTasks[] = $task;
		}

		return $lastTasks;
	}

	/**
	 * @return array
	 * @throws TasksException
	 */
	private function getCurrentTasks()
	{
		$currentTasks = [];

		$order = ['TITLE' => 'ASC'];
		$filter = ['ID' => $this->arParams['VALUE']];
		$select = ['ID', 'TITLE', 'STATUS'];

		$tasksDdRes = CTasks::GetList($order, $filter, $select);
		while ($task = $tasksDdRes->Fetch())
		{
			$task['TITLE'] = \Bitrix\Main\Text\Emoji::decode($task['TITLE']) . ' [' . $task['ID'] . ']';
			$currentTasks[] = $task;
		}

		return $currentTasks;
	}
}