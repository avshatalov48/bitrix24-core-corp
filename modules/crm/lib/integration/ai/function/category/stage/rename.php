<?php

namespace Bitrix\Crm\Integration\AI\Function\Category\Stage;

use Bitrix\Crm\Integration\AI\Contract\AIFunction;
use Bitrix\Crm\Integration\AI\Function\Category\Dto\Stage\RenameParameters;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Result;

final class Rename implements AIFunction
{
	private readonly UserPermissions $permissions;

	public function __construct(
		private readonly int $currentUserId,
	)
	{
		$this->permissions = Container::getInstance()->getUserPermissions($this->currentUserId);
	}

	public function isAvailable(): bool
	{
		return true;
	}

	public function invoke(...$args): \Bitrix\Main\Result
	{
		$parameters = new RenameParameters($args);
		if ($parameters->hasValidationErrors())
		{
			return Result::fail($parameters->getValidationErrors());
		}

		if (!$this->permissions->isAdminForEntity($parameters->entityTypeId))
		{
			return Result::failAccessDenied();
		}

		/** @var Factory $factory */
		$factory = Container::getInstance()->getFactory($parameters->entityTypeId);

		$stageCollection = $factory->getStages($parameters->categoryId);
		foreach ($stageCollection->getAll() as $stage)
		{
			$newName = $parameters->getRenameValue($stage->getStatusId());
			if ($newName !== null)
			{
				$stage->setName($newName);
			}
		}

		return $stageCollection->save();
	}
}
