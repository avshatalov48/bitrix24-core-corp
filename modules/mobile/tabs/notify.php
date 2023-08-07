<?php

namespace Bitrix\Mobile\AppTabs;

use Bitrix\Main\Loader;
use \Bitrix\MobileApp\Mobile;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\MobileApp\Janative\Manager;

class Notify implements Tabable
{
	private $context;

	public function isAvailable()
	{
		if (!Loader::includeModule('mobileapp') || !Loader::includeModule('im'))
		{
			return false;
		}

		if (Mobile::getApiVersion() >= 41)
		{
			return false;
		}

		return true;
	}

	public function isNext()
	{
		return (
			Mobile::getApiVersion() >= 41
			&& \Bitrix\Main\Config\Option::get("mobile", "NEXT_NOTIFICATIONS", "N") !== "N"
		);
	}

	public function getData()
	{
		return $this->getDataInternal();
	}

	public function getMenuData()
	{
		$result = [
			"title" => $this->getTitle(),
			"sort" => $this->defaultSortValue(),
			"counter" => "notifications",
			"useLetterImage" => true,
			"color" => "#40465A",
			"imageUrl" => "favorite/notify.png",
		];;

		$data = $this->getDataInternal();

		if ($this->isNext())
		{
			$result["params"] = [
				"onclick" => \Bitrix\Mobile\Tab\Utils::getComponentJSCode($data["component"]),
			];
		}
		else
		{
			$result["params"] = $data["page"];
		}

		return $result;
	}

	public function getDataInternal()
	{
		return [
			"sort" => $this->defaultSortValue(),
			"imageName" => "bell",
			"badgeCode" => "notifications",
			"id" => $this->getId(),
			"page" => [
				"titleParams" => [
					"useLargeTitleMode" => true,
					"text" => $this->getTitle()
				],
				"page_id" => "im.notify",
				"url" => $this->context->siteDir . "mobile/im/notify.php",
			]
		];
	}

	public function shouldShowInMenu()
	{
		return false;
	}

	public function canBeRemoved()
	{
		return true;
	}

	/**
	 * @return integer
	 */
	public function defaultSortValue()
	{
		return 300;
	}

	public function canChangeSort()
	{
		return true;
	}

	public function getTitle()
	{
		return Loc::getMessage("TAB_NAME_NOTIFY");
	}

	public function setContext($context)
	{
		$this->context = $context;
	}

	public function getShortTitle()
	{
		return Loc::getMessage("TAB_NAME_NOTIFY");
	}

	public function getId()
	{
		return "notify";
	}

	public function getIconId(): string
	{
		return $this->getId();
	}
}