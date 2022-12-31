<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\Value;

\Bitrix\Main\Loader::includeModule('documentgenerator');

use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\DocumentGenerator\Nameable;
use Bitrix\DocumentGenerator\Value;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class Money extends Value implements Nameable
{
	protected static $currencyInfo = [];

	/**
	 * @param string $modifier
	 * @return string
	 */
	public function toString($modifier = null): string
	{
		if($this->value === null)
		{
			return '';
		}
		$options = $this->getOptions($modifier);
		if(isset($options['WORDS']) && $options['WORDS'] === true && function_exists('Number2Word_Rus'))
		{
			$isMoney = 'Y';
			if(isset($options['NO_SIGN']) && $options['NO_SIGN'] === true)
			{
				$isMoney = 'N';
			}
			$result = Number2Word_Rus(round((float)$this->value, 2), $isMoney, $options['CURRENCY_ID']);
			if($result !== '')
			{
				return $result;
			}
		}

		$isShowSign = isset($options['WITH_ZEROS']) && $options['WITH_ZEROS'] === true;

		// use CrmCurrency to safely process strings with huge numbers
		if($this->value !== '' && filter_var($this->value, FILTER_VALIDATE_INT|FILTER_VALIDATE_FLOAT) === false)
		{
			$result = $this->formatUsingCrmCurrency($options);
		}
		else
		{
			$format = \CCurrencyLang::GetFormatDescription($options['CURRENCY_ID']);

			$format['HIDE_ZERO'] = ($isShowSign ? 'N' : 'Y');

			$result = \CCurrencyLang::formatValue(
				$this->value,
				$format,
				(!isset($options['NO_SIGN']) || $options['NO_SIGN'] !== true)
			);
		}

		$regionLanguageId = DataProviderManager::getInstance()->getRegionLanguageId();
		if($regionLanguageId !== LANGUAGE_ID && $isShowSign)
		{
			$regionCurrencySymbol = static::getCurrencySymbol($options['CURRENCY_ID'], $regionLanguageId);
			$languageCurrencySymbol = static::getCurrencySymbol($options['CURRENCY_ID'], LANGUAGE_ID);

			if($regionCurrencySymbol !== $languageCurrencySymbol)
			{
				$result = str_replace($languageCurrencySymbol, $regionCurrencySymbol, $result);
			}
		}

		return $result;
	}

	protected function formatUsingCrmCurrency(array $options): string
	{
		if(isset($options['WITH_ZEROS']) && $options['WITH_ZEROS'] === true)
		{
			$this->disableZeros();
		}
		else
		{
			$this->enableZeros();
		}
		$formatString = '';
		if(isset($options['NO_SIGN']) && $options['NO_SIGN'] === true)
		{
			$formatString = '#';
		}
		$result = \CCrmCurrency::MoneyToString($this->value, $options['CURRENCY_ID'], $formatString);
		$this->enableZeros();

		return $result;
	}

	/**
	 * @param $modifier
	 * @return array
	 */
	protected function getOptions($modifier = null): array
	{
		$options = parent::getOptions($modifier);
		if(!isset($options['CURRENCY_ID']) || empty($options['CURRENCY_ID']))
		{
			$options['CURRENCY_ID'] = static::getDefaultCurrencyId();
		}

		return $options;
	}

	/**
	 * @return array
	 */
	protected static function getAliases(): array
	{
		return [
			'CurId' => 'CURRENCY_ID',
			'WZ' => 'WITH_ZEROS',
			'NS' => 'NO_SIGN',
			'W' => 'WORDS',
		];
	}

	/**
	 * @return array
	 */
	protected static function getDefaultOptions(): array
	{
		return [
			'CURRENCY_ID' => static::getDefaultCurrencyId(),
		];
	}

	/**
	 * @return string
	 */
	protected static function getDefaultCurrencyId(): string
	{
		return \CCrmCurrency::GetBaseCurrencyID();
	}

	/**
	 * Disable hide zero
	 */
	protected function disableZeros()
	{
		$maxIterations = 1000;
		while(\CCurrencyLang::isAllowUseHideZero())
		{
			if($maxIterations-- <= 0)
			{
				break;
			}
			\CCurrencyLang::disableUseHideZero();
		}
		\CCurrencyLang::disableUseHideZero();
	}

	/**
	 * Enable hide zero
	 */
	protected function enableZeros()
	{
		$maxIterations = 1000;
		while(!\CCurrencyLang::isAllowUseHideZero())
		{
			if($maxIterations-- <= 0)
			{
				break;
			}
			\CCurrencyLang::enableUseHideZero();
		}
	}

	/**
	 * @param string $currencyId
	 * @param string $languageId
	 * @return false|string
	 */
	public static function getCurrencySymbol($currencyId, $languageId)
	{
		if(Loader::includeModule('currency'))
		{
			if(!isset(static::$currencyInfo[$currencyId][$languageId]))
			{
				static::loadCurrency($currencyId, $languageId);
			}
			if(!is_array(static::$currencyInfo[$currencyId][$languageId]))
			{
				if($languageId !== LANGUAGE_ID)
				{
					return static::getCurrencySymbol($currencyId,LANGUAGE_ID);
				}

				return false;
			}

			return trim(static::$currencyInfo[$currencyId][$languageId]['FORMAT_STRING'], " \n\r\t#");
		}

		return '';
	}

	/**
	 * @param string $currencyId
	 * @param string $languageId
	 */
	protected static function loadCurrency($currencyId, $languageId)
	{
		static::$currencyInfo[$currencyId][$languageId] = \CCurrencyLang::GetByID($currencyId, $languageId);
	}

	/**
	 * @return string
	 */
	public static function getLangName(): string
	{
		Loc::loadLanguageFile(__FILE__);
		return Loc::getMessage('CRM_DOCGEN_VALUE_MONEY_TITLE');
	}
}