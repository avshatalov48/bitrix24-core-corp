<?php

namespace Bitrix\Intranet\Enum;

use Bitrix\Main\HttpRequest;
use Bitrix\Main\Web\UserAgent\Detector;
use Bitrix\Main\Web\UserAgent\DeviceType;

enum UserAgentType
{
	case MOBILE_APP;
	case DESKTOP;
	case TV_APP;
	case BROWSER;
	case UNKNOWN;

	public static function fromRequest(HttpRequest $request): static
	{
		$userAgent = $request->getUserAgent();
		if (empty($userAgent))
		{
			return UserAgentType::UNKNOWN;
		}

		$browser = (new Detector())->detectBrowser($userAgent);
		switch ($browser->getDeviceType())
		{
			case DeviceType::DESKTOP:
				return UserAgentType::DESKTOP;
			case DeviceType::MOBILE_PHONE:
			case DeviceType::TABLET:
				return UserAgentType::MOBILE_APP;
			case DeviceType::TV:
				return UserAgentType::TV_APP;
			default:
				return UserAgentType::BROWSER;
		}
	}
}
