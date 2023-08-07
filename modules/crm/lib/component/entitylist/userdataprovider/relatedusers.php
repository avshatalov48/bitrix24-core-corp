<?php

namespace Bitrix\Crm\Component\EntityList\UserDataProvider;

use Bitrix\Crm\Service\Container;

final class RelatedUsers extends Base
{
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
				$this->addToResultFields[$fieldId][] = self::FIELD_FORMATTED_NAME;
				$this->addToResultFields[$fieldId][] = self::FIELD_SHOW_URL;

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
					self::FIELD_FORMATTED_NAME,
					self::FIELD_SHOW_URL,
				];
			}
		}
	}

	/**
	 * Add extra user data (LOGIN, NAME etc) to $entities
	 *
	 * @param array $entities
	 */
	public function appendResult(array &$entities): void
	{
		if (empty($this->addToResultFields))
		{
			return;
		}

		$userIds = $this->extractUserIds($entities);
		if (empty($userIds))
		{
			return;
		}

		$this->fillEntities($userIds, $entities);
	}

	protected function fillEntities(array $userIds, array &$entities, array $params = []): void
	{
		$userData = $this->prepareUserData(Container::getInstance()->getUserBroker()->getBunchByIds($userIds));

		foreach ($entities as $entityId => $entity)
		{
			foreach (array_keys($this->addToResultFields) as $fieldId)
			{
				$userId = (int)($entity[$fieldId] ?? 0);
				if (
					$userId > 0
					&& !empty($userData[$userId][$fieldId])
				)
				{
					$entities[$entityId] = array_merge(
						$entities[$entityId],
						$userData[$userId][$fieldId]
					);
				}
			}
		}
	}

	private function getUserIdFields(): array
	{
		return [
			'CREATED_BY',
			'MODIFY_BY',
			'ASSIGNED_BY',
		];
	}

	private function getClientUserIdFields(): array
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

	private function extractUserIds(array $entities): array
	{
		$result = [];

		foreach ($entities as $entity)
		{
			foreach (array_keys($this->addToResultFields) as $fieldId)
			{
				if (
					isset($entity[$fieldId])
					&& (int)$entity[$fieldId] > 0
					&& !in_array((int)$entity[$fieldId], $result)
				)
				{
					$result[] = (int)$entity[$fieldId];
				}
			}
		}

		return $result;
	}

	private function getFieldSuffixes(): array
	{
		return [
			'LOGIN',
			'NAME',
			'LAST_NAME',
			'SECOND_NAME',
		];
	}
}
