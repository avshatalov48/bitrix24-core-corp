<?php

namespace Bitrix\Market\Subscription;

use Bitrix\Main\Loader;
use Bitrix\Market\Subscription;
use Bitrix\Rest\Marketplace\Client;
use Bitrix\Main\Type\Date;


class Status
{
	public const ACTIVE = 'A';
	public const PRE_EXPIRED = 'P';
	public const EXPIRED = 'E';
	public const NEVER_USED = 'N';
	public const NOT_EXIST = 'U';

	public const PRE_EXPIRED_DAYS = 5;

	public static function isExist(): bool
	{
		return Loader::includeModule('rest') && Client::isSubscriptionAccess();
	}

	public static function get(): string
	{
		$status = Status::NOT_EXIST;

		if (Status::isExist()) {
			$finish = Subscription::getFinishDate();

			if (Client::isSubscriptionAvailable()) {
				$date = (new Date())->add(Status::PRE_EXPIRED_DAYS . 'days');
				$status = ($finish > $date) ? Status::ACTIVE : Status::PRE_EXPIRED;
			} elseif ($finish) {
				$status = Status::EXPIRED;
			} else {
				$status = Status::NEVER_USED;
			}
		}

		return $status;
	}

	public static function isExpired(): bool
	{
		return in_array(Status::get(), [Status::PRE_EXPIRED, Status::EXPIRED]);
	}

	public static function getSlider(): string
	{
		$slider = 'limit_benefit_market';

		if (Client::isSubscriptionDemo()) {
			$slider = 'limit_benefit_market_trial_active';
		} else if (Subscription::getFinishDate() !== null) {
			$slider = 'limit_benefit_market_active';
		}

		return $slider;
	}
}