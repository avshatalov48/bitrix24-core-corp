<?php

namespace Bitrix\Crm\Integrity;

use Bitrix\Main\ORM\Objectify\State;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Service\Container;

class AutoSearchUserSettings extends Entity\EO_AutosearchUserSettings
{
	public const STATUS_NEW = 1;
	public const STATUS_INDEX_REBUILDING = 2;
	public const STATUS_READY_TO_MERGE = 3;
	public const STATUS_MERGING = 4;
	public const STATUS_CONFLICTS_RESOLVING = 5;

	public const DEFAULT_EXEC_INTERVAL = 1;

	public const MERGE_ACTIVITY_TIMEOUT = 120;

	public static function getForUserByEntityType(int $entityTypeId, int $userId = null): AutoSearchUserSettings
	{
		$userId = $userId ?: \CCrmSecurityHelper::GetCurrentUser()->GetID();
		$entity = Entity\AutosearchUserSettingsTable::query()
			->where('ENTITY_TYPE_ID', $entityTypeId)
			->where('USER_ID', $userId)
			->setSelect(['*'])
			->setLimit(1)
			->fetchObject();

		if ($entity)
		{
			return $entity;
		}

		$entity = new static();
		$entity->setEntityTypeId($entityTypeId);
		$entity->setUserId($userId);

		return $entity;
	}

	public static function isEnabled(): bool
	{
		$restriction = \Bitrix\Crm\Restriction\RestrictionManager::getDuplicateControlRestriction();
		return $restriction->hasPermission();
	}

	public static function hasAccess(int $entityTypeId, int $userId = null): bool
	{
		$userId = $userId ?: \CCrmSecurityHelper::GetCurrentUser()->GetID();
		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);

		$permissions = \CCrmPerms::GetUserPermissions($userId);
		return !$permissions->HavePerm($entityTypeName, BX_CRM_PERM_NONE, 'WRITE')
			&& !$permissions->HavePerm($entityTypeName, BX_CRM_PERM_NONE, 'DELETE');
	}

	public function createIfNotExists(int $execInterval = self::DEFAULT_EXEC_INTERVAL): void
	{
		if ($this->state === State::RAW)
		{
			$this->setStatusId(self::STATUS_NEW);
			$this->setExecInterval($execInterval);
			$this->setNextExecTime(new DateTime());
			$this->save();
		}
	}

	public function createDisabledIfNotExists(): void
	{
		if ($this->state === State::RAW)
		{
			$this->setExecInterval(0);
			$this->save();
		}
	}

	public function setExecInterval(int $interval): self
	{
		$nextExecTime = $this->getLastExecTime();
		if ($nextExecTime && $interval > 0)
		{
			$nextExecTime->add($interval . ' days');
			$this->setNextExecTime($nextExecTime);
		}
		elseif ($interval > 0) // not executed yet
		{
			$this->setNextExecTime(new DateTime());
		}
		else // disabled
		{
			$this->setNextExecTime(null);
			$this->setStatusId(self::STATUS_NEW);
		}
		return parent::setExecInterval($interval);
	}

	public function setStatusId($statusId): self
	{
		if ($statusId === self::STATUS_NEW)
		{
			$this->clearConflictsNotification();
		}
		return parent::setStatusId($statusId);
	}

	public function calcAndSetNextExecTime()
	{
		$interval = $this->getExecInterval();
		if ($interval > 0)
		{
			$this->setNextExecTime((new DateTime())->add($interval . ' days'));
		}
		else
		{
			$this->setNextExecTime(null);
		}
	}

	public function tryToSetMergeId(string $mergeId): bool
	{
		$currentMergeId = $this->getMergeId();
		if ($currentMergeId === $mergeId)
		{
			return true;
		}
		$mergeActivityTimestamp = $this->getMergeActivityDate() ?
			$this->getMergeActivityDate()->getTimestamp() : 0;
		if (
			!$currentMergeId ||
			(time() - $mergeActivityTimestamp > static::MERGE_ACTIVITY_TIMEOUT))
		{
			$this
				->setMergeId($mergeId)
				->setMergeActivityDate(new DateTime())
				->save();
			return true;
		}
		return false;
	}

	public function canShowNotification(): bool
	{
		$sessionStorage = \Bitrix\Main\Application::getInstance()->getLocalSession($this->getSessionStorageKey());
		$key = 'notification_shown_'.$this->getStatusId();
		if ($sessionStorage->get($key))
		{
			return false;
		}
		$sessionStorage->set($key, true);
		return true;
	}
	protected function clearConflictsNotification(): void
	{
		if (Container::getInstance()->getContext()->getUserId())
		{
			$sessionStorage = \Bitrix\Main\Application::getInstance()->getLocalSession($this->getSessionStorageKey());
			$sessionStorage->clear();
		}
	}
	protected function getSessionStorageKey(): string
	{
		return 'crm.dedupe.autosearch.' . $this->getEntityTypeId();
	}
}
