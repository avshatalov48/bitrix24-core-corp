<?php

namespace Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView;

use Bitrix\Crm\Entity\EntityEditorConfigScope;
use Bitrix\Crm\Entity\EntityEditorOptionBuilder;
use Bitrix\Crm\Integration\AI\Contract\AIFunction;
use Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Dto\CreateParameters;
use Bitrix\Crm\Integration\UI\EntityEditor\Configuration;
use Bitrix\Crm\Integration\UI\EntityEditor\Enum\MarkTarget;
use Bitrix\Crm\Integration\UI\EntityEditor\MartaAIMarksRepository;
use Bitrix\Crm\Result;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Ui\EntityForm\Scope;

final class Create implements AIFunction
{
	private const SCOPE_CATEGORY = 'crm';

	private readonly UserPermissions $permissions;

	public function __construct(private readonly int $currentUserId)
	{
		$this->permissions = Container::getInstance()->getUserPermissions($this->currentUserId);
	}

	public function isAvailable(): bool
	{
		return true;
	}

	public function invoke(...$args): Result
	{
		$parameters = new CreateParameters($args);
		if ($parameters->hasValidationErrors())
		{
			return Result::fail($parameters->getValidationErrors());
		}

		if (!Loader::includeModule('ui'))
		{
			return Result::failModuleNotInstalled('ui');
		}

		if (!$this->permissions->isAdminForEntity($parameters->entityTypeId, $parameters->categoryId))
		{
			return Result::failAccessDenied();
		}

		$guid = $this->getGuid($parameters);
		if (empty($guid))
		{
			return Result::failEntityTypeNotSupported($parameters->entityTypeId);
		}

		$config = $parameters->configuration();

		$userScopeIdOrErrors = Scope::getInstance()
			->setScopeConfig(
				self::SCOPE_CATEGORY,
				$guid,
				$parameters->title,
				$this->getUserAccessCodes($parameters->userIds),
				$config,
				$parameters->options(),
			);

		if (is_array($userScopeIdOrErrors))
		{
			return Result::fail(new ErrorCollection($userScopeIdOrErrors));
		}

		$configuration = Configuration::fromArray($config);
		$marksRepository = new MartaAIMarksRepository(
			$this->currentUserId,
			$guid,
			EntityEditorConfigScope::CUSTOM,
			$userScopeIdOrErrors,
		);

		$marksRepository
			->mark(MarkTarget::Section, $configuration->getSectionNames())
			->mark(MarkTarget::Field, $configuration->getElementNames());

		return Result::success(userScopeId: $userScopeIdOrErrors);
	}

	private function getGuid(CreateParameters $parameters): string
	{
		return (new EntityEditorOptionBuilder($parameters->entityTypeId))
			->setCategoryId($parameters->categoryId)
			->build();
	}

	private function getUserAccessCodes(array $userIds): array
	{
		$userIds[] = $this->currentUserId;
		$userIds = array_unique($userIds);

		$result = [];
		foreach ($userIds as $userId)
		{
			$result[] = [
				'id' => $userId,
				'entityId' => 'user', /* @see Scope::TYPE_USER */
			];
		}

		return $result;
	}
}
