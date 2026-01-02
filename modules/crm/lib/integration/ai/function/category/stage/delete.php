<?php

namespace Bitrix\Crm\Integration\AI\Function\Category\Stage;

use Bitrix\Crm\Integration\AI\Contract\AIFunction;
use Bitrix\Crm\Integration\AI\Function\Category\Dto\Stage\DeleteParameters;
use Bitrix\Crm\Result;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Localization\Loc;

final class Delete implements AIFunction
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
		$parameters = new DeleteParameters($args);
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

		$stageToDelete = null;
		$stageCollection = $factory->getStages($parameters->categoryId);
		foreach ($stageCollection->getAll() as $stage)
		{
			if ($stage->getStatusId() === $parameters->stageId)
			{
				$stageToDelete = $stage;

				break;
			}
		}

		if ($stageToDelete === null)
		{
			return Result::fail(Loc::getMessage('CRM_INTEGRATION_AI_FUNCTION_CATEGORY_STAGE_DELETE_STAGE_NOT_FOUND_ERROR'), 'STAGE_NOT_FOUND');
		}

		return $stageToDelete->delete();
	}
}
