<?php

namespace Bitrix\Crm\Integration\AI\Function\Category\Stage;

use Bitrix\Crm\Integration\AI\Contract\AIFunction;
use Bitrix\Crm\Integration\AI\Function\Category\Dto\Stage\CreateParameters;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Result;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\UserPermissions;
use CCrmStatus;

final class Create implements AIFunction
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
		$parameters = new CreateParameters($args);
		if ($parameters->hasValidationErrors())
		{
			return Result::fail($parameters->getValidationErrors());
		}

		if (!$this->permissions->isAdminForEntity($parameters->entityTypeId))
		{
			return Result::failAccessDenied();
		}

		$entity = $this->getCCrmStatus($parameters);

		$fields = $this->getStageFields($parameters);
		$id = $entity->Add($fields);
		if (!$id)
		{
			$lastError = $entity->GetLastError();
			if ($lastError !== null)
			{
				return Result::fail($lastError);
			}

			return Result::failFromApplication();
		}

		return Result::success(id: $id);
	}

	private function getCCrmStatus(CreateParameters $parameters): CCrmStatus
	{
		/** @var Factory $factory */
		$factory = Container::getInstance()->getFactory($parameters->entityTypeId);
		$entityId = $factory->getStagesEntityId($parameters->categoryId);

		return new CCrmStatus($entityId);
	}

	private function getStageFields(CreateParameters $parameters): array
	{
		$semantics = $parameters->fields->semantics !== PhaseSemantics::PROCESS
			? $parameters->fields->semantics
			: null;

		return [
			'NAME' => $parameters->fields->name,
			'SEMANTICS' => $semantics,
			'SORT' => $parameters->fields->sort,
			'COLOR' => $parameters->fields->color,
			'CATEGORY_ID' => $parameters->categoryId,
		];
	}
}
