<?php

namespace Bitrix\IntranetMobile\Provider;

use Bitrix\Intranet\User\UserManager;
use Bitrix\IntranetMobile\Dto\SortingDto;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\IntranetMobile\Dto\FilterDto;

final class UserProvider
{
	private ?UserManager $userManager = null;

	public const ALL_DEPARTMENTS = 0;

	public function __construct()
	{
		if (\Bitrix\Main\Loader::includeModule('tasks'))
		{
			$this->userManager = new UserManager('IntranetMobile/UserProvider/getByPage', []);
		}
	}

	public function getByPage(
		FilterDto $filter,
		SortingDto $sorting,
		?PageNavigation $nav = null,
	)
	{
		$users = $this->getUsers($this->getSelect(), $filter, $sorting, $nav);

		return $this->convertUsers($users);
	}

	private function convertUsers(array $users)
	{
		$usersMainInfo = [];
		$usersIntranetInfo = [];
		foreach ($users as $user)
		{
			$usersMainInfo[] = \Bitrix\Mobile\Provider\UserRepository::createUserDTO($user['data']);
			$usersIntranetInfo[] = \Bitrix\IntranetMobile\Repository\UserRepository::createUserDto([...$user['data'], 'ACTIONS' => $user['actions']]);
		}

		return [
			'items' => $usersMainInfo,
			'users' => $usersIntranetInfo,
		];
	}

	public function getPresets(): array
	{
		$presets = $this->userManager?->getDefaultFilterPresets();
		$result = [];

		foreach ($presets as $preset)
		{
			$result[] = ['id' => $preset->getId(), ...$preset->toArray()];
		}

		return $result;
	}

	public function getDefaultPreset()
	{
		foreach ($this->getPresets() as $preset)
		{
			if ($preset['default'] === true)
			{
				return $preset;
			}
		}
	}

	public function isDefaultFilter(FilterDto $filter): bool
	{
		return (
			$filter->searchString === ''
			&& $filter->presetId === self::getDefaultPreset()['id']
			&& $filter->department === FilterDto::ALL_DEPARTMENTS
		);
	}

	public function isEmptyFilter(FilterDto $filter): bool
	{
		return (
			$filter->searchString === ''
			&& $filter->presetId === null
			&& $filter->department === FilterDto::ALL_DEPARTMENTS
		);
	}

	public function isDefaultOrEmptyFilter($filter): bool
	{
		return $this->isDefaultFilter($filter) || $this->isEmptyFilter($filter);
	}

	private function getUsers(array $select, ?FilterDto $filter = null, ?SortingDto $sorting = null, ?PageNavigation $nav = null): array
	{
		$nav ??= new PageNavigation('page');
		$filter ??= new FilterDto();
		$sorting ??= new SortingDto();

		$params = [
			'select' => $select,
			'limit' => $nav->getLimit(),
			'offset' => $nav->getOffset(),
			'filter' => ['DEPARTMENT' => $filter->department],
		];

		$sort = $sorting->getType();
		if (is_array($sort))
		{
			$params['order'] = [
				...UserManager::SORT_WAITING_CONFIRMATION,
				...UserManager::SORT_INVITED,
				...UserManager::SORT_STRUCTURE,
				...UserManager::SORT_INVITATION,
				];
		}

		return $this->userManager
			? $this->userManager->getList(
				$params,
				$filter->presetId,
				$filter->searchString,
			) : [];
	}

	public function getUsersByIds(array $ids): array
	{
		$params = [
			'select' => $this->getSelect(),
			'filter' => ['=ID' => $ids],
		];

		return $this->userManager ? $this->convertUsers($this->userManager->getList($params)) : [];
	}

	public function getAllUsersCount(): int
	{
		$users = $this->userManager ? $this->userManager->getList([
			'select' => ['ID'],
			'limit' => 2,
		]) : [];

		return count($users);
	}

	private function getSelect(): array
	{
		return [
			'ID',
			'NAME',
			'LAST_NAME',
			'SECOND_NAME',
			'LOGIN',
			'PERSONAL_PHOTO',
			'WORK_POSITION',
			'UF_DEPARTMENT',
			'EMAIL',
			'WORK_PHONE',
			'ACTIVE',
			'CONFIRM_CODE',
			'DATE_REGISTER',
			'PERSONAL_MOBILE',
			'PERSONAL_PHONE',
		];
	}
}
