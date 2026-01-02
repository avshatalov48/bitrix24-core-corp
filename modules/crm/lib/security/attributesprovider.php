<?php

namespace Bitrix\Crm\Security;

use Bitrix\Crm\Integration\HumanResources\DepartmentQueries;

class AttributesProvider
{
	protected $userId;
	protected ?array $userAttributes = null;
	protected $userAttributesCodes;
	protected $entityAttributes;

	private const CACHE_TIME = 8640000; // 100 days

	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function getUserAttributes(): array
	{
		if (!$this->userAttributes)
		{
			$this->userAttributes = $this->loadUserAttributes();
		}

		return $this->userAttributes;
	}

	public function getUserAttributesCodes(): array
	{
		if (!$this->userAttributesCodes)
		{
			$this->userAttributesCodes = $this->loadUserAccessCodes();
		}

		return $this->userAttributesCodes;
	}

	public function getEntityAttributes(): array
	{
		if (!$this->entityAttributes)
		{
			$this->entityAttributes = $this->loadEntityAttributes();
		}

		return $this->entityAttributes;
	}

	protected function loadUserAttributes(): array
	{
		$attributesByUser = [];

		$userAccessCodes = $this->getUserAccessCodes();
		foreach ($userAccessCodes as $accessCode)
		{
			if (mb_strpos($accessCode['ACCESS_CODE'], 'DR') !== 0)
			{
				$attributesByUser[mb_strtoupper($accessCode['PROVIDER_ID'])][] = $accessCode['ACCESS_CODE'];
			}
		}

		if (!empty($attributesByUser['INTRANET']))
		{
			foreach ($attributesByUser['INTRANET'] as $iDepartment)
			{
				if (mb_substr($iDepartment, 0, 1) === 'D')
				{
					$departmentTree = $this->getSubDepartmentsIds((int)mb_substr($iDepartment, 1));
					foreach ($departmentTree as $departmentId)
					{
						$attributesByUser['SUBINTRANET'][] = 'D' . $departmentId;
					}
				}
			}
		}

		return $attributesByUser;
	}

	protected function loadEntityAttributes(): array
	{
		$result = [
			'INTRANET' => [],
		];
		$userAttributes = $this->getUserAttributes();
		if (!empty($userAttributes['INTRANET']))
		{
			//HACK: Removing intranet subordination relations, otherwise staff will get access to boss's entities
			foreach ($userAttributes['INTRANET'] as $code)
			{
				if (mb_strpos($code, 'IU') !== 0)
				{
					$result['INTRANET'][] = $code;
				}
			}
			$userId = $this->getUserId();
			$result['INTRANET'][] = "IU{$userId}";
		}

		return $result;
	}

	protected function getUserAccessCodes(): array
	{
		$userId = $this->getUserId();

		$cache = \Bitrix\Main\Application::getInstance()->getCache();

		$cacheId = 'crm_user_access_codes_' . $userId . '_' . md5(serialize($this->getUserAttributesCodes()));

		if ($cache->initCache(self::CACHE_TIME, $cacheId, '/crm/user_access_codes/'))
		{
			$result = $cache->getVars();
		}
		else
		{
			$cache->startDataCache();
			$result = [];
			$userAccessCodes = \CAccess::GetUserCodes($this->getUserId());
			while ($accessCode = $userAccessCodes->Fetch())
			{
				// imchat generates too much useless codes. Skip them:
				if ($accessCode['PROVIDER_ID'] !== 'imchat')
				{
					$result[] = $accessCode;
				}
			}
			$cache->endDataCache($result);
		}

		return $result;
	}

	protected function loadUserAccessCodes(): array
	{
		$userId = $this->getUserId();

		$access = new \CAccess();
		$access->UpdateCodes(['USER_ID' => $userId]);
		$userAccessCodes = $access->GetUserCodesArray($userId);

		$usefulUserAccessCodes = [];
		foreach ($userAccessCodes as $code)
		{
			if (mb_substr($code, 0, 4) !== 'CHAT') // code started from "CHAT" is useless
			{
				$usefulUserAccessCodes[] = $code;
			}
		}

		return $usefulUserAccessCodes;
	}

	protected function getSubDepartmentsIds($departmentId): array
	{
		return DepartmentQueries::getInstance()->getSubDepartmentsAccessCodesIds($departmentId);
	}
}
