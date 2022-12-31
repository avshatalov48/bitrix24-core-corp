<?php

namespace Bitrix\Crm\Controller\Activity;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

class Binding extends Base
{
	/**
	 * @var \CCrmActivity
	 */
	protected $activityEntity;

	protected function init(): void
	{
		parent::init();
		$this->activityEntity = new \CCrmActivity();
	}

	/**
	 * Get list of activity bindings.
	 *
	 * @param int $activityId
	 * @return array|null
	 */
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

	/**
	 * Add new activity binding to an entity.
	 *
	 * @param int $activityId
	 * @param int $entityTypeId
	 * @param int $entityId
	 * @return bool|null
	 */
	public function addAction(int $activityId, int $entityTypeId, int $entityId): ?bool
	{
		if (!$this->doesActivityExist($activityId))
		{
			return null;
		}

		if (!$this->canEdit($entityTypeId, $entityId))
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return null;
		}
		$bindings = $this->getExistingBindings($activityId);
		if (is_null($bindings))
		{
			return null;
		}

		foreach ($bindings as $binding)
		{
			if (
				(int)$binding['OWNER_TYPE_ID'] === $entityTypeId
				&& (int)$binding['OWNER_ID'] === $entityId
			)
			{
				$this->addError(new Error(
					Loc::getMessage('CRM_ACTIVITY_BINDING_ALREADY_BOUND_ERROR'),
					'ACTIVITY_IS_ALREADY_BOUND'
				));

				return null;
			}
		}
		$bindings[] = [
			'OWNER_TYPE_ID' => $entityTypeId,
			'OWNER_ID' => $entityId,
		];

		return $this->updateBindings($activityId, $bindings);
	}

	/**
	 * Remove activity binding to an entity.
	 *
	 * @param int $activityId
	 * @param int $entityTypeId
	 * @param int $entityId
	 * @return bool|null
	 */
	public function deleteAction(int $activityId, int $entityTypeId, int $entityId): ?bool
	{
		if (!$this->doesActivityExist($activityId))
		{
			return null;
		}

		if (!$this->canEdit($entityTypeId, $entityId))
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return null;
		}

		$bindings = $this->getExistingBindings($activityId);
		if (is_null($bindings))
		{
			return null;
		}

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
		)->Fetch();
		if (!$activity)
		{
			$this->addError(new Error(
					Loc::getMessage('CRM_TYPE_ITEM_NOT_FOUND'),
					ErrorCode::NOT_FOUND
			));
		}

		return !!$activity;
	}

	protected function canView(int $entityTpeId, int $entityId): bool
	{
		return \Bitrix\Crm\Security\EntityAuthorization::checkReadPermission($entityTpeId, $entityId);
	}

	protected function canEdit(int $entityTpeId, int $entityId): bool
	{
		return \Bitrix\Crm\Security\EntityAuthorization::checkUpdatePermission($entityTpeId, $entityId);
	}

	protected function addLastActivityError(): void
	{
		$this->addError(new Error(
			$this->activityEntity::GetLastErrorMessage(),
		));
	}

	protected function updateBindings(int $activityId, array $bindings): ?bool
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
			]
		);
		if (!$result)
		{
			$this->addLastActivityError();

			return null;
		}

		return true;
	}

	protected function getExistingBindings(int $activityId): ?array
	{

		$bindings = $this->activityEntity::GetBindings($activityId);
		if ($this->activityEntity::GetErrorCount())
		{
			$this->addError(new Error(
				$this->activityEntity::GetLastErrorMessage()
			));

			return null;
		}
		if (!is_array($bindings))
		{
			return null;
		}

		return $bindings;
	}

}
