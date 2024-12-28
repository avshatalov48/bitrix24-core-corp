<?php

namespace Bitrix\Intranet\UI\LeftMenu\Preset;

use Bitrix\Extranet\Service\ServiceContainer;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Main\Loader;

class Collab extends PresetAbstract
{
	const CODE = 'collab';

	public static function isAvailable(): bool
	{
		return Loader::includeModule('extranet')
			&& \CExtranet::isExtranetSite()
			&& ServiceContainer::getInstance()->getCollaberService()->isCollaberById((int)CurrentUser::get()->getId())
		;
	}

	public function getName(): string
	{
		return self::CODE;
	}

	public function getStructure(): array
	{
		return [
			'shown' => [
				'menu_im_messenger',
				'menu_im_collab',
				'menu_tasks',
				'menu_files',
				'menu_calendar',
			],
		];
	}
}