<?php

namespace Bitrix\Mobile\AppTabs;

use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Context;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\MobileApp\Janative\Manager;

class Notify implements Tabable
{
	private $context;

	public function isAvailable()
	{
		return true;
	}

	public function getData()
	{
		return $this->getDataInternal();
	}

	public function getMenuData()
	{
		$data = $this->getDataInternal();
		$result = [
			"title" => $this->getTitle(),
			"useLetterImage" => true,
			"color" => "#40465A",
			"imageUrl" => "favorite/notify.png",
		];;

		if($data["component"])
		{
			$result["params"]= [
				"onclick"=>\Bitrix\Mobile\Tab\Utils::getComponentJSCode($data["component"]),
			];
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
			"component" => [
				"name" => "JSStackComponent",
				"title" =>  $this->getTitle(),
				"componentCode" => "im.notify",
				"scriptPath" => Manager::getComponentPath("im.notify"),
				"rootWidget" => [
					'name' => 'layout',
					'settings' => [
						'objectName' => 'layoutWidget',
					],
				],
			],
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

	public function getShortTitle()
	{
		return Loc::getMessage("TAB_NAME_NOTIFY");
	}

	public function getId()
	{
		return "notify";
	}
}