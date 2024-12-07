<?php

namespace Bitrix\Crm\Integration\Disk;


use Bitrix\Crm\Integration\Disk\Dto\SaveAOParam;;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Main\Loader;

class QuoteItemAttachedObjectPersist
{
	use Singleton;

	private DiskRepository $diskRepository;

	public function __construct()
	{
		Loader::requireModule('disk');

		$this->diskRepository = DiskRepository::getInstance();
	}

	// Item\Quote $quoteItem, array $values, int $userId
	public function saveAllAsAttachedObject(SaveAOParam $param): array
	{
		$needToDetach = array_diff($param->prevStorageIds(), $param->storageIds());
		if (!empty($needToDetach))
		{
			$this->diskRepository->detachByAttachedObjectIds($needToDetach);
		}

		$valuesToInsert = [];
		foreach($param->storageIds() as $value)
		{
			if (empty($value))
			{
				continue;
			}

			$insertValue = $this->save($param->quoteId(), $value, $param->userId());
			if ($insertValue)
			{
				$valuesToInsert[] = $insertValue;
			}
		}

		return $valuesToInsert;
	}

	private function save(int $quoteId, string|int $value, int $userId): ?int
	{
		[$type, $realValue] = FileUserType::detectType($value);

		if ($type === FileUserType::TYPE_NEW_OBJECT)
		{
			$fileModel = $this->diskRepository->getFileById($realValue);
			if (!$fileModel || !$fileModel->getStorage())
			{
				return null;
			}

			$securityContext = $fileModel->getStorage()->getSecurityContext($userId);

			$canUpdate = $fileModel->canUpdate($securityContext);
			$attachedModel = $fileModel->attachToEntity(
				[
					'id' => $quoteId,
					'type' => 'CRM_QUOTE',
				],
				[
					'isEditable' => $canUpdate,
					'allowEdit' => null,
					'createdBy' => $userId,
				]
			);

			return $attachedModel?->getId();
		}

		return $realValue;
	}

}
