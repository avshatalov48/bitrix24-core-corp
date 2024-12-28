<?php
namespace Bitrix\IntranetMobile\Controller;

use Bitrix\Intranet\Controller\Invite;
use Bitrix\IntranetMobile\Dto\SortingDto;
use Bitrix\IntranetMobile\Dto\FilterDto;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Intranet\ActionFilter\IntranetUser;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\IntranetMobile\Provider\UserProvider;

class Employees extends Base
{
	public function configureActions(): array
	{
		return [
			'getUserList' => [
				'+prefilters' => [
					new CloseSession(),
					new IntranetUser(),
				],
			],
		];
	}

	public function getUserListAction(
		?array $filterParams = null,
		?array $sortingParams = null,
		?PageNavigation $nav = null,
	): array
	{
		$filter = $filterParams ? new FilterDto(...$filterParams) : new FilterDto();
		$sorting = $sortingParams ? new SortingDto(...$sortingParams) : new SortingDto();
		$userProvider = new UserProvider();

		$result = $userProvider->getByPage(filter: $filter, sorting: $sorting, nav: $nav);

		$users = $result['users'];
		$isOnlyCurrentUser = count($users) === 1 && $users[0]->id === (int)$this->getCurrentUser()->getId();

		if ($isOnlyCurrentUser && $userProvider->isDefaultOrEmptyFilter($filter))
		{
			return [];
		}

		return $result;
	}

	public function getUsersByIdsAction(array $ids): array
	{
		return (new UserProvider())->getUsersByIds($ids);
	}

	public function reinviteAction(int $userId, bool $isExtranetUser)
	{
		$isExtranetUser = $isExtranetUser ? 'Y' : 'N';

		return $this->forward(Invite::class, 'reinvite', [
			'params' => [
				'userId' => $userId,
				'extranet' => $isExtranetUser,
			],
		]);
	}

	public function getSearchBarPresetsAction(): array
	{
		$presets = (new UserProvider())->getPresets();

		$intranetUser = new \Bitrix\Intranet\User();
		$result = [];

		foreach ($presets as $preset)
		{
			if ($preset['id'] === 'invited')
			{
				$preset['value'] = $intranetUser->getInvitationCounterValue();
			}
			if ($preset['id'] === 'wait_confirmation')
			{
				$preset['value'] = $intranetUser->getWaitConfirmationCounterValue();
			}
			$result[] = $preset;
		}

		return [
			'presets' => $result,
		];
	}

	public function updateDepartmentAction(array $newDepartmentsIds, int $userId): array|bool
	{
		if (!\Bitrix\Intranet\Util::isIntranetUser($userId))
		{
			return false;
		}

		$allDepartments = \CIntranetRestService::departmentGet([]);

		foreach ($allDepartments as $department)
		{
			if (!is_array($department))
			{
				continue;
			}

			if ((int)$department['UF_HEAD'] === $userId && !in_array($department['ID'], $newDepartmentsIds))
			{
				\CIntranetRestService::departmentUpdate([
					'ID' => $department['ID'],
					'UF_HEAD' => '0',
				]);
			}
		}

		return \Bitrix\Rest\Api\User::userUpdate([
			'id' => $userId,
			'UF_DEPARTMENT' => $newDepartmentsIds,
		]);
	}
}