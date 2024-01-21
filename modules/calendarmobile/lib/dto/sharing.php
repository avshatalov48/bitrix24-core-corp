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

	/** @var string|null */
	public $shortUrl;

	/** @var Settings */
	public $settings;

	public function getCasts(): array
	{
		return [
			'isEnabled' => Type::bool(),
			'isRestriction' => Type::bool(),
			'shortUrl' => Type::string(),
			'settings' => Type::object(Settings::class),
		];
	}
}