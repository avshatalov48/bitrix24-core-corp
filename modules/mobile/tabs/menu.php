<?php
namespace Bitrix\Mobile\AppTabs;

use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Context;
use Bitrix\Mobile\Tab\Tabable;

class Menu implements Tabable
{
	private $context;

	public function isAvailable()
	{
		return true;
	}

	public function getData()
	{
		return [
			"sort" => 1000,
			"imageName" => "menu_2",
			"badgeCode" => "more",
			"component" => [
				"settings" => ["useSearch" => true, "useLargeTitleMode" => true],
				"name" => "JSMenuComponent",
				"title" => GetMessage("MD_COMPONENT_MORE"),
				"componentCode" => "settings",
				"scriptPath" => \Bitrix\MobileApp\Janative\Manager::getComponentPath("more"),
				"params" => [
					"userId" => $this->context->userId,
					"SITE_ID" => $this->context->siteId,
				]
			]
		];
	}

	public function getMenuData()
	{
		return null;
	}

	public function shouldShowInMenu()
	{
		return false;
	}

	public function canBeRemoved()
	{
		return false;
	}

	/**
	 * @return integer
	 */
	public function defaultSortValue()
	{
		return 1000;
	}

	public function canChangeSort()
	{
		return false;
	}

	public function getTitle()
	{
		return Loc::getMessage("TAB_NAME_MORE");
	}

	public function setContext($context)
	{
		$this->context = $context;
	}

	public function getShortTitle()
	{
		return Loc::getMessage("TAB_NAME_MORE_SHORT");
	}

	public function getId()
	{
		return "more";
	}
}

