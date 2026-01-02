<?php

namespace Bitrix\HumanResources\Access\LeftMenuSection;

use Bitrix\HumanResources\Enum\Access\RoleCategory;
use Bitrix\HumanResources\Internals\Service\Container;

abstract class MenuSectionBaseClass
{
	abstract public function getMenuId(): string;
	abstract  public function getTitle(): string;
	abstract  public function getCategory(): RoleCategory;

	public function hasPermissionToEditRights(): bool
	{
		return Container::getAccessService()
			->checkAccessToEditPermissions(
				category: $this->getCategory(),
				checkTariffRestriction: false,
			)
		;
	}

	/**
	 * @return array{category: string, menuId: string}
	*/
	public function getCategoryParameters(): array
	{
		return [
			'category' => $this->getCategory()->value,
			'menuId' => $this->getMenuId(),
		];
	}
}