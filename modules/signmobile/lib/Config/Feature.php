<?php

namespace Bitrix\SignMobile\Config;

use Bitrix\Intranet\Util;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\FeatureResolver;
use Bitrix\MobileApp\Mobile;
use Bitrix\Sign\Integration\Bitrix24\B2eTariff;

final class Feature
{
	private static ?self $instance = null;

	public static function instance(): self
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function isMyDocumentsGridAvailable(): bool
	{
		if (class_exists(FeatureResolver::class))
		{
			return Mobile::getInstance()::getApiVersion() >= 54
				&& FeatureResolver::instance()->released('mobileMyDocumentsGrid')
				&& Util::isIntranetUser()
				&& Storage::instance()->isB2eAvailable()
			;
		}

		return false;
	}

	public function isSendDocumentByEmployeeEnabled(): bool
	{
		if (class_exists(FeatureResolver::class))
		{
			return Mobile::getInstance()::getApiVersion() >= 54
				&& FeatureResolver::instance()->released('mobileSendByEmployee')
				&& \Bitrix\Sign\Config\Feature::instance()->isSendDocumentByEmployeeEnabled()
				&& !B2eTariff::instance()->isB2eRestrictedInCurrentTariff()
			;
		}

		return false;
	}
}