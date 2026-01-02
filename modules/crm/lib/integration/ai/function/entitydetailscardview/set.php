<?php

namespace Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView;

use Bitrix\Crm\Entity\EntityEditorConfig;
use Bitrix\Crm\Entity\EntityEditorConfigScope;
use Bitrix\Crm\Entity\EntityEditorOptionBuilder;
use Bitrix\Crm\Integration\AI\Contract\AIFunction;
use Bitrix\Crm\Result;
use Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Dto\SetParameters;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Ui\EntityForm\Scope;

final class Set implements AIFunction
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

	public function invoke(...$args): Result
	{
		$parameters = new SetParameters($args);
		if ($parameters->hasValidationErrors())
		{
			return Result::fail($parameters->getValidationErrors());
		}

		if (!Loader::includeModule('ui'))
		{
			return Result::failModuleNotInstalled('ui');
		}

		$category = EntityEditorConfig::CATEGORY_NAME;
		$guid = (new EntityEditorOptionBuilder($parameters->entityTypeId))
			->setCategoryId($parameters->categoryId)
			->build();

		if (empty($guid))
		{
			return Result::failEntityTypeNotSupported($parameters->entityTypeId);
		}

		if (!$this->isCustomScopeExists($parameters))
		{
			return Result::fail(Loc::getMessage('CRM_INTEGRATION_AI_FUNCTION_ENTITY_DETAILS_CARD_VIEW_CUSTOM_SCOPE_NOT_FOUND_ERROR'), 'CUSTOM_SCOPE_NOT_FOUND');
		}

		foreach ($this->getUserIds($parameters) as $userId)
		{
			Scope::getInstance()
				->setScope(
					$category,
					$guid,
					$parameters->scope,
					$parameters->customScopeId ?? 0,
					$userId,
				);
		}

		$this->tryFillUserPermissionsForConfig($parameters);

		return Result::success();
	}

	private function tryFillUserPermissionsForConfig(SetParameters $parameters): void
	{
		if (
			$parameters->scope !== EntityEditorConfigScope::CUSTOM
			|| !$this->canSetConfigForOtherUsers($parameters)
		)
		{
			return;
		}

		$userAccessCodes = $this->buildUserAccessCodes($parameters->userIds);
		$configAccessCodes = Scope::getInstance()->getScopeAccessCodesByScopeId($parameters->customScopeId);
		$toAdd = array_diff($userAccessCodes, $configAccessCodes);

		Scope::getInstance()->addAccessCodes($parameters->customScopeId, $toAdd);
	}

	private function getUserIds(SetParameters $parameters): array
	{
		$userIds = [$this->currentUserId];
		if ($this->canSetConfigForOtherUsers($parameters))
		{
			$userIds = array_merge($userIds, $parameters->userIds);
		}

		return $userIds;
	}

	private function buildUserAccessCodes(array $userIds): array
	{
		$accessCodes = [];
		foreach ($userIds as $userId)
		{
			$accessCodes[UserPermissions::ATTRIBUTES_USER_PREFIX . $userId] = 'USERS';
		}

		return $accessCodes;
	}

	private function canSetConfigForOtherUsers(SetParameters $parameters): bool
	{
		return !empty($parameters->userIds)
			&& $this->permissions->isAdminForEntity($parameters->entityTypeId, $parameters->categoryId);
	}

	private function isCustomScopeExists(SetParameters $parameters): bool
	{
		if ($parameters->scope !== EntityEditorConfigScope::CUSTOM)
		{
			return true;
		}

		return Scope::getInstance($this->currentUserId)
			->isHasScope($parameters->customScopeId ?? 0);
	}
}
