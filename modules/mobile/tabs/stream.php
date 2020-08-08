<?php

namespace Bitrix\Mobile\AppTabs;

use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Tab\Tabable;

class Stream implements Tabable
{
	private $context;

	public function isAvailable()
	{
		return true;
	}

	public function getData()
	{
		return [
			"sort" => 200,
			"imageName" => "stream",
			"badgeCode" => "stream",
			"page" => [
				"url" => $this->context->siteDir . "mobile/index.php?version=" . $this->context->version,
				"titleParams"=>["useLargeTitleMode"=>true, "text"=>$this->getTitle()],
				"useSearchBar" => true
			],
		];
	}

	/**
	 * @return boolean
	 */
	public function shouldShowInMenu()
	{
		return true;
	}

	/**
	 * @return null|array
	 */
	public function getMenuData()
	{
		return [
			"sort" => 200,
			"counter" => "**",
			"useLetterImage" => true,
			"color" => "#40465A",
			"imageUrl" => "favorite/stream.png",
			"title" => $this->getTitle(),
			"params" => [
				"url" => $this->context->siteDir . "mobile/index.php?version=" . $this->context->version,
				"counter"=>"**"
			],
		];
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
		return 200;
	}

	public function canChangeSort()
	{
		return true;
	}

	public function getTitle()
	{
		return Loc::getMessage("TAB_NAME_NEWS");
	}

	public function setContext($context)
	{
		$this->context = $context;
	}

	public function getShortTitle()
	{
		return Loc::getMessage("TAB_NAME_NEWS_SHORT");
	}

	public function getId()
	{
		return "news";
	}
}

