<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if(!\Bitrix\Main\Loader::includeModule('rpa'))
{
	return;
}

use Bitrix\Main;
use Bitrix\Rpa;
use Bitrix\Bizproc;

class RpaAutomationTaskComponent extends Rpa\Components\Base
{
	public function onPrepareComponentParams($arParams)
	{
		$arParams["typeId"] = (string) $arParams["typeId"];
		$arParams["elementId"] = (int) $arParams["elementId"];
		$arParams["SET_TITLE"] = (($arParams["SET_TITLE"] ?? '') === "N" ? "N" : "Y");

		return $arParams;
	}

	public function executeComponent()
	{
		parent::init();

		if ($this->arParams['SET_TITLE'] === 'Y')
		{
			$this->getApplication()->SetTitle(GetMessage("RPA_AUTOMATION_TASK_TITLE"));
		}

		$this->resolveType();

		$type = Rpa\Model\TypeTable::getById($this->arParams['typeId'])->fetchObject();
		$item = $type ? $type->getItem($this->arParams['elementId']) : null;

		if (!$item)
		{
			$this->errorCollection->setError(
				new Main\Error(Main\Localization\Loc::getMessage('RPA_AUTOMATION_TASK_NOT_FOUND'))
			);
			$this->includeComponentTemplate();
			return false;
		}

		$this->arResult['ITEM'] = $item;

		$this->arResult['DOCUMENT_TYPE'] = Rpa\Integration\Bizproc\Document\Item::makeComplexType(
			$this->arParams['typeId']
		);

		$this->arResult['DOCUMENT_ID'] = Rpa\Integration\Bizproc\Document\Item::makeComplexId(
			$this->arParams['typeId'],
			$this->arParams['elementId']
		);

		$userId = (int) Main\Engine\CurrentUser::get()->getId();
		$this->arResult['USER'] = current(static::getUsers([$userId]));

		$task = $this->arResult['TASK'] ?? current(Rpa\Driver::getInstance()->getTaskManager()->getIncompleteItemTasks($item, $userId));
		if ($task)
		{
			$task = Rpa\Driver::getInstance()->getTaskManager()->getTaskById($task['ID']);
			$this->arResult['TASK'] = $task;
			$this->arResult['IS_MINE'] = $this->isMyTask($task, $userId);
		}
		else
		{
			$this->errorCollection->setError(
				new Main\Error(Main\Localization\Loc::getMessage('RPA_AUTOMATION_TASK_NOT_FOUND'))
			);
		}

		$this->includeComponentTemplate();
	}

	private function isMyTask($task, $meId)
	{
		return in_array($meId, $task['INCOMPLETE_USERS']);
	}

	private function resolveType()
	{
		if ($this->arParams['typeId'] === 'id')
		{
			$this->arResult['TASK'] = Rpa\Driver::getInstance()->getTaskManager()->getTaskById($this->arParams['elementId']);
			if ($this->arResult['TASK'])
			{
				$documentId = $this->arResult['TASK']['PARAMETERS']['DOCUMENT_ID'];

				$this->arParams['typeId'] = \Bitrix\Rpa\Integration\Bizproc\Document\Item::getDocumentTypeId($documentId[2]);
				$this->arParams['elementId'] = \Bitrix\Rpa\Integration\Bizproc\Document\Item::getDocumentItemId($documentId[2]);
			}
		}
	}
}