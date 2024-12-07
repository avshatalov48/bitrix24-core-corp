<?php

namespace Bitrix\CalendarMobile\Dto;

use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Type;

final class Sharing extends Dto
{
	/** @var bool */
	public $isEnabled;

	/** @var bool */
	public $isRestriction;

	/** @var bool */
	public $isPromo;

	/** @var string|null */
	public $shortUrl;

	/** @var UserInfo */
	public $userInfo;

	/** @var Settings */
	public $settings;

	/** @var SharingOptions */
	public $options;

	public function getCasts(): array
	{
		return [
			'isEnabled' => Type::bool(),
			'isRestriction' => Type::bool(),
			'isPromo' => Type::bool(),
			'shortUrl' => Type::string(),
			'userInfo' => Type::object(UserInfo::class),
			'settings' => Type::object(Settings::class),
			'options' => Type::object(SharingOptions::class)
		];
	}
}
