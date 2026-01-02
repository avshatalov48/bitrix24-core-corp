<?php

namespace Bitrix\Booking\Internals\Service\Logger;

enum EventTypeEnum: string
{
	case DelayedTask = 'DELAYED_TASK';
	case YandexCompanyFeedSender = 'YANDEX_COMPANY_FEED_SENDER';
	case Common = 'COMMON';
}
