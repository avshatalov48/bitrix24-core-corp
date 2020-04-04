<?php

namespace Bitrix\Mobile\AppTabs;

use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Context;
use Bitrix\Mobile\Tab\Tabable;

class Notify implements Tabable
{
	private $context;

	public function isAvailable()
	{
		return true;
	}

	public function getData()
	{
		return [
			"sort" => 300,
			"imageName" => "bell",
			"badgeCode" => "notifications",
			"page" => ["page_id" => "notifications", "url" => $this->context->siteDir . "mobile/im/notify.php"]
		];

	}

	public function getMenuData()
	{
		return [
			"sort" => $this->defaultSortValue(),
			"counter" => "notifications",
			"useLetterImage" => true,
			"color" => "#40465A",
			"imageUrl" => "favorite/notify.png",
			"title" => $this->getTitle(),
			"params" => [
				"page_id" => "notifications",
				"url" => $this->context->siteDir . "mobile/im/notify.php"
			]
		];
	}

	public function shouldShowInMenu()
	{
		return true;
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

}