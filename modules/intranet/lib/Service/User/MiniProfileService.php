<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Service\User;

use Bitrix\Intranet\Internal\Integration\Humanresources\TeamRepository;
use Bitrix\Intranet\Service;
use Bitrix\Intranet\Integration;
use Bitrix\Intranet\Enum\UserRole;
use Bitrix\Intranet\Dto\User\MiniProfile;
use Bitrix\Intranet\Result\Service\User\MiniProfileDataResult;

use Bitrix\Main;
use Bitrix\Main\EO_User;

class MiniProfileService
{
	private const AVATAR_SIZE = 100;
	private const DEPARTMENT_BRANCH_SIZE = 3;

	private Service\UserService $userService;
	private Integration\Im\ChatFacade $chatFacade;
	private Integration\HumanResources\Department $departmentService;
	private TeamRepository $teamService;

	public function __construct(
		Integration\Im\ChatFacade $chatFacade,
		Integration\HumanResources\Department $departmentService,
		TeamRepository $teamService,
		?Service\UserService $userService = null,
	)
	{
		$container = Service\ServiceContainer::getInstance();

		$this->chatFacade = $chatFacade;
		$this->departmentService = $departmentService;
		$this->teamService = $teamService;
		$this->userService = $userService ?? $container->getUserService();
	}

	public function getData(int $currentUserId, int $userId): MiniProfileDataResult | Main\Result
	{
		$userModel = $this->getUserModel($userId);
		if (!$userModel)
		{
			return $this->makeAccessDeniedResult();
		}

		if (!$this->checkAccess($currentUserId, $userId))
		{
			return $this->makeAccessDeniedResult();
		}

		$accessDto = $this->makeAccessDto($currentUserId, $userModel);
		$baseInfo = $this->makeBaseInfoDto($userModel);
		if ($this->showOnlyBaseInfo($currentUserId, $userModel))
		{
			return new MiniProfileDataResult(
				new MiniProfile\UserMiniProfileDto(
					baseInfo: $baseInfo,
					access: $accessDto,
				),
			);
		}

		$structureDto = $this->makeStructureDto($userId);
		if (!$structureDto)
		{
			return new MiniProfileDataResult(
				new MiniProfile\UserMiniProfileDto(
					baseInfo: $baseInfo,
					access: $accessDto,
				),
			);
		}

		return new MiniProfileDataResult(
			new MiniProfile\UserMiniProfileDto(
				baseInfo: $baseInfo,
				access: $accessDto,
				detailInfo: $this->makeDetailInfoDto($userModel),
				structure: $structureDto,
			),
		);
	}

	private function makeStructureDto(int $userId): ?MiniProfile\StructureDto
	{
		// Department
		$departmentBranchCollection = $this->departmentService->getUserDepartmentBranchCollection(
			userId: $userId,
			depth: self::DEPARTMENT_BRANCH_SIZE
		);
		if (!$departmentBranchCollection)
		{
			return null;
		}

		$userDepartmentIds = $departmentBranchCollection->getFromDepartmentIds();
		if (empty($userDepartmentIds))
		{
			return null;
		}

		$structureTitle = $departmentBranchCollection->getValues()[0]?->rootTitle;

		$nodeDictionary = $departmentBranchCollection->toNodeDictionary();
		$nodeIds = array_keys($nodeDictionary);
		$employeeCountByNodeId = $this->departmentService->getEmployeeCountByDepartmentIds($nodeIds);

		$headInfoByNodeId = $this->departmentService->getHeadDictionaryByNodeCollection(
			$departmentBranchCollection->toFlatNodeCollection()
		);

		/** @var array<int, MiniProfile\Structure\HeadDto> $headDictionaryDto */
		$headDictionaryDto = [];
		$headIdsByNodeId = [];
		foreach ($headInfoByNodeId as $nodeId => $heads)
		{
			$headIdsByNodeId[$nodeId] = [];
			foreach ($heads as $head)
			{
				$headId = $head['id'] ?? null;
				if (!$headId)
				{
					continue;
				}

				$headIdsByNodeId[$nodeId][] = $headId;

				$headDictionaryDto[$headId] ??= new MiniProfile\Structure\HeadDto(
					id: $head['id'],
					name: $head['name'],
					workPosition: $head['workPosition'],
					avatar: $head['avatar'],
					url: $this->userService->getDetailUrl($headId),
				);
			}
		}

		$departmentDictionaryDto = [];
		foreach ($nodeDictionary as $id => $node)
		{
			$departmentDictionaryDto[$id] = new MiniProfile\Structure\DepartmentDto(
				id: $node->id,
				title: $node->name,
				parentId: $node->parentId,
				employeeCount: $employeeCountByNodeId[$id] ?? 0,
				headIds: $headIdsByNodeId[$id] ?? [],
			);
		}

		// Team
		$teamCollection = $this->teamService->getAllByUserId($userId);
		$teamDtoList = [];

		if ($teamCollection)
		{
			foreach ($teamCollection as $team)
			{
				$teamDtoList[] = new MiniProfile\Structure\TeamDto(
					id: $team->id,
					title: $team->name,
				);
			}
		}

		return new MiniProfile\StructureDto(
			title: $structureTitle,
			headDictionary: $headDictionaryDto,
			departmentDictionary: $departmentDictionaryDto,
			userDepartmentIds: $userDepartmentIds,
			teams: $teamDtoList,
		);
	}

