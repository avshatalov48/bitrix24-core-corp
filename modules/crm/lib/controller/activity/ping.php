<?php

namespace Bitrix\Crm\Controller\Activity;

use Bitrix\Crm\Activity\Ping\PingSettingsProvider;
use Bitrix\Crm\Activity\Provider\ProviderManager;
use Bitrix\Crm\Activity\Provider\ToDo\BlocksManager;
use Bitrix\Crm\Activity\Provider\ToDo\OptionallyConfigurable;
use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use CAllCrmActivity;

class Ping extends Base
{
	public function updateOffsetsAction(
		int $ownerTypeId,
		int $ownerId,
		int $id,
		string $providerId = 'CRM_TODO',
		array $value = []
	): ?array
	{
		$itemIdentifier = new ItemIdentifier($ownerTypeId, $ownerId);
		$activity = $this->loadActivity($itemIdentifier, $id, $providerId);
		if (!$activity)
		{
			return null;
		}

		if ($activity->isCompleted())
		{
			$this->addError(new Error( Loc::getMessage('CRM_ACTIVITY_TODO_UPDATE_PING_OFFSETS_ERROR')));

			return null;
		}

		$filteredValue = PingSettingsProvider::filterOffsets($value);
		if (!empty($value) && empty($filteredValue))
		{
			$this->addError(new Error( Loc::getMessage('CRM_ACTIVITY_TODO_WRONG_PING_OFFSETS_FORMAT')));

			return null; // nothing to do - wrong input
		}

		$activity->setAdditionalFields(['PING_OFFSETS' => $filteredValue]);

		$activity = (BlocksManager::createFromEntity($activity))->enrichEntityWithBlocks(null, true);

		return $this->saveActivity($activity, [], true);
	}

	private function loadActivity(ItemIdentifier $itemIdentifier, int $id, string $providerId): ?OptionallyConfigurable
	{
		$provider = CAllCrmActivity::GetProviderByIdSafelyByDisabled($providerId);
		if (!$provider)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return null;
		}

		$activityEntityClass = ProviderManager::getProviderEntity($provider::getId());

		if (!$activityEntityClass)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return null;
		}

		$activity = (new $activityEntityClass($itemIdentifier, new $provider()))->load($id);

		if (!$activity)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return null;
		}

		return $activity;
	}

	private function saveActivity(OptionallyConfigurable $activity, array $options = [], bool $useCurrentSettings = false): ?array
	{
		$saveResult = $activity->save($options, $useCurrentSettings);
		if ($saveResult->isSuccess())
		{
			return [
				'id' => $activity->getId(),
			];
		}

		$this->addErrors($saveResult->getErrors());

		return null;
	}
}