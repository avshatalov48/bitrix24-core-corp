<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\Value;

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
	public function toString($modifier = null)
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
			$result = Number2Word_Rus($this->value, $isMoney, $options['CURRENCY_ID']);
			if($result != '')
			{
				return $result;
			}
		}
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
		$regionLanguageId = DataProviderManager::getInstance()->getRegionLanguageId();
		if($regionLanguageId != LANGUAGE_ID && $formatString != '#')
		{
			$regionCurrencySymbol = static::getCurrencySymbol($options['CURRENCY_ID'], $regionLanguageId);
			$languageCurrencySymbol = $this->getCurrencySymbol($options['CURRENCY_ID'], LANGUAGE_ID);

			if($regionCurrencySymbol != $languageCurrencySymbol)
			{
				$result = str_replace($languageCurrencySymbol, $regionCurrencySymbol, $result);
			}
		}
		$this->enableZeros();

		return $result;
	}

	/**
	 * @param $modifier
	 * @return array
	 */
	protected function getOptions($modifier = null)
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
	protected static function getAliases()
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
	protected static function getDefaultOptions()
	{
		return [
			'CURRENCY_ID' => static::getDefaultCurrencyId(),
		];
	}

	/**
	 * @return string
	 */
	protected static function getDefaultCurrencyId()
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
				if($languageId != LANGUAGE_ID)
				{
					return static::getCurrencySymbol($currencyId,LANGUAGE_ID);
				}
				else
				{
					return false;
				}
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
	public static function getLangName()
	{
		Loc::loadLanguageFile(__FILE__);
		return Loc::getMessage('CRM_DOCGEN_VALUE_MONEY_TITLE');
	}
}