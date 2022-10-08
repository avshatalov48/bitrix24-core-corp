<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

\Bitrix\Main\Loader::includeModule('documentgenerator');

use Bitrix\Crm\Requisite\Country;
use Bitrix\DocumentGenerator\DataProvider;
use Bitrix\DocumentGenerator\DataProviderManager;

abstract class BaseRequisite extends DataProvider
{
	protected $interfaceCountryId;
	protected $documentCountryId;
	protected $interfaceTitles;
	protected $documentTitles;

	/**
	 * Returns list of value names for this Provider.
	 *
	 * @return array
	 */
	public function getFields()
	{
		if($this->fields === null)
		{
			$this->fields = [];
			$titles = $this->getInterfaceLanguageTitles();
			$documentLanguageTitles = $this->getDocumentLanguageTitles();

			foreach($titles as $placeholder => $title)
			{
				if(isset($this->getHiddenFields()[$placeholder]))
				{
					continue;
				}
				$this->fields[$placeholder] = [];
				if(empty($title) && !empty($documentLanguageTitles[$placeholder]))
				{
					$title = $documentLanguageTitles[$placeholder];
				}
				if(!empty($title))
				{
					$this->fields[$placeholder]['TITLE'] = $title;
				}
			}
		}

		return $this->fields;
	}

	/**
	 * Returns value by its name.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getValue($name)
	{
		$this->fetchData();
		return parent::getValue($name);
	}

	abstract protected function fetchData();

	/**
	 * @return int
	 */
	protected function getInterfaceCountryId()
	{
		if(!$this->interfaceCountryId)
		{
			$this->interfaceCountryId = static::getCountryIdByRegion(LANGUAGE_ID);
		}

		return $this->interfaceCountryId;
	}

	/**
	 * @return int
	 */
	protected function getDocumentCountryId()
	{
		if(!$this->documentCountryId)
		{
			$this->documentCountryId = static::getCountryIdByRegion(DataProviderManager::getInstance()->getRegion());
		}

		return $this->documentCountryId;
	}

	/**
	 * @return array
	 */
	protected static function getRegionCountryIdMap()
	{
		return [
			'ru' => Country::ID_RUSSIA,
			'by' => Country::ID_BELARUS,
			'kz' => Country::ID_KAZAKHSTAN,
			'ua' => Country::ID_UKRAINE,
			'de' => Country::ID_GERMANY,
			'en' => Country::ID_USA,
		];
	}

	/**
	 * @param string $region
	 * @return int
	 */
	public static function getCountryIdByRegion($region)
	{
		$countryId = static::getRegionCountryIdMap()[$region];
		if(!$countryId)
		{
			$countryId = Country::ID_USA;
		}

		return $countryId;
	}

	abstract protected function getInterfaceLanguageTitles();

	abstract protected function getDocumentLanguageTitles();

	/**
	 * @return array
	 */
	protected function getHiddenFields()
	{
		return [
			'ID' => 'ID',
			'ENTITY_TYPE_ID' => 'ENTITY_TYPE_ID',
			'ENTITY_ID' => 'ENTITY_ID',
			// 'PRESET_ID' => 'PRESET_ID',
			'DATE_CREATE' => 'DATE_CREATE',
			'DATE_MODIFY' => 'DATE_MODIFY',
			'CREATED_BY_ID' => 'CREATED_BY_ID',
			'MODIFY_BY_ID' => 'MODIFY_BY_ID',
			'CODE' => 'CODE',
			'XML_ID' => 'XML_ID',
			'ORIGINATOR_ID' => 'ORIGINATOR_ID',
			'ACTIVE' => 'ACTIVE',
			'SORT' => 'SORT',
			'COUNTRY_ID' => 'COUNTRY_ID',
			'RQ_ADDR' => 'RQ_ADDR',
			'ADDRESS_ONLY' => 'ADDRESS_ONLY',
		];
	}
}
