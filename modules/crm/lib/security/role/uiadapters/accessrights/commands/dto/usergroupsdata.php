<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Commands\DTO;

class UserGroupsData
{
	public function __construct(
		public readonly ?int $id,
		public readonly ?string $title,
		public readonly ?string $type,
		/** @var AccessCodeDTO[] */
		public readonly ?array $accessCodes,
		/** @var AccessRightDTO[] */
		public readonly ?array $accessRights,
	)
	{
	}

	/**
	 * @param array $userGroups
	 * @return UserGroupsData[]
	 */
	public static function makeFromArray(array $userGroups): array
	{
		$result = [];

		foreach ($userGroups as $userGroup)
		{
			$resultAccessCodes = [];
			foreach ($userGroup['accessCodes'] as $code => $type)
			{
				$resultAccessCodes[] = new AccessCodeDTO($code, $type);
			}

			$resultAccessRights = [];
			foreach ($userGroup['accessRights'] as $right)
			{
				$resultAccessRights[] = new AccessRightDTO($right['id'], $right['value']);
			}

			$result[] = new UserGroupsData(
				$userGroup['id'] ?? null,
				$userGroup['title'] ?? null,
				$userGroup['type'] ?? null,
					$resultAccessCodes,
					$resultAccessRights,
			);
		}

		return $result;
	}
}