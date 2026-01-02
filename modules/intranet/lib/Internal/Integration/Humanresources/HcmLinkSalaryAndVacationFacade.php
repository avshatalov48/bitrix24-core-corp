<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Integration\Humanresources;

use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Loader;

class HcmLinkSalaryAndVacationFacade
{
	public function isAvailableByUserId(int $userId): bool
	{
		$isExtranetSite = Loader::includeModule('extranet') && \CExtranet::IsExtranetSite();

		if ($isExtranetSite)
		{
			return false;
		}

		if ($userId > 0 && Loader::includeModule('humanresources'))
		{
			$settings = Container::getHcmLinkSalaryAndVacationService()->getSettingsForFrontendByUser($userId);

			if (isset($settings['show']))
			{
				return $settings['show'] === true;
			}
		}

		return false;
	}
}
