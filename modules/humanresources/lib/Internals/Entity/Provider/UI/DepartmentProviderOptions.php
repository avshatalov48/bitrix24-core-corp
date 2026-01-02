<?php

namespace Bitrix\HumanResources\Internals\Entity\Provider\UI;

use Bitrix\HumanResources\Internals\Enum\Provider\UI\DepartmentProviderAvatarMode;
use Bitrix\HumanResources\Internals\Enum\Provider\UI\DepartmentProviderSelectMode;
use Bitrix\HumanResources\Internals\Enum\Provider\UI\DepartmentProviderTagStyleMode;
use Bitrix\Socialnetwork\Integration\UI\EntitySelector\UserProvider;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Loader;

class DepartmentProviderOptions extends BaseProviderOptions
{
	public readonly DepartmentProviderTagStyleMode $tagStyle;
	public readonly DepartmentProviderAvatarMode $avatarMode;
	public readonly DepartmentProviderSelectMode $selectMode;
	public readonly bool $shouldCountSubdepartments;
	public readonly bool $allowOnlyUserDepartments;
	public readonly bool $allowSelectRootDepartment;
	public readonly bool $allowFlatDepartments;
	public readonly bool $fillDepartmentsTab;
	public readonly bool $nodeActiveFilter;
	public readonly bool $shouldCountUsers;
	public readonly bool $useMultipleTabs;
	public readonly bool $fillRecentTab;
	public readonly bool $restricted;
	public readonly bool $showIcons;
	public readonly bool $isForSearch;
	public readonly bool $isFlatMode;
	public readonly array $userOptions;
	public readonly int $depthLevel;

	/**
	 * @throws LoaderException
	 */
	public function __construct(array $rawOptions = [])
	{
		parent::__construct($rawOptions);

		$this->initNodeActiveFilter($rawOptions);
		$this->initSelectMode($rawOptions);
		$this->initAllowFlatDepartments($rawOptions);
		$this->initAllowOnlyUserDepartments($rawOptions);
		$this->initAllowSelectRootDepartment($rawOptions);
		$this->initUserOptions($rawOptions);
		$this->initFillDepartmentsTab($rawOptions);
		$this->initFillRecentTab($rawOptions);
		$this->initDepthLevel($rawOptions);
		$this->initShouldCountSubdepartments($rawOptions);
		$this->initShouldCountUsers($rawOptions);
		$this->initForSearch($rawOptions);
		$this->initFlatMode($rawOptions);
		$this->initUseMultipleTabs($rawOptions);
		$this->initVisualOptions($rawOptions);
	}

	private function initNodeActiveFilter(array $options): void
	{
		$this->nodeActiveFilter = isset($options['activeNodeFilter']) && is_bool($options['activeNodeFilter'])
			? $options['activeNodeFilter']
			: true
		;
	}

	private function initSelectMode(array $options): void
	{
		$this->selectMode =
			DepartmentProviderSelectMode::tryFrom($options['selectMode'])
			?? DepartmentProviderSelectMode::UsersOnly
		;
	}

	private function initAllowFlatDepartments(array $options): void
	{
		$this->allowFlatDepartments =
			isset($options['allowFlatDepartments'])
			&& $options['allowFlatDepartments'] === true
		;
	}

	private function initAllowOnlyUserDepartments(array $options): void
	{
		$this->allowOnlyUserDepartments =
			isset($options['allowOnlyUserDepartments'])
			&& $options['allowOnlyUserDepartments'] === true
		;
	}

	private function initAllowSelectRootDepartment(array $options): void
	{
		$this->allowSelectRootDepartment = is_bool($options['allowSelectRootDepartment'] ?? null)
			? $options['allowSelectRootDepartment']
			: $this->selectMode === DepartmentProviderSelectMode::DepartmentsOnly
		;
	}

	/**
	 * @throws LoaderException
	 */
	private function initUserOptions(array $options): void
	{
		if (
			isset($options['userOptions'])
			&& is_array($options['userOptions'])
			&& Loader::includeModule('socialnetwork')
		)
		{
			$userProvider = new UserProvider($options['userOptions']);
			$this->userOptions = $userProvider->getOptions();
		}
		else
		{
			$this->userOptions = [];
		}
	}

	private function initFillDepartmentsTab(array $options): void
	{
		$this->fillDepartmentsTab = is_bool($options['fillDepartmentsTab'] ?? null)
			? $options['fillDepartmentsTab']
			: true
		;
	}

	private function initFillRecentTab(array $options): void
	{
		if (isset($options['fillRecentTab']) && is_bool($options['fillRecentTab']))
		{
			$this->fillRecentTab =
				$options['fillRecentTab']
				&& ($this->selectMode === DepartmentProviderSelectMode::DepartmentsOnly)
			;
		}
		else
		{
			$this->fillRecentTab = false;
		}
	}

	private function initDepthLevel(array $options): void
	{
		if (
			isset($options['depthLevel'])
			&& is_int($options['depthLevel'])
			&& $this->fillRecentTab
		)
		{
			$this->depthLevel = $options['depthLevel'];
		}
		else
		{
			$this->depthLevel = 1;
		}
	}

	private function initShouldCountSubdepartments(array $options): void
	{
		$allowedModes = [
			DepartmentProviderSelectMode::DepartmentsOnly,
			DepartmentProviderSelectMode::UsersAndDepartments,
		];

		if (
			isset($options['shouldCountSubdepartments'])
			&& is_bool($options['shouldCountSubdepartments'])
		)
		{
			$this->shouldCountSubdepartments =
				$options['shouldCountSubdepartments']
				&& in_array($this->selectMode, $allowedModes, true)
			;
		}
		else
		{
			$this->shouldCountSubdepartments = false;
		}
	}

	private function initShouldCountUsers(array $options): void
	{
		$allowedModes = [
			DepartmentProviderSelectMode::UsersOnly,
			DepartmentProviderSelectMode::UsersAndDepartments,
		];

		if (
			isset($options['shouldCountUsers'])
			&& is_bool($options['shouldCountUsers'])
		)
		{
			$this->shouldCountUsers =
				$options['shouldCountUsers']
				&& in_array($this->selectMode, $allowedModes, true)
			;
		}
		else
		{
			$this->shouldCountUsers = false;
		}
	}

	private function initForSearch(array $options): void
	{
		$this->isForSearch = is_bool($options['forSearch'] ?? null)
			? $options['forSearch']
			: false
		;
	}

	private function initFlatMode(array $options): void
	{
		$this->isFlatMode = is_bool($options['flatMode'] ?? null)
			? $options['flatMode']
			: false
		;
	}

	private function initUseMultipleTabs(array $options): void
	{
		$this->useMultipleTabs =
			isset($options['useMultipleTabs'])
			&& $options['useMultipleTabs']
		;
	}

	private function initVisualOptions(array $options): void
	{
		$visualOptions = $options['visual'] ?? [];

		$this->avatarMode =
			DepartmentProviderAvatarMode::tryFrom($visualOptions['avatarMode'])
			?? DepartmentProviderAvatarMode::Both
		;

		$this->tagStyle =
			DepartmentProviderTagStyleMode::tryFrom($visualOptions['tagStyle'])
			?? DepartmentProviderTagStyleMode::Default
		;

		$this->showIcons = (bool)($visualOptions['showIcons'] ?? true);
	}
}
