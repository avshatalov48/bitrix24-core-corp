<?php

namespace Bitrix\Crm\Controller\Activity;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use CCrmActivity;

class Binding extends Base
{
	protected CCrmActivity $activityEntity;
	protected UserPermissions $userPermissions;

	protected function init(): void
	{
		parent::init();

		$this->activityEntity = new CCrmActivity();
		$this->userPermissions = Container::getInstance()->getUserPermissions();
	}

	public function listAction(int $activityId): ?array
	{
		if (!$this->doesActivityExist($activityId))
		{
			return null;
		}
		$bindings = $this->getExistingBindings($activityId);
		if (is_null($bindings))
		{
			return null;
		}

		$result = [];
		foreach ($bindings as $binding)
		{
			$entityTypeId = (int)$binding['OWNER_TYPE_ID'];
			$entityId = (int)$binding['OWNER_ID'];
			if ($this->canView($entityTypeId, $entityId))
			{
				$result[] = [
					'entityTypeId' => $entityTypeId,
					'entityId' => $entityId,
				];
			}
		}

		return $result;
	}

	public function addAction(int $activityId, int $entityTypeId, int $entityId): ?bool
	{
		if (!$this->doesActivityExist($activityId))
		{
			return null;
		}

		if (!$this->canEdit($entityTypeId, $entityId))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}
		$bindings = $this->getExistingBindings($activityId);
		if (is_null($bindings))
		{
			return null;
		}

		$bindings = $this->addBinding($bindings, $entityTypeId, $entityId);
		if (is_null($bindings))
		{
			$this->addError(
				new Error(
					Loc::getMessage('CRM_ACTIVITY_BINDING_ALREADY_BOUND_ERROR'),
					'ACTIVITY_IS_ALREADY_BOUND'
				)
			);

			return null;
		}

		return $this->updateBindings($activityId, $bindings);
	}

	public function deleteAction(int $activityId, int $entityTypeId, int $entityId): ?bool
	{
		if (!$this->doesActivityExist($activityId))
		{
			return null;
		}

		if (!$this->canEdit($entityTypeId, $entityId))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		$bindings = $this->getExistingBindings($activityId);
		if (is_null($bindings))
		{
			return null;
		}

		$bindings = $this->removeBinding($bindings, $entityTypeId, $entityId);
		if (is_null($bindings))
		{
			$this->addError(new Error(
				Loc::getMessage('CRM_ACTIVITY_BINDING_NOT_BOUND_ERROR'),
				'BINDING_NOT_FOUND'
			));

			return null;
		}

		if (!count($bindings))
		{
			$this->addError(new Error(
				Loc::getMessage('CRM_ACTIVITY_BINDING_LAST_BINDING_ERROR'),
				'LAST_BINDING_CANNOT_BE_DELETED'
			));

			return null;
		}

		return $this->updateBindings($activityId, $bindings);
	}

	public function moveAction(
		int $activityId,
		int $sourceEntityTypeId,
		int $sourceEntityId,
		int $targetEntityTypeId,
		int $targetEntityId
	): ?bool
	{
		if (!$this->doesActivityExist($activityId))
		{
			return null;
		}

		if (
			!$this->canEdit($sourceEntityTypeId, $sourceEntityId)
			|| !$this->canEdit($targetEntityTypeId, $targetEntityId)
		)
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		if ($sourceEntityId === $targetEntityId)
		{
			$this->addError(new Error('Source and target entity ID cannot be equal'));

			return null;
		}

		if ($sourceEntityTypeId !== $targetEntityTypeId)
		{
			$this->addError(new Error('Source and target entity types are not equal'));

			return null;
		}

		$bindings = $this->getExistingBindings($activityId);
		if (is_null($bindings))
		{
			return null;
		}

		$bindings = $this->addBinding($bindings, $targetEntityTypeId, $targetEntityId);
		if (is_null($bindings))
		{
			$this->addError(
				new Error(
					Loc::getMessage('CRM_ACTIVITY_BINDING_ALREADY_BOUND_ERROR'),
					'ACTIVITY_IS_ALREADY_BOUND'
				)
			);

			return null;
		}

		$bindings = $this->removeBinding($bindings, $sourceEntityTypeId, $sourceEntityId);
		if (is_null($bindings))
		{
			$this->addError(
				new Error(
					Loc::getMessage('CRM_ACTIVITY_BINDING_NOT_BOUND_ERROR'),
					'BINDING_NOT_FOUND'
				)
			);

			return null;
		}

		return $this->updateBindings(
			$activityId,
			$bindings,
			[
				'source' => new ItemIdentifier($sourceEntityTypeId, $sourceEntityId),
				'target' => new ItemIdentifier($targetEntityTypeId, $targetEntityId),
			]
		);
	}

	protected function doesActivityExist(int $activityId): bool
	{
		$activity = $this->activityEntity::GetList(
			[],
			[
				'ID' => $activityId,
				'CHECK_PERMISSIONS' => 'N'
			],
			false,
			false,
			[
				'ID'
			]
		)?->Fetch();
		if (!$activity)
		{
			$this->addError(
				new Error(
					Loc::getMessage('CRM_TYPE_ITEM_NOT_FOUND'),
					ErrorCode::NOT_FOUND
				)
			);
		}

		return (bool)$activity;
	}

	protected function canView(int $entityTpeId, int $entityId): bool
	{
		return $this->userPermissions->checkReadPermissions($entityTpeId, $entityId);
	}

	protected function canEdit(int $entityTpeId, int $entityId): bool
	{
		return $this->userPermissions->checkUpdatePermissions($entityTpeId, $entityId);
	}

	protected function updateBindings(int $activityId, array $bindings, array $moveBindingsMap = []): ?bool
	{
		$result = $this->activityEntity::Update(
			$activityId,
			[
				'BINDINGS' => array_values($bindings),
			],
			true,
			false,
			[
				'SKIP_CALENDAR_EVENT' => true,
				'REGISTER_SONET_EVENT' => false,
				'MOVE_BINDINGS_MAP' => $moveBindingsMap,
			]
		);
		if (!$result)
		{
			$this->addError(new Error($this->activityEntity::GetLastErrorMessage()));

			return null;
		}

		return true;
	}

	protected function getExistingBindings(int $activityId): ?array
	{
		$bindings = $this->activityEntity::GetBindings($activityId);
		if ($this->activityEntity::GetErrorCount())
		{
			$this->addError(new Error($this->activityEntity::GetLastErrorMessage()));

			return null;
		}
		if (!is_array($bindings))
		{
			return null;
		}

		return $bindings;
	}

	private function addBinding(array $bindings, int $entityTypeId, int $entityId): ?array
	{
		foreach ($bindings as $binding)
		{
			if (
				(int)$binding['OWNER_TYPE_ID'] === $entityTypeId
				&& (int)$binding['OWNER_ID'] === $entityId
			)
			{
				return null;
			}
		}

		$bindings[] = [
			'OWNER_TYPE_ID' => $entityTypeId,
			'OWNER_ID' => $entityId,
		];

		return $bindings;
	}

	private function removeBinding(array $bindings, int $entityTypeId, int $entityId): ?array
	{
		$bindingFound = false;
		foreach ($bindings as $iterator => $binding)
		{
			if (
				(int)$binding['OWNER_TYPE_ID'] === $entityTypeId
				&& (int)$binding['OWNER_ID'] === $entityId
			)
			{
				unset($bindings[$iterator]);
				$bindingFound = true;

				break;
			}
		}
		if (!$bindingFound)
		{
			return null;
		}

		return $bindings;
	}
}
