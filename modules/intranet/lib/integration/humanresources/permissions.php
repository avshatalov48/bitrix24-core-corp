<?php

namespace Bitrix\Intranet\Integration\HumanResources;

use Bitrix\HumanResources\Access\StructureAccessController;
use Bitrix\HumanResources\Access\StructureActionDictionary;
use Bitrix\Intranet\User\Access\Model\TargetUserModel;
use Bitrix\Intranet\User\Access\Model\UserModel;
use Bitrix\Main\Access\Exception\UnknownActionException;
use Bitrix\Main\Loader;

class Permissions
{
	private bool $isAvailable;

	public function __construct(
		private readonly UserModel $currentUser,
	)
	{
		$this->isAvailable = Loader::includeModule('humanresources');
	}

	/**
	 * @param TargetUserModel|null $targetUser
	 * @return bool
	 */
	public function canFireUser(?TargetUserModel $targetUser): bool
	{
		if (!$this->isAvailable)
		{
			return false;
		}

		$accessController = new StructureAccessController($this->currentUser->getUserId());
		$hrTargetUserModel = $targetUser
			? \Bitrix\HumanResources\Access\Model\UserModel::createFromId($targetUser->getId())
			: null;

		try
		{
			return $accessController->check(
				StructureActionDictionary::ACTION_FIRE_EMPLOYEE,
				$hrTargetUserModel,
			);
		}
		catch (UnknownActionException $exception)
		{
			return false;
		}
	}
}
