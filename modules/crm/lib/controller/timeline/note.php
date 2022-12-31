<?php

namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Timeline\Entity\NoteTable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\Type\DateTime;

class Note extends Base
{
	public function saveAction(int $itemId, int $itemType, int $ownerTypeId, int $ownerId, string $text): bool
	{
		if (!$this->checkPermissions($itemId, $itemType, $ownerTypeId, $ownerId))
		{
			return false;
		}

		$note = $this->resolveNote($itemId, $itemType);

		$note->set('TEXT', $text);
		$note->set('UPDATED_BY_ID', CurrentUser::get()->getId());
		$note->set('UPDATED_TIME', new DateTime());
		$saveResult = $note->save();
		if ($saveResult->isSuccess())
		{
			$this->sendPullEvent($ownerTypeId, $ownerId, $itemType, $itemId);

			return true;
		}
		$this->addErrors($saveResult->getErrors());

		return false;
	}

	public function deleteAction(int $itemId, int $itemType, int $ownerTypeId, int $ownerId): bool
	{
		if (!$this->checkPermissions($itemId, $itemType, $ownerTypeId, $ownerId))
		{
			return false;
		}

		$note = $this->findNote($itemId, $itemType);
		if (!$note)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return false;
		}

		$deleteResult = $note->delete();
		if ($deleteResult->isSuccess())
		{
			$this->sendPullEvent($ownerTypeId, $ownerId, $itemType, $itemId);

			return true;
		}
		$this->addErrors($deleteResult->getErrors());

		return false;
	}

	public function getAction(int $itemId, int $itemType, int $ownerTypeId, int $ownerId): ?array
	{
		if (!$this->checkPermissions($itemId, $itemType, $ownerTypeId, $ownerId))
		{
			return null;
		}

		$note = $this->findNote($itemId, $itemType);
		if (!$note)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return null;
		}

		return [
			'text' => $note->getText(),
			'createdById' => $note->getCreatedById(),
			'createdTime' => $this->formatDateTime($note->getCreatedTime()),
			'updatedById' => $note->getUpdatedById(),
			'updatedTime' => $this->formatDateTime($note->getUpdatedTime()),
		];
	}

	private function formatDateTime(DateTime $datetime): string
	{
			if ($this->getScope() === \Bitrix\Main\Engine\Controller::SCOPE_REST)
			{
				return \CRestUtil::convertDateTime($datetime);
			}

			return $datetime->toString();
	}

	private function resolveNote(int $itemId, int $type): EntityObject
	{
		return $this->findNote($itemId, $type) ?? $this->newNote($itemId, $type);
	}

	private function findNote(int $itemId, int $itemType): ?EntityObject
	{
		return NoteTable::query()
			->addSelect('*')
			->where('ITEM_ID', $itemId)
			->where('ITEM_TYPE', $itemType)
			->fetchObject()
		;
	}

	private function newNote(int $itemId, int $itemType): EntityObject
	{
		$note = NoteTable::createObject();
		$note->set('ITEM_ID', $itemId);
		$note->set('ITEM_TYPE', $itemType);
		$note->set('CREATED_BY_ID', CurrentUser::get()->getId());
		$note->set('CREATED_TIME', new DateTime());

		return $note;
	}

	private function checkPermissions(int $itemId, int $itemType, int $ownerTypeId, int $ownerId): bool
	{
		if (!Container::getInstance()->getUserPermissions()->checkUpdatePermissions($ownerTypeId, $ownerId))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return false;
		}

		if ($itemType === NoteTable::NOTE_TYPE_HISTORY)
		{
			if (!\Bitrix\Crm\Timeline\TimelineEntry::checkBindingExists($itemId, $ownerTypeId, $ownerId))
			{
				$this->addError(ErrorCode::getNotFoundError());

				return false;
			}
		}

		if ($itemType === NoteTable::NOTE_TYPE_ACTIVITY)
		{
			$bindingFound = false;
			$activityBindings = \CCrmActivity::GetBindings($itemId);
			foreach ($activityBindings as $binding)
			{
				if ($binding['OWNER_TYPE_ID'] == $ownerTypeId && $binding['OWNER_ID'] == $ownerId)
				{
					$bindingFound = true;
					break;
				}
			}
			if (!$bindingFound)
			{
				$this->addError(ErrorCode::getNotFoundError());

				return false;
			}
		}

		return true;
	}

	private function sendPullEvent(int $ownerTypeId, int $ownerId, int $noteItemType, int $noteItemId): void
	{
		if ($noteItemType === \Bitrix\Crm\Timeline\Entity\NoteTable::NOTE_TYPE_HISTORY)
		{
			\Bitrix\Crm\Timeline\Controller::getInstance()->sendPullEventOnUpdate(
				new ItemIdentifier($ownerTypeId, $ownerId),
				$noteItemId
			);
		}
		elseif ($noteItemType === \Bitrix\Crm\Timeline\Entity\NoteTable::NOTE_TYPE_ACTIVITY)
		{
			$activity = \CCrmActivity::GetByID($noteItemId, false);
			$activity['IS_INCOMING_CHANNEL'] = \Bitrix\Crm\Activity\IncomingChannel::getInstance()->isIncomingChannel($noteItemId) ? 'Y' : 'N';

			if ($activity)
			{
				\Bitrix\Crm\Timeline\ActivityController::getInstance()->notifyTimelinesAboutActivityUpdate(
					$activity,
					null,
					true
				);
			}
		}
	}
}
