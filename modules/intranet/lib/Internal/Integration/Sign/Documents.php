<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Integration\Sign;

use Bitrix\Intranet;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Sign;

class Documents
{
	public static function isAvailable(): bool
	{
		$isExtranetSite = Loader::includeModule('extranet') && \CExtranet::IsExtranetSite();

		if ($isExtranetSite)
		{
			return false;
		}

		if (!Loader::includeModule('sign'))
		{
			return false;
		}

		if (!class_exists(Sign\FeatureResolver::class) || !class_exists(Sign\Config\Feature::class))
		{
			return false;
		}

		$user = new Intranet\User();

		return $user->isIntranet()
			&& Sign\FeatureResolver::instance()->released('sendByEmployee')
			&& Sign\Config\Feature::instance()->isSendDocumentByEmployeeEnabled();
	}

	public static function isLocked(): bool
	{
		return !Loader::includeModule('sign');
	}

	public static function isAvailableB2e(): bool
	{
		return !Sign\Integration\Bitrix24\B2eTariff::instance()->isB2eRestrictedInCurrentTariff();
	}

	public static function getCount(): int
	{
		if (Loader::includeModule('sign') && Sign\Config\Storage::instance()->isB2eAvailable())
		{
			return Sign\Service\Container::instance()
				->getCounterService()
				->get(Sign\Type\CounterType::SIGN_B2E_MY_DOCUMENTS, (int)CurrentUser::get()->getId())
			;
		}

		return 0;
	}

	public static function getPullCounterEventName(): string
	{
		if (Loader::includeModule('sign') && Sign\Config\Storage::instance()->isB2eAvailable())
		{
			return Sign\Service\Container::instance()
				->getCounterService()
				->getPullEventName(Sign\Type\CounterType::SIGN_B2E_MY_DOCUMENTS)
			;
		}

		return '';
	}
}
