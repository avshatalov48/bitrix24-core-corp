<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Commands\DTO;

class UserGroupsData
{
	public function __construct(
		public readonly ?int $id,
		public readonly ?string $title,
		public ?string $groupCode,
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
	public static function makeFromArray(array $userGroups, ?string $groupCode): array
	{
		$result = [];

		foreach ($userGroups as $userGroup)
		{
			$resultAccessCodes = [];
			foreach ($userGroup['accessCodes'] as $code => $type)
			{
				$resultAccessCodes[] = new AccessCodeDTO($code, $type);
			}

			/** @var Array<string, AccessRightDTO> $resultAccessRights */
			$resultAccessRights = [];
			foreach ($userGroup['accessRights'] as $right)
			{
				if (!isset($resultAccessRights[$right['id']]))
				{
					$resultAccessRights[$right['id']] = new AccessRightDTO($right['id'], $right['value']);

					continue;
				}

				// it's multivariables, we have several right items with same id

				$previousDto = $resultAccessRights[$right['id']];
				$previousValues = (array)$previousDto->value;

				$resultAccessRights[$right['id']] = new AccessRightDTO(
					$previousDto->id,
					[
						...$previousValues,
						$right['value'],
					],
				);
			}

			$result[] = new UserGroupsData(
				$userGroup['id'] ?? null,
				$userGroup['title'] ?? null,
				$groupCode,
				$userGroup['type'] ?? null,
				$resultAccessCodes,
				array_values($resultAccessRights),
			);
		}

		return $result;
	}

	public function isNew(): bool
	{
		return $this->id === 0;
	}
}