	private function makeBaseInfoDto(EO_User $userModel): MiniProfile\BaseInfoDto
	{
		$userId = $userModel->getId();

		return new MiniProfile\BaseInfoDto(
			id: $userModel->getId(),
			name: $this->formatName($userModel),
			workPosition: $userModel->getWorkPosition(),
			utcOffset: $this->userService->getUtcOffset($userModel->getId()),
			status: $this->getStatus($userModel),
			role: (new \Bitrix\Intranet\User($userId))->getUserRole()?->value,
			url: $this->userService->getDetailUrl($userId),
			avatar: $this->getAvatarUrl($userModel, self::AVATAR_SIZE),
		);
	}

	private function makeDetailInfoDto(EO_User $userModel): MiniProfile\DetailInfoDto
	{
		global $USER_FIELD_MANAGER;

		$innerPhone = $USER_FIELD_MANAGER->GetUserFieldValue(
			entity_id: 'USER',
			field_id: 'UF_PHONE_INNER',
			value_id: $userModel->getId(),
			LANG: LANGUAGE_ID,
		);

		return new MiniProfile\DetailInfoDto(
			personalMobile: $userModel->getPersonalMobile(),
			innerPhone: is_string($innerPhone) ? $innerPhone : '',
			email: $userModel->getEmail(),
		);
	}

	private function makeAccessDto(int $currentUserId, Main\EO_User $targetUserModel): MiniProfile\AccessDto
	{
		if ($targetUserModel->getActive() === false)
		{
			return new MiniProfile\AccessDto();
		}

		return new MiniProfile\AccessDto(
			canChat: $this->chatFacade->hasAccess($currentUserId, $targetUserModel->getId())
		);
	}

	private function showOnlyBaseInfo(int $userId, Main\EO_User $targetUserModel): bool
	{
		if ($targetUserModel->getActive() === false)
		{
			return true;
		}

		$isUserIntranet = (new \Bitrix\Intranet\User($userId))->isIntranet();
		if (!$isUserIntranet)
		{
			return true;
		}

		$targetUserRole = (new \Bitrix\Intranet\User($targetUserModel->getId()))->getUserRole();
		return !in_array($targetUserRole, [UserRole::ADMIN, UserRole::INTRANET], true);
	}

	private function checkAccess(int $currentUserId, int $targetUserId): bool
	{
		if ($currentUserId === $targetUserId)
		{
			return true;
		}

		if (Main\Loader::includeModule('socialnetwork'))
		{
			return \CSocNetUser::canProfileView($currentUserId, $targetUserId);
		}

		return false;
	}

	private function getUserModel(int $userId): ?EO_User
	{
		return Main\UserTable::getByPrimary($userId, [
			'select' => [
				'ID',
				'NAME',
				'SECOND_NAME',
				'LAST_NAME',
				'LOGIN',
				'PERSONAL_MOBILE',
				'WORK_POSITION',
				'EMAIL',
				'PERSONAL_PHOTO',
				'ACTIVE',
				'LAST_ACTIVITY_DATE',
			],
		])->fetchObject();
	}

	private function makeAccessDeniedResult(): Main\Result
	{
		return (new Main\Result)->addError(
			new Main\Error(
				'',
				'ACCESS_DENIED'
			)
		);
	}

	private function formatName(EO_User $userModel): string
	{
		return \CUser::FormatName(
			\CSite::GetNameFormat(false),
			[
				'LOGIN' => $userModel->getLogin(),
				'NAME' => $userModel->getName(),
				'LAST_NAME' => $userModel->getLastName(),
				'SECOND_NAME' => $userModel->getSecondName(),
			],
			true,
			false,
		);
	}

	private function getAvatarUrl(EO_User $userModel, int $size): ?string
	{
		$fileTmp = \CFile::ResizeImageGet(
			$userModel->getPersonalPhoto(),
			['width' => $size, 'height' => $size],
			BX_RESIZE_IMAGE_EXACT,
			false,
			false,
			true,
		);

		return $fileTmp['src'] ?? null;
	}

	private function getStatus(Main\EO_User $userModel): array
	{
		if ($userModel->getActive() === false)
		{
			return [
				'code' => 'fired',
			];
		}

		$userId = $userModel->getId();
		$vacation = \Bitrix\Intranet\UserAbsence::isAbsentOnVacation($userId, true);
		if ($vacation)
		{
			$dateToTs = $vacation['DATE_TO_TS'] ?? null;

			return [
				'code' => 'vacation',
				'vacationTs' => $dateToTs,
			];
		}

		$status = \CUser::GetOnlineStatus($userId, $userModel->getLastActivityDate());

		return [
			'code' => $status['STATUS'] ?? null,
			'lastSeenTs' => $status['LAST_SEEN'] ?? null,
		];
	}
}