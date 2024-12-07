<?php

namespace Bitrix\Crm\Service\Sign\B2e;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\LanguageTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use CBitrix24;
use ReflectionException;

/**
 * Service for working with languages.
 */
final class LanguageService
{
	public function getDefaultLanguage(): string
	{
		if (
			ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24')
			&& method_exists('CBitrix24', 'getLicensePrefix')
		)
		{
			$defaultLanguage = CBitrix24::getLicensePrefix();
		}
		else
		{
			$defaultLanguage = LanguageTable::getList([
				'select' => ['ID'],
				'filter' => ['=ACTIVE' => 'Y', '=DEF' => 'Y'],
			])->fetch()['ID'] ?? null;
		}

		if(!is_string($defaultLanguage))
		{
			$defaultLanguage = 'en';
		}

		if (!in_array($defaultLanguage, ['ru', 'en', 'de']))
		{
			$defaultLanguage = Loc::getDefaultLang($defaultLanguage);
		}

		return $defaultLanguage;
	}

	/**
	 * @throws ReflectionException
	 */
	public function loadTranslations(string $className, string $language): void
	{
		$reflector = new \ReflectionClass($className);
		$langFile = $reflector->getFileName();
		Loc::loadLanguageFile($langFile, $language);
	}

	public function getStatusMessage(string $status, string $language): string
	{
		return Loc::getMessage('CRM_SMART_B2E_DOCUMENT_STATUS_' . $status, null, $language) ?? '';
	}
}
