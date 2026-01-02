<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Service\Logger\EventLogger;
use Bitrix\Booking\Internals\Service\Logger\EventTypeEnum;
use Bitrix\Booking\Internals\Service\Logger\LogLevelEnum;

class CompanyFeedSenderAgent
{
	public static function execute(): string|null
	{
		$account = Container::getYandexAccount();
		if (!$account->isRegistered())
		{
			return null;
		}

		$companyCollection = Container::getYandexCompanyFeedProvider()->getCompanies();
		$companyFeedSender = Container::getYandexCompanyFeedSender();

		// feed has not changed since the last time
		if ($companyFeedSender->getLastFeedHash() === $companyFeedSender->getFeedHash($companyCollection))
		{
			return self::getName();
		}

		$result = Container::getYandexCompanyFeedSender()->sendFeed($companyCollection);
		if (!$result->isSuccess())
		{
			(new EventLogger())->log(
				LogLevelEnum::Error,
				implode(';', $result->getErrorMessages()),
				EventTypeEnum::YandexCompanyFeedSender
			);
		}

		return self::getName();
	}

	public static function getName(): string
	{
		return sprintf('\\' . static::class . '::execute();');
	}
}
