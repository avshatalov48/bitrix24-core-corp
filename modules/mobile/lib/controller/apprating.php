<?php

namespace Bitrix\Mobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\JsonController;
use Bitrix\Main\Config\Option;

class AppRating extends JsonController
{
	public function configureActions(): array
	{
		return [
			'isAppRatingEnabled' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	/**
	 * @restMethod mobile.AppRating.isAppRatingEnabled
	 * @return bool
	 */
	public function isAppRatingEnabledAction(): bool
	{
		return Option::get('mobile', 'app_rating_enabled', 'Y') === 'Y';
	}
}