<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

\Bitrix\Main\Loader::includeModule('documentgenerator');

use Bitrix\Crm\EntityAddress;
use Bitrix\DocumentGenerator\DataProvider\HashDataProvider;
use Bitrix\Main\Localization\Loc;

class Address extends HashDataProvider
{
	protected $typeId;

	/**
	 * Returns list of value names for this Provider.
	 *
	 * @return array
	 */
	public function getFields()
	{
		$fields = [];
		foreach ($this->getAddressFields() as $placeholder)
		{
			$fields[$placeholder] = ['TITLE' => EntityAddress::getLabel($placeholder, $this->getTypeId())];
		}

		$fields['TYPE'] = [
			'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_ADDRESS_TYPE_TITLE'),
			'VALUE' => function() {
				return \Bitrix\Crm\EntityAddressType::getDescription($this->getTypeId());
			}
		];

		$fields['TEXT'] = [
			'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_ADDRESS_TEXT_TITLE'),
			'VALUE' => function()
			{
				return $this->formatText();
			},
			'TYPE' => \Bitrix\Crm\Integration\DocumentGenerator\Value\Address::class,
		];

		return $fields;
	}

	/**
	 * @return string
	 */
	protected function formatText()
	{
		return new \Bitrix\Crm\Integration\DocumentGenerator\Value\Address($this->data);
	}

	/**
	 * @return int|null
	 */
	protected function getTypeId()
	{
		return $this->options['TYPE_ID'] ?? null;
	}

	/**
	 * @return array
	 */
	protected function getAddressFields()
	{
		return [
			'ADDRESS_1',
			'ADDRESS_2',
			'CITY',
			'POSTAL_CODE',
			'REGION',
			'PROVINCE',
			'COUNTRY',
			//'COUNTRY_CODE',
			//'LOC_ADDR_ID',
		];
	}

	/**
	 * @return string|null
	 */
	protected function getCountryId()
	{
		return $this->options['COUNTRY_ID'] ?? null;
	}
}
