<?php

namespace Bitrix\Crm\Component\EntityList;

use Bitrix\Crm\Service\Container;

class UserDataProvider
{
	protected $addToResultFields = [];

	/**
	 * Remove joined user fields like CREATED_BY_LOGIN form $select
	 * and store them for following usage
	 *
	 * @param array $select
	 */
	public function prepareSelect(array &$select): void
	{
		$suffixes = $this->getFieldSuffixes();

		foreach ($this->getUserIdFields() as $fieldId)
		{
			$needLoadField = false;
			foreach ($suffixes as $suffix)
			{
				$fieldIdWithSuffix = $fieldId . '_' . $suffix;
				$fieldIndex = array_search($fieldIdWithSuffix, $select, true);
				if ($fieldIndex !== false)
				{
					$this->addToResultFields[$fieldId][] = $suffix;
					unset($select[$fieldIndex]);
					$needLoadField = true;
				}
			}
			if ($needLoadField)
			{
				$this->addToResultFields[$fieldId][] = 'FORMATTED_NAME';
				$this->addToResultFields[$fieldId][] = 'SHOW_URL';

				if (!in_array($fieldId, $select))
				{
					$select[] = $fieldId;
				}
			}
		}
		foreach ($this->getClientUserIdFields() as $fieldId)
		{
			if (in_array($fieldId, $select))
			{
				$this->addToResultFields[$fieldId] = [
					'FORMATTED_NAME',
					'SHOW_URL',
				];
			}
		}
	}

	/**
	 * Add extra user data (LOGIN, NAME etc) to $deals
	 *
	 * @param array $deals
	 */
	public function appendResult(array &$deals): void
	{
		if (empty($this->addToResultFields))
		{
			return;
		}

		$userIds = $this->extractUserIds($deals);
		if (empty($userIds))
		{
			return;
		}

		$dealUserData = $this->prepareUserData(Container::getInstance()->getUserBroker()->getBunchByIds($userIds));
		foreach ($deals as $dealId => $deal)
		{
			foreach (array_keys($this->addToResultFields) as $fieldId)
			{
				$userId = (int)($deal[$fieldId] ?? 0);
				if (
					$userId > 0
					&& !empty($dealUserData[$userId][$fieldId])
				)
				{
					$deals[$dealId] = array_merge(
						$deals[$dealId],
						$dealUserData[$userId][$fieldId]
					);
				}
			}
		}
	}

	protected function getUserIdFields(): array
	{
		return [
			'CREATED_BY',
			'MODIFY_BY',
			'ASSIGNED_BY',
		];
	}

	protected function getClientUserIdFields(): array
	{
		return [
			'CONTACT_CREATED_BY_ID',
			'CONTACT_MODIFY_BY_ID',
			'CONTACT_ASSIGNED_BY_ID',
			'COMPANY_CREATED_BY_ID',
			'COMPANY_MODIFY_BY_ID',
			'COMPANY_ASSIGNED_BY_ID',
		];
	}

	protected function extractUserIds(array $deals): array
	{
		$result = [];

		foreach ($deals as $deal)
		{
			foreach (array_keys($this->addToResultFields) as $fieldId)
			{
				if (
					isset($deal[$fieldId])
					&& (int)$deal[$fieldId] > 0
					&& !in_array((int)$deal[$fieldId], $result)
				)
				{
					$result[] = (int)$deal[$fieldId];
				}
			}
		}

		return $result;
	}

	protected function getFieldSuffixes(): array
	{
		return [
			'LOGIN',
			'NAME',
			'LAST_NAME',
			'SECOND_NAME',
		];
	}

	protected function prepareUserData(array $userData): array
	{
		$result = [];

		foreach ($userData as $userId => $user)
		{
			$result[$userId] = [];
			foreach ($this->addToResultFields as $fieldId => $suffixes)
			{
				$result[$userId][$fieldId] = [];
				foreach ($suffixes as $suffix)
				{
					$fieldIdWithSuffix = $fieldId . '_' . $suffix;

					$value = (string)($user[$suffix] ?? '');
					$result[$userId][$fieldId]['~' . $fieldIdWithSuffix] = $value;
					$result[$userId][$fieldId][$fieldIdWithSuffix] = htmlspecialcharsbx($value);
				}
			}
		}

		return $result;
	}
}
