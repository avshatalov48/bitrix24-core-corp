<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Internals\Task\Result\ResultManager;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util\User;

class TasksMobileFragmentRendererComponent extends CBitrixComponent
{
	private Collection $errors;

	private function checkPermissions(): bool
	{
		$this->arResult['USER_ID'] = User::getId();

		if (!$this->arResult['USER_ID'])
		{
			$this->errors->add('ACCESS_DENIED', 'Current user is not defined');
		}

		return $this->errors->checkNoFatals();
	}

	private function checkParameters(): bool
	{
		$allowedFragmentsTypes = ['table', 'video'];

		$this->arResult['FRAGMENT_TYPE'] = (string)$this->arParams['FRAGMENT_TYPE'];
		$this->arResult['FRAGMENT_ID'] = (int)$this->arParams['FRAGMENT_ID'];

		if (!in_array($this->arResult['FRAGMENT_TYPE'], $allowedFragmentsTypes, true))
		{
			$this->errors->add('WRONG_FRAGMENT_TYPE', 'Wrong fragment type');
		}

		if ($this->arParams['TASK_ID'])
		{
			$this->arResult['TASK_ID'] = (int)$this->arParams['TASK_ID'];
		}
		if ($this->arParams['RESULT_ID'])
		{
			$this->arResult['RESULT_ID'] = (int)$this->arParams['RESULT_ID'];
		}

		if (!$this->arResult['TASK_ID'])
		{
			$this->errors->add('WRONG_TASK_ID', 'Wrong task id');
		}

		return $this->errors->checkNoFatals();
	}

	private function checkRights(): bool
	{
		$userId = $this->arResult['USER_ID'];
		$taskId = $this->arResult['TASK_ID'];

		if (!TaskAccessController::can($userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->errors->add('ACCESS_DENIED', 'Task not found or not accessible');
		}

		return $this->errors->checkNoFatals();
	}

	public function getData(): void
	{
		$this->arResult['FRAGMENT'] = (
			array_key_exists('RESULT_ID', $this->arResult) && $this->arResult['RESULT_ID']
				? $this->getFragmentFromTaskResult()
				: $this->getFragmentFromTaskDescription()
		);
	}

	private function getFragmentFromTaskResult(): string
	{
		$resultId = $this->arResult['RESULT_ID'];
		$resultList = (new ResultManager($this->arResult['USER_ID']))->getTaskResults($this->arResult['TASK_ID']);
		$resultList = array_filter(
			$resultList,
			static function($result) use ($resultId) {
				return ($result->getId() === $resultId);
			}
		);
		if (empty($resultList))
		{
			return '';
		}
		$result = array_shift($resultList);

		return $this->getFragmentByType($result->getText());
	}

	private function getFragmentFromTaskDescription(): string
	{
		$task = new CTaskItem($this->arResult['TASK_ID'], $this->arResult['USER_ID']);
		$taskData = $task->getData(
			false,
			['select' => ['DESCRIPTION', 'DESCRIPTION_IN_BBCODE']]
		);

		return $this->getFragmentByType($taskData['DESCRIPTION']);
	}

	private function getFragmentByType(string $text): string
	{
		switch ($this->arResult['FRAGMENT_TYPE'])
		{
			case 'table':
				return $this->getTableFragment($text, $this->arResult['FRAGMENT_ID']);

			case 'video':
				return $this->getVideoFragment($text, $this->arResult['FRAGMENT_ID']);

			default:
				return '';
		}
	}

	private function getTableFragment(string $text, int $fragmentId): string
	{
		$index = 0;
		$tableEnd = 0;

		while (true)
		{
			$index++;

			$tableStart = mb_strpos($text, '[TABLE]', $tableEnd);
			$tableEnd = mb_strpos($text, '[/TABLE]', $tableStart);

			if ($tableStart === false || $tableEnd === false)
			{
				return '';
			}

			if ($tableStart >= $tableEnd)
			{
				return '';
			}

			if ($index === $fragmentId)
			{
				return mb_substr($text, $tableStart, $tableEnd + mb_strlen('[/TABLE]') - $tableStart);
			}

			if ($index >= 100)
			{
				return '';
			}
		}
	}

	private function getVideoFragment(string $text, int $fragmentId): string
	{
		if (
			preg_match_all("/\[VIDEO.*](?:.|\n)*\[\/VIDEO]/U", $text, $matches)
			&& !empty($matches)
			&& array_key_exists($fragmentId, $matches[0])
		)
		{
			return $matches[0][$fragmentId];
		}

		return '';
	}


	public function executeComponent()
	{
		$this->arResult['ERRORS'] = [];

		if (Loader::includeModule('tasks'))
		{
			$this->errors = new Collection();

			if (
				$this->checkPermissions()
				&& $this->checkParameters()
				&& $this->checkRights()
			)
			{
				$this->getData();
			}

			$this->arResult['ERRORS'] = $this->errors->getArrayMeta();
		}
		else
		{
			$this->arResult['ERRORS'][] = [
				'CODE' => 'TASKS_MODULE_NOT_INSTALLED',
				'MESSAGE' => 'Tasks module is not installed',
			];
		}

		$this->includeComponentTemplate();
	}
}