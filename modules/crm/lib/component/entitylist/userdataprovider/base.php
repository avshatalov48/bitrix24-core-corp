<?php

namespace Bitrix\Crm\Component\EntityList\UserDataProvider;

use Bitrix\Main\NotSupportedException;
use CCrmOwnerType;

abstract class Base
{
	protected const FIELD_FORMATTED_NAME = 'FORMATTED_NAME';
	protected const FIELD_SHOW_URL = 'SHOW_URL';
	private const SUPPORTED_TYPES = [
		CCrmOwnerType::Lead,
		CCrmOwnerType::Deal,
		CCrmOwnerType::Contact,
		CCrmOwnerType::Company,
	];

	protected int $entityTypeId;
	protected array $addToResultFields = [];

	abstract public function prepareSelect(array &$select): void;
	abstract public function appendResult(array &$entities): void;
	abstract protected function fillEntities(array $userIds, array &$entities, array $params = []): void;

	public function __construct(int $entityTypeId)
	{
		if (!in_array($entityTypeId, self::SUPPORTED_TYPES, true))
		{
			throw new NotSupportedException(CCrmOwnerType::ResolveName($entityTypeId) . 'is not supported entity');
		}

		$this->entityTypeId = $entityTypeId;
	}

	protected function prepareUserData(array $userData, bool $includeUserId = false): array
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

					if ($includeUserId)
					{
						$result[$userId][$fieldId][$fieldId . '_' . 'ID'] = $userId;
					}
				}
			}
		}

		return $result;
	}
}
