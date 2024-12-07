<?php

namespace Bitrix\AI\Entity;

trait TranslateTrait
{
	/**
	 * Return translate by translates and langCode
	 *
	 * @param array $translates
	 * @param string $langCode
	 * @param string $defaultTitle
	 * @return string
	 */
	static private function translate(array $translates, string $langCode, string $defaultTitle = ''): string
	{
		if (empty($translates))
		{
			return $defaultTitle;
		}

		if (isset($translates[$langCode]))
		{
			return $translates[$langCode];
		}

		if (isset($translates['en']))
		{
			return $translates['en'];
		}

		return $translates[array_key_first($translates)];
	}
}
