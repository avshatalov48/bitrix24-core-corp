<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\Value;

\Bitrix\Main\Loader::includeModule('documentgenerator');

use Bitrix\Crm\Format\AddressSeparator;
use Bitrix\Crm\Format\EntityAddressFormatter;
use Bitrix\Crm\Format\RequisiteAddressFormatter;
use Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Requisite;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\DocumentGenerator\Nameable;
use Bitrix\DocumentGenerator\Value;
use Bitrix\Main\Localization\Loc;

class Address extends Value implements Nameable
{
	/**
	 * @param null $modifier
	 * @return string
	 */
	public function toString($modifier = null)
	{
		if(is_string($this->value))
		{
			return $this->value;
		}
		elseif(!is_array($this->value))
		{
			return '';
		}
		$options = $this->getOptions($modifier);
		$options['SEPARATOR'] = (int)$options['SEPARATOR'];
		$options['FORMAT'] = (int)$options['FORMAT'];
		return EntityAddressFormatter::format($this->value, $options);
	}

	/**
	 * @return array
	 */
	protected static function getDefaultOptions()
	{
		return [
			'SEPARATOR' => AddressSeparator::Comma,
			'FORMAT' => RequisiteAddressFormatter::getFormatByCountryId(Requisite::getCountryIdByRegion(DataProviderManager::getInstance()->getRegion())),
		];
	}

	protected static function getAliases()
	{
		return [
			'Separator' => 'SEPARATOR',
			'Format' => 'FORMAT',
		];
	}

	/**
	 * @return string
	 */
	public static function getLangName()
	{
		Loc::loadLanguageFile(__FILE__);
		return Loc::getMessage('CRM_DOCGEN_VALUE_ADDRESS_TITLE');
	}
}