<?php

namespace Bitrix\Crm\EntityForm;

use Bitrix\Crm\Entity\EntityEditorOptionParser;
use Bitrix\Crm\Service\Container;
use Bitrix\Ui\EntityForm\Scope;

class ScopeAccess extends \Bitrix\Ui\EntityForm\ScopeAccess
{
	protected Scope $scope;
	protected EntityEditorOptionParser $editorOptionParser;

	public function __construct(string $moduleId = null, int $userId = null)
	{
		parent::__construct($moduleId, $userId);

		$this->scope = Scope::getInstance($this->userId);
		$this->editorOptionParser = new EntityEditorOptionParser();
	}

	/**
	 * @param int $scopeId
	 * @return bool
	 */
	public function canRead(int $scopeId): bool
	{
		$scope = $this->scope->getById($scopeId);

		return (
			(isset($scope) && $this->canAddByEntityTypeId($scope['ENTITY_TYPE_ID']))
			|| $this->scope->isHasScope($scopeId)
		);
	}

	/**
	 * @return bool
	 */
	public function canAdd(): bool
	{
		return Container::getInstance()->getUserPermissions($this->userId)->isCrmAdmin();
	}

	public function canAddByEntityTypeId(string $entityTypeId): bool
	{
		return $this->isAdminForEntityTypeId($entityTypeId);
	}

	/**
	 * @param int $scopeId
	 * @return bool
	 */
	public function canUpdate(int $scopeId): bool
	{
		$scope = $this->scope->getById($scopeId);
		return (isset($scope) && $this->canAddByEntityTypeId($scope['ENTITY_TYPE_ID']) && $scope['CATEGORY'] === $this->moduleId);
	}

	/**
	 * @param array|int $scopeIds
	 * @return bool
	 */
	public function canDelete($scopeIds): bool
	{
		if (!is_array($scopeIds))
		{
			$scopeIds = [$scopeIds];
		}

		foreach ($scopeIds as $scopeId)
		{
			if (!$this->canUpdate($scopeId))
			{
				return false;
			}
		}

		return true;
	}

	public function isAdmin(): bool
	{
		return Container::getInstance()->getUserPermissions($this->userId)->isAdmin();
	}

	public function isAdminForEntityTypeId(string $entityTypeId): bool
	{
		$parseResult = $this->editorOptionParser->parse($entityTypeId);
		if (!$parseResult->isEntityTypeIdFound())
		{
			return false;
		}

		return Container::getInstance()
			->getUserPermissions($this->userId)
			->isAdminForEntity($parseResult->entityTypeId(), $parseResult->categoryId());
	}
}
