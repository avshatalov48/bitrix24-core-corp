<?php
namespace Bitrix\Mobile\AppTabs;

use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Provider\UserRepository;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\Mobile\Config\Feature;
use Bitrix\Mobile\Feature\MenuFeature;

class MenuNew implements Tabable
{
	private $context;

	public function isAvailable()
	{
		return Feature::isEnabled(MenuFeature::class);
	}

	public function getData()
	{
		return [
			'id' => $this->getId(),
			'sort' => 1000,
			'useAvatar' => true,
			'imageName' => 'menu_2',
			'badgeCode' => 'more',
			'component' => [
				'name' => 'JSStackComponent',
				"title" => GetMessage("MD_COMPONENT_MORE"),
				'componentCode' => 'menu',
				"scriptPath" => \Bitrix\MobileApp\Janative\Manager::getComponentPath("menu"),
				'rootWidget' => [
					'name' => 'layout',
					'settings' => [
						'objectName' => 'layout',
						'useLargeTitleMode' => true,
					],
				],
				'params' => [],
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

	public function defaultSortValue()
	{
		return 2000;
	}

	public function canChangeSort()
	{
		return false;
	}

	public function getTitle()
	{
		return Loc::getMessage('TAB_NAME_MORE');
	}

	public function getShortTitle()
	{
		return Loc::getMessage('TAB_NAME_MORE');
	}

	public function getId()
	{
		return 'menu';
	}

	public function getIconId(): string
	{
		return 'menu';
	}

	public function setContext($context)
	{
		$this->context = $context;
	}

	public function getLastAndSecondName(): string
	{
		global $USER;

		return \CUser::formatName(\CSite::getNameFormat(), [
			'NAME' => $USER->GetParam('NAME'),
			'LAST_NAME' => $USER->GetParam('LAST_NAME'),
		]);
	}

	public function getImageUrl(): string
	{
		global $USER;

		$personalPhotoId = $USER->GetParam('PERSONAL_PHOTO');

		if (!$personalPhotoId)
		{
			return '';
		}

		[$originalAvatar, $resizedAvatar] = UserRepository::getAvatar(
			(int)$personalPhotoId,
			['width' => 64, 'height' => 64],
		);

		return $resizedAvatar;
	}
}
