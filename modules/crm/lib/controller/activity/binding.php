<?php

namespace Bitrix\Crm\Controller\Activity;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

class Binding extends Base
{
	/**
	 * Get list of activity bindings.
	 *
	 * @param int $activityId
	 * @return array|null
	 */
	public function listAction(int $activityId): ?array
	{
		if (!$this->doesActivityExists($activityId))
		{
			return null;
		}
		$bindings = $this->getExistedBindings($activityId);
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
		if (!$this->doesActivityExists($activityId))
		{
			return null;
		}

		if (!$this->canEdit($entityTypeId, $entityId))
		{
			$this->addError(new Error(
				Loc::getMessage('CRM_COMMON_ERROR_ACCESS_DENIED'),
				ErrorCode::ACCESS_DENIED
			));

			return null;
		}
		$bindings = $this->getExistedBindings($activityId);
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
					'Activity is already bound to this entity'
				));

				return null;
			}
		}
		$bindings[] = [
			'OWNER_TYPE_ID' => $entityTypeId,
			'OWNER_ID' => $entityId,
		];

		return $this->setBindings($activityId, $bindings);
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
		if (!$this->doesActivityExists($activityId))
		{
			return null;
		}

		if (!$this->canEdit($entityTypeId, $entityId))
		{
			$this->addError(new Error(
				Loc::getMessage('CRM_COMMON_ERROR_ACCESS_DENIED'),
				ErrorCode::ACCESS_DENIED
			));

			return null;
		}

		$bindings = $this->getExistedBindings($activityId);
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
				'Activity is not bound to this entity'
			));

			return null;
		}
		if (!count($bindings))
		{
			$this->addError(new Error(
				'Last binding cannot be deleted'
			));

			return null;
		}

		return $this->setBindings($activityId, $bindings);
	}

	protected function doesActivityExists(int $activityId): bool
	{
		$activity = \CCrmActivity::GetList(
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
					ErrorCode::NOT_FOUND)
			);
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
			\CCrmActivity::GetLastErrorMessage()
		));
	}

	protected function setBindings(int $activityId, array $bindings): ?bool
	{
		$result = \CCrmActivity::Update(
			$activityId,
			[
				'BINDINGS' => $bindings,
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

	protected function getExistedBindings(int $activityId): ?array
	{

		$bindings = \CCrmActivity::GetBindings($activityId);
		if (\CCrmActivity::GetErrorCount())
		{
			$this->addError(new Error(
				\CCrmActivity::GetLastErrorMessage()
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
