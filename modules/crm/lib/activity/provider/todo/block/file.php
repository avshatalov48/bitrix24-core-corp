<?php

namespace Bitrix\Crm\Activity\Provider\ToDo\Block;

use Bitrix\Crm\Activity\Provider\ToDo\FileUploader\Mode;
use Bitrix\Crm\Activity\Provider\ToDo\FileUploader\Uploader;
use Bitrix\Crm\Activity\Provider\ToDo\OptionallyConfigurable;
use Bitrix\Crm\Activity\Provider\ToDo\SaveConfig;
use Bitrix\Crm\Integration\Disk\HiddenStorage;
use Bitrix\Crm\Integration\DiskManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

final class File extends Base
{
	public const TYPE_NAME = 'file';

	public function prepareEntityBefore(OptionallyConfigurable $entity): SaveConfig
	{
		$fileTokens = $this->blockData['fileTokens'] ?? [];
		if (empty($fileTokens) && empty($this->activityData['storageElementIds']))
		{
			return $this->getSaveConfig();
		}

		$ownerId = $this->activityData['ownerId'];
		$ownerTypeId = $this->activityData['ownerTypeId'];
		$fileUploader = (new Uploader($fileTokens, $ownerTypeId, $ownerId))
			->setActivityId($this->activityData['id'] ?? null)
			->setCurrentStorageElementIds($entity->getStorageElementIds() ?? [])
		;

		$isCopy = (bool)($entity->getAdditionalFields()['IS_COPY'] ?? false);
		if ($isCopy)
		{
			$fileUploader->setMode(Mode::COPY);
		}
		$result = $fileUploader->saveFilesToStorage();

		if (!$result->isSuccess())
		{
			return $this->getSaveConfig();
		}

		$storageElementIds = $result->getData()['ids'];

		if (empty($storageElementIds))
		{
			return $this->getSaveConfig();
		}

		$this->setStorageElementIds($entity, $storageElementIds);

		return $this->getSaveConfig(true);
	}

	protected function getSaveConfig(bool $needSave = false): SaveConfig
	{
		return new SaveConfig($needSave);
	}

	public function prepareEntity(OptionallyConfigurable $entity, bool $skipActiveSectionCheck = false): void
	{
		$this->setCalendarFileLinks($entity, $entity->getStorageElementIds() ?? []);
	}

	public function setStorageElementIds(OptionallyConfigurable $entity, array $storageElementIds): void
	{
		$entity->setStorageElementIds($storageElementIds);
	}

	public function setCalendarFileLinks(OptionallyConfigurable $entity, array $storageElementIds): void
	{
		$options = [
			'OWNER_TYPE_ID' => \CCrmOwnerType::Activity,
			'OWNER_ID' => $entity->getId(),
		];

		$title = Loc::getMessage('CRM_ACTIVITY_PROVIDER_TODO_FILE_CALENDAR_DESCRIPTION_TITLE');

		$fields['CALENDAR_ADDITIONAL_DESCRIPTION_DATA']['FILE'] = [
			'TITLE' => $title,
			'ITEMS' => [],
		];

		foreach ($storageElementIds as $id)
		{
			$fileInfo = DiskManager::getFileInfo($id, false, $options);
			if (is_array($fileInfo) && !empty($fileInfo['VIEW_URL']))
			{
				$link = (new Uri($fileInfo['VIEW_URL']))->toAbsolute();
				$fields['CALENDAR_ADDITIONAL_DESCRIPTION_DATA']['FILE']['ITEMS'][] = '[URL=' . $link . ']' .  $fileInfo['NAME'] . '[/URL]';
			}
		}

		$entity->appendAdditionalFields($fields);
	}

	public function fetchSettings(): array
	{
		$storageElementIds = $this->activityData['storageElementIds'] ?? null;

		if (!$storageElementIds)
		{
			return [];
		}

		$item = new ItemIdentifier($this->activityData['ownerTypeId'], $this->activityData['ownerId']);

		return [
			'fileTokens' => array_values($this->getCurrentFileIds($item, $storageElementIds)),
		];
	}

	protected function getCurrentFileIds(ItemIdentifier $item, array $storageFileIds): array
	{
		return $this->getHiddenStorage($item)->fetchFileIdsByStorageFileIds(
			$storageFileIds,
			HiddenStorage::USE_DISK_OBJ_ID_AS_KEY
		);
	}

	protected function getHiddenStorage(ItemIdentifier $item): HiddenStorage
	{
		return (new HiddenStorage())->setSecurityContextOptions([
			'entityTypeId' => $item->getEntityTypeId(),
			'entityId' => $item->getEntityId(),
		]);
	}
}
