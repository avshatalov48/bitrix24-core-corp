<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main;
use Bitrix\Rpa\UserField\UserField;

if(!\Bitrix\Main\Loader::includeModule('rpa'))
{
	return;
}

class RpaItemDetailComponent extends Bitrix\Rpa\Components\ItemDetail
{
	const TIMELINE_RECORD_COUNT = 20;

	public function executeComponent()
	{
		$this->init();
		if($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}

		$this->arResult['formParams'] = $this->prepareFormParams();
		$history = $this->getHistory();
		$tasks = \Bitrix\Rpa\Driver::getInstance()->getTaskManager()->getTimelineTasks($this->item);

		$this->arResult['jsParams'] = [
			'typeId' => $this->type->getId(),
			'id' => $this->item->getId(),
			'containerId' => 'rpa-detail-'.$this->type->getId().'-'.$this->item->getId(),
			'stages' => $this->getStages(),
			'item' => $this->getItem(),
			'history' => $history,
			'nameFormat' => \Bitrix\Main\Application::getInstance()->getContext()->getCulture()->getNameFormat(),
			'timelinePageSize' => static::TIMELINE_RECORD_COUNT,
			'tasks' => $tasks,
			'editorId' => $this->arResult['formParams']['GUID']
		];

		$userIds = [];
		foreach($history as $item)
		{
			if($item['userId'] > 0)
			{
				$userIds[$item['userId']] = $item['userId'];
			}
		}
		foreach($tasks as $task)
		{
			if($task['userId'] > 0)
			{
				$userIds[$task['userId']] = $task['userId'];
			}
			if ($task['data']['users'])
			{
				foreach ($task['data']['users'] as $user)
				{
					$userIds[$user['id']] = $user['id'];
				}
			}
		}

		$this->arResult['jsParams']['users'] = static::getUsers($userIds);

		$pullManager = \Bitrix\Rpa\Driver::getInstance()->getPullManager();

		if($this->item->getId() > 0)
		{
			$this->arResult['jsParams']['itemUpdatedPullTag'] = $pullManager->subscribeOnItemUpdatedEvent($this->item->getType()->getId(), $this->item->getId());
			$this->arResult['jsParams']['timelinePullTag'] = $pullManager->subscribeOnTimelineUpdate($this->item->getType()->getId(), $this->item->getId());
		}

		$this->arResult['jsParams']['taskCountersPullTag'] = $pullManager->subscribeOnTaskCounters();

		$this->getApplication()->setTitle(htmlspecialcharsbx($this->getTitle()));

		$this->includeComponentTemplate();
	}

	protected function getStages(): array
	{
		$controller = new \Bitrix\Rpa\Controller\Stage();
		$stages = [];

		foreach($this->type->getStages() as $stage)
		{
			$stages[] = $controller->prepareData($stage, false);
		}

		return $stages;
	}

	protected function getItem(): array
	{
		$controller = new \Bitrix\Rpa\Controller\Item();
		return $controller->prepareItemData($this->item);
	}

	protected function getHistory(): array
	{
		$controller = new \Bitrix\Rpa\Controller\Timeline();

		$pageNavigation = new \Bitrix\Main\UI\PageNavigation('rpa_item_detail_timeline');
		$pageNavigation->setRecordCount(static::TIMELINE_RECORD_COUNT);
		$pageNavigation->setCurrentPage(1);

		if(!$this->item->getId())
		{
			return [];
		}

		return $controller->listForItemAction($this->item->getType(), $this->item->getId(), $pageNavigation)['timeline'];
	}
}