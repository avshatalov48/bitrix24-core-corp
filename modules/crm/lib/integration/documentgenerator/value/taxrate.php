<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\Value;

use Bitrix\DocumentGenerator\Nameable;
use Bitrix\DocumentGenerator\Value;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loader::includeModule('documentgenerator');

Loc::loadLanguageFile(__FILE__);

class TaxRate extends Value implements Nameable
{
	public const TAX_FREE = 'tax_free';

	/**
	 * @param string $modifier
	 * @return string
	 */
	public function toString($modifier = null): string
	{
		if ($this->value === self::TAX_FREE)
		{
			return Loc::getMessage('CRM_DOCGEN_VALUE_TAX_RATE_TAX_FREE');
		}

		$options = $this->getOptions($modifier);

		if (isset($options['WITH_PERCENT']) && $options['WITH_PERCENT'] === true)
		{
			return $this->value . '%';
		}

		return (string)$this->value;
	}

	/**
	 * @return array
	 */
	protected static function getAliases(): array
	{
		return [
			'WP' => 'WITH_PERCENT',
		];
	}

	/**
	 * @return array
	 */
	protected static function getDefaultOptions(): array
	{
		return [
			'WITH_PERCENT' => true,
		];
	}

	/**
	 * @return string
	 */
	public static function getLangName(): string
	{
		return Loc::getMessage('CRM_DOCGEN_VALUE_TAX_RATE_TITLE');
	}
}