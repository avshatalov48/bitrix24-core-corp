<?php

namespace Bitrix\Intranet\Public\Provider\Portal;

use Bitrix\Intranet\Internal\Repository\LanguageRepository;
use Bitrix\Main\ModuleManager;

class LanguageProvider
{
	public function getPublicArray(): array
	{
		$languagesRes = (new LanguageRepository())->getPortalLanguages();
		$languages = [];

		foreach ($languagesRes as $language)
		{
			$value = [
				'NAME' => $language['NAME'],
				'IS_BETA' => in_array($language['ID'], ['kz', 'ar']) ? true : false,
			];

			if ($language['ID'] === LANGUAGE_ID)
			{
				$languages = [$language['ID'] => $value] + $languages;
			}
			else
			{
				$languages[$language['ID']] = $value;
			}
		}

		return $languages;
	}

	public function isLanguageIdChangeAvailable(): bool
	{
		return
			ModuleManager::isModuleInstalled('bitrix24')
			|| (defined('INTRANET_LANGUAGE_ID_CHANGE_AVAILABLE') && INTRANET_LANGUAGE_ID_CHANGE_AVAILABLE)
		;
	}
}
