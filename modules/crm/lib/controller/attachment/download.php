<?php
namespace Bitrix\Crm\Controller\Attachment;

use Bitrix\Crm\Integration\DiskManager;
use Bitrix\Crm\Integration\StorageType;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Main;
use Bitrix\Main\Engine\ActionFilter\Authentication;

class Download extends Main\Engine\Controller
{
	public function configureActions()
	{
		$configureActions = parent::configureActions();

		$configureActions['downloadArchive'] = [
			'-prefilters' => [
				Main\Engine\ActionFilter\Csrf::class,
				Authentication::class,
			],
			'+prefilters' => [
				new Authentication(true),
				new Main\Engine\ActionFilter\CloseSession(),
			]
		];

		return $configureActions;
	}

	public function downloadArchiveAction(int $ownerTypeId, int $ownerId, array $fileIds)
	{
		$ownerTypeName = \CCrmOwnerType::ResolveName($ownerTypeId);
		$ownerId = (int)$ownerId;

		if ($ownerId <= 0 || !\CCrmOwnerType::IsDefined($ownerTypeId))
		{
			$this->addError(new Main\Error('Invalid data ownerTypeID = ' . $ownerTypeId . ', ownerID = ' . $ownerId));

			return null;
		}
		if ($ownerTypeId !== \CCrmOwnerType::Activity)
		{
			$this->addError(new Main\Error("The owner type '{$ownerTypeName}' is not supported in current context"));

			return null;
		}

		foreach ($fileIds as $fileId)
		{
			$isFileExists = false;
			switch ($ownerTypeId)
			{
				case \CCrmOwnerType::Activity:
					$isFileExists = \CCrmActivity::CheckStorageElementExists($ownerId, StorageType::Disk, $fileId);
					break;
			}

			if (!$isFileExists)
			{
				$this->addError(new Main\Error('File not found'));

				return null;
			}
		}

		if (!\CCrmPerms::IsAdmin())
		{
			$isPermitted = false;
			$userPermissions = \CCrmPerms::GetCurrentUserPermissions();

			switch ($ownerTypeId)
			{
				case \CCrmOwnerType::Activity:
					$bindings = \CCrmActivity::GetBindings($ownerId);
					break;
				default:
					$bindings = [];
			}

			foreach ($bindings as $binding)
			{
				if (EntityAuthorization::checkReadPermission($binding['OWNER_TYPE_ID'], $binding['OWNER_ID'], $userPermissions))
				{
					$isPermitted = true;
					break;
				}
			}
			if (!$isPermitted)
			{
				$this->addError(new Main\Error('Access denied'));

				return null;
			}
		}

		return DiskManager::buildArchive('archive' . date('y-m-d') . '.zip', $fileIds);
	}
}
