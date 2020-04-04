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
			"page" => ["url" => $this->context->siteDir . "mobile/index.php?version=" . $this->context->version],
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
		return Loc::getMessage("TAB_NAME_STREAM");
	}

	public function setContext($context)
	{
		$this->context = $context;
	}

}

