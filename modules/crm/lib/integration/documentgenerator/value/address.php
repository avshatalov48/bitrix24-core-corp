<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\Value;

\Bitrix\Main\Loader::includeModule('documentgenerator');

use Bitrix\Crm\Format\AddressFormatter;
use Bitrix\Crm\Format\AddressSeparator;
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
	public function toString($modifier = null): string
	{
		if(is_string($this->value))
		{
			return $this->value;
		}
		if(!is_array($this->value))
		{
			return '';
		}
		$options = $this->getOptions($modifier);
		$options['SEPARATOR'] = (int)$options['SEPARATOR'];
		$options['FORMAT'] = (int)$options['FORMAT'];
		$options['SHOW_TYPE'] = $options['SHOW_TYPE'] ?? null;

		$addressFormatter = AddressFormatter::getSingleInstance();
		switch ($options['SEPARATOR'])
        {
            case AddressSeparator::Comma:
                $result = $addressFormatter->formatTextComma($this->value, $options['FORMAT']);
                break;
            case AddressSeparator::NewLine:
                $result = $addressFormatter->formatTextMultiline($this->value, $options['FORMAT']);
                break;
            case AddressSeparator::HtmlLineBreak:
                $result = $addressFormatter->formatHtmlMultiline($this->value, $options['FORMAT']);
                break;
            default:
                $result = $addressFormatter->formatTextComma($this->value, $options['FORMAT']);
        }
        unset($addressFormatter);

        if($options['SHOW_TYPE'] === true && !empty($this->value['TYPE']))
		{
			$separator = AddressSeparator::getSeparator($options['SEPARATOR']);
			$separator = str_replace(',', '', $separator);

			$result .= $separator . '(' . $this->value['TYPE'] . ')';
		}

		return $result;
	}

	/**
	 * @return array
	 */
	protected static function getDefaultOptions(): array
	{
		return [
			'SEPARATOR' => AddressSeparator::Comma,
			'FORMAT' => RequisiteAddressFormatter::getFormatByCountryId(Requisite::getCountryIdByRegion(DataProviderManager::getInstance()->getRegion())),
		];
	}

	protected static function getAliases(): array
	{
		return [
			'Separator' => 'SEPARATOR',
			'Format' => 'FORMAT',
			'ShowType' => 'SHOW_TYPE',
		];
	}

	/**
	 * @return string
	 */
	public static function getLangName(): ?string
	{
		Loc::loadLanguageFile(__FILE__);

		return Loc::getMessage('CRM_DOCGEN_VALUE_ADDRESS_TITLE');
	}
}