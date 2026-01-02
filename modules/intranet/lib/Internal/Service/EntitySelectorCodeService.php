<?php

namespace Bitrix\Intranet\Internal\Service;

use Bitrix\Intranet\Dto\EntitySelector\EntitySelectorCodeDto;
use Bitrix\Intranet\Internal\Integration\Humanresources\DepartmentRepository;

class EntitySelectorCodeService
{
	public function isUserBelongsToEntitySelectorCode(
		int $userId,
		EntitySelectorCodeDto $codeDto,
	): bool
	{
		if ($codeDto->isAllUser)
		{
			return true;
		}

		if (in_array($userId, $codeDto->userIds))
		{
			return true;
		}

		$hrDepartment = new DepartmentRepository();
		$selectorDepartments = $hrDepartment->getDepartmentsByEntitySelectorAccessCode($codeDto);
		$userDepartments = $hrDepartment->getDepartmentsByUserId($userId);
		$intersectedDepartments = $selectorDepartments->intersect($userDepartments);

		return !$intersectedDepartments->empty();
	}

	public function createEntitySelectorCodeDtoFromCodeList(array $codes): EntitySelectorCodeDto
	{
		$userIds = $departmentIds = $departmentWithAllChildIds = [];
		$isAllUser = false;

		foreach($codes as $accessCode)
		{
			if ($accessCode === 'AU' || $accessCode === 'UA')
			{
				$isAllUser = true;
			}
			elseif (preg_match('/^IU(\d+)$/i', $accessCode, $matches))
			{
				$userIds[] = $matches[1];
			}
			elseif (preg_match('/^U(\d+)$/i', $accessCode, $matches))
			{
				$userIds[] = $matches[1];
			}
			elseif (preg_match('/^D(\d+)$/i', $accessCode, $matches))
			{
				$departmentIds[] = $matches[1];
			}
			elseif (preg_match('/^DR(\d+)$/i', $accessCode, $matches))
			{
				$departmentWithAllChildIds[] = $matches[1];
			}
		}

		return new EntitySelectorCodeDto(
			$isAllUser,
			$userIds,
			$departmentIds,
			$departmentWithAllChildIds,
		);
	}

	public function createCodeDtoFromSerializedString(string $serializedCodes): EntitySelectorCodeDto
	{
		$accessCodeList = unserialize($serializedCodes, ['allowed_classes' => false]);

		if (is_array($accessCodeList))
		{
			return static::createEntitySelectorCodeDtoFromCodeList($accessCodeList);
		}

		return new EntitySelectorCodeDto(
			isAllUser: false,
		);
	}
}
