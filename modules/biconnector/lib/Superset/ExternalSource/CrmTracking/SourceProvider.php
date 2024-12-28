<?php

namespace Bitrix\BIConnector\Superset\ExternalSource\CrmTracking;

use Bitrix\Main\Loader;
use Bitrix\Crm;

final class SourceProvider
{
	/**
	 * @return Source[]
	 */
	public static function getSources(): array
	{
		if (!Loader::includeModule('crm') || !Loader::includeModule('seo'))
		{
			return [];
		}

		/** @var Source[] $sources */
		$sources = [];
		foreach (Crm\Tracking\Provider::getAvailableSources() as $row)
		{
			if (!isset($row['CODE']))
			{
				continue;
			}

			$code = $row['CODE'];
			if (isset($sources[$code]))
			{
				$sources[$code]->setConnected($sources[$code]->isConnected() || $row['CONFIGURED']);

				continue;
			}

			$internalCode = self::getCodeByCrmSourceCode($code);

			if ($internalCode)
			{
				$sources[$code] = new Source($internalCode, $code, $row['CONFIGURED']);
			}
		}

		return $sources;
	}

	/**
	 * @param string $code
	 * @return string|null
	 */
	private static function getCodeByCrmSourceCode(string $code): ?string
	{
		return match ($code) {
			Crm\Tracking\Source\Base::Vkads => Source::CRM_SOURCE_VK_ADS,
			Crm\Tracking\Source\Base::Ya => Source::CRM_SOURCE_YANDEX,
			Crm\Tracking\Source\Base::Fb => Source::CRM_SOURCE_FACEBOOK,
			Crm\Tracking\Source\Base::Ga => Source::CRM_SOURCE_GOOGLE,
			default => null,
		};
	}
}
