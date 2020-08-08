<?php

namespace Bitrix\Rpa\Controller;

use Bitrix\Main\Engine\ActionFilter\Scope;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Model\ItemHistoryTable;

class Timeline extends Base
{
	public function configureActions(): array
	{
		$configureActions = parent::configureActions();
		$configureActions['delete'] =
		$configureActions['update'] =
		$configureActions['add'] = [
			'+prefilters' => [
				new Scope(Scope::REST)
			],
		];

		return $configureActions;
	}

	public function addAction(\Bitrix\Rpa\Model\Type $type, int $itemId, array $fields): ?array
	{
		$item = $type->getItem($itemId);
		if(!$item)
		{
			$this->addError(new Error(Loc::getMessage('RPA_ITEM_NOT_FOUND_ERROR')));
			return null;
		}
		if(!Driver::getInstance()->getUserPermissions()->canViewItem($item))
		{
			$this->addError(new Error(Loc::getMessage('RPA_VIEW_ITEM_ACCESS_DENIED')));
			return null;
		}

		$timeline = \Bitrix\Rpa\Model\Timeline::createForItem($item);
		$timeline->setUserId(Driver::getInstance()->getUserId());
		$timeline->setTitle($fields['title']);
		$timeline->setDescription($fields['description']);
		$timeline->setData([
			'scope' => ItemHistoryTable::SCOPE_REST,
		]);

		$result = $timeline->save();

		if($result->isSuccess())
		{
			Driver::getInstance()->getPullManager()->sendTimelineAddEvent($timeline);
			return [
				'timeline' => $timeline->preparePublicData(),
			];
		}

		$this->addErrors($result->getErrors());
		return null;
	}

	public function updateAction(\Bitrix\Rpa\Model\Timeline $timeline, array $fields): ?array
	{
		$item = $timeline->getItem();
		if(!$item)
		{
			$this->addError(new Error(Loc::getMessage('RPA_ITEM_NOT_FOUND_ERROR')));
			return null;
		}
		if(!Driver::getInstance()->getUserPermissions()->canViewItem($item))
		{
			$this->addError(new Error(Loc::getMessage('RPA_VIEW_ITEM_ACCESS_DENIED')));
			return null;
		}
		if(
			$timeline->getUserId() !== Driver::getInstance()->getUserId()
			|| $timeline->getData()['scope'] !== ItemHistoryTable::SCOPE_REST
		)
		{
			$this->addError(new Error('Access denied'));
			return null;
		}

		if(isset($fields['title']))
		{
			$timeline->setTitle($fields['title']);
		}
		if(isset($fields['description']))
		{
			$timeline->setDescription($fields['description']);
		}

		$result = $timeline->save();

		if($result->isSuccess())
		{
			Driver::getInstance()->getPullManager()->sendTimelineUpdateEvent($timeline);
			return [
				'timeline' => $timeline->preparePublicData(),
			];
		}

		$this->addErrors($result->getErrors());
		return null;
	}

	public function deleteAction(\Bitrix\Rpa\Model\Timeline $timeline): void
	{
		$item = $timeline->getItem();
		if(!$item)
		{
			$this->addError(new Error(Loc::getMessage('RPA_ITEM_NOT_FOUND_ERROR')));
			return;
		}
		if(!Driver::getInstance()->getUserPermissions()->canViewItem($item))
		{
			$this->addError(new Error(Loc::getMessage('RPA_VIEW_ITEM_ACCESS_DENIED')));
			return;
		}
		if(
			$timeline->getUserId() !== Driver::getInstance()->getUserId()
			|| $timeline->getData()['scope'] !== ItemHistoryTable::SCOPE_REST
		)
		{
			$this->addError(new Error('Access denied'));
			return;
		}

		$timeline->delete();
	}

	public function listForItemAction(\Bitrix\Rpa\Model\Type $type, int $itemId, PageNavigation $pageNavigation = null): ?array
	{
		$item = $type->getItem($itemId);
		if(!$item)
		{
			$this->addError(new Error(Loc::getMessage('RPA_ITEM_NOT_FOUND_ERROR')));
			return null;
		}
		if(!Driver::getInstance()->getUserPermissions()->canViewItem($item))
		{
			$this->addError(new Error(Loc::getMessage('RPA_VIEW_ITEM_ACCESS_DENIED')));
			return null;
		}

		$parameters = [];

		if($pageNavigation)
		{
			$parameters['offset'] = $pageNavigation->getOffset();
			$parameters['limit'] = $pageNavigation->getLimit();
		}

		$result = [
			'timeline' => [],
		];

		$list = \Bitrix\Rpa\Model\TimelineTable::getListByItem($type->getId(), $itemId, $parameters);
		foreach($list as $item)
		{
			$result['timeline'][] = $item->preparePublicData([
				'withFiles' => false,
			]);
		}

		return $result;
	}

	public function updateIsFixedAction(\Bitrix\Rpa\Model\Timeline $timeline, string $isFixed, string $eventId = ''): ?array
	{
		$type = Driver::getInstance()->getType($timeline->getTypeId());
		if(!$type)
		{
			$this->addError(new Error(Loc::getMessage('RPA_NOT_FOUND_ERROR')));
			return null;
		}
		$item = $type->getItem($timeline->getItemId());
		if(!$item)
		{
			$this->addError(new Error(Loc::getMessage('RPA_ITEM_NOT_FOUND_ERROR')));
			return null;
		}
		if(!Driver::getInstance()->getUserPermissions()->canViewItem($item))
		{
			$this->addError(new Error(Loc::getMessage('RPA_VIEW_ITEM_ACCESS_DENIED')));
			return null;
		}

		$result = $timeline->setIsFixed(($isFixed === 'y'))->save();
		if(!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return null;
		}

		Driver::getInstance()->getPullManager()->sendTimelinePinEvent($timeline, $eventId);

		return [
			'timeline' => $timeline->preparePublicData(),
		];
	}
}