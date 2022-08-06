<?php

namespace Bitrix\Crm\Integrity;

class DedupeConfig
{
	public const OPTION_KEY = 'crm.dedupe.wizard';

	/** @var $userId int */
	protected $userId;

	public function __construct(int $userId = null)
	{
		$this->userId = $userId ?: \CCrmSecurityHelper::GetCurrentUser()->GetID();
	}

	public function save(string $guid, array $config): void
	{
		\CUserOptions::SetOption(self::OPTION_KEY, $guid, $config, false, $this->userId);
	}

	public function get(string $guid, int $entityTypeId): array
	{
		$config = array(
			'scope' => DuplicateIndexType::DEFAULT_SCOPE,
			'typeNames' => [],
			'typeIDs' => []
		);

		$savedConfig = \CUserOptions::GetOption(self::OPTION_KEY, $guid, null, $this->userId);
		if ($savedConfig)
		{
			$types = $savedConfig['typeNames'] ?? [];
			foreach ($types as $typeName)
			{
				$typeID = DuplicateIndexType::resolveID($typeName);
				if ($typeID !== DuplicateIndexType::UNDEFINED)
				{
					$config['typeNames'][] = $typeName;
					$config['typeIDs'][] = $typeID;
				}
			}
			if (DuplicateIndexType::checkScopeValue($savedConfig['scope']))
			{
				$config['scope'] = $savedConfig['scope'];
			}
		}
		else
		{
			if ($entityTypeId === \CCrmOwnerType::Contact || $entityTypeId === \CCrmOwnerType::Lead)
			{
				$config['typeNames'][] = DuplicateIndexType::PERSON_NAME;
				$config['typeIDs'][] = DuplicateIndexType::PERSON;
			}
			if ($entityTypeId === \CCrmOwnerType::Company)
			{
				$config['typeNames'][] = DuplicateIndexType::ORGANIZATION_NAME;
				$config['typeIDs'][] = DuplicateIndexType::ORGANIZATION;
			}

			$config['typeNames'][] = DuplicateIndexType::COMMUNICATION_PHONE_NAME;
			$config['typeNames'][] = DuplicateIndexType::COMMUNICATION_EMAIL_NAME;
			$config['typeIDs'][] = DuplicateIndexType::COMMUNICATION_PHONE;
			$config['typeIDs'][] = DuplicateIndexType::COMMUNICATION_EMAIL;
		}
		return $config;
	}
}