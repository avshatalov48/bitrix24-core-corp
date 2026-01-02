<?php

namespace Bitrix\Mobile\Collab\Provider;

use Bitrix\Main\Loader;

class LanguageProvider
{
	public function getLanguages(): array
	{
		if (!Loader::includeModule("intranet"))
		{
			return [];
		}

		$languages = (new \Bitrix\Intranet\Public\Provider\Portal\LanguageProvider())->getPublicArray();
		if (!empty($languages[LANGUAGE_ID]))
		{
			$languages[LANGUAGE_ID]['IS_DEFAULT_LANGUAGE'] = true;
		}

		return $languages;
	}
}