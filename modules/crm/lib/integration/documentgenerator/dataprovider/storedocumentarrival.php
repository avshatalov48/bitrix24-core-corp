<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\Catalog\ContractorTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Catalog\v2\Contractor\Provider\Manager;

/**
 * Class StoreDocumentArrival
 *
 * @package Bitrix\Crm\Integration\DocumentGenerator\DataProvider
 */
class StoreDocumentArrival extends StoreDocument
{
	/**
	 * @inheritDoc
	 */
	public static function getLangName()
	{
		return Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_SD_ARRIVAL_TITLE');
	}

	/**
	 * @inheritDoc
	 */
	public function getFields()
	{
		if (!is_null($this->fields))
		{
			return $this->fields;
		}

		$fields = [
			'DOCUMENT_CONTRACTOR_NAME' => [
				'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_SD_ARRIVAL_FLD_DOCUMENT_CONTRACTOR_NAME'),
				'VALUE' => [$this, 'getDocumentContractorName'],
			],
			'DOCUMENT_CONTRACTOR_COMPANY' => [
				'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_SD_ARRIVAL_FLD_DOCUMENT_CONTRACTOR_COMPANY'),
				'VALUE' => [$this, 'getDocumentContractorCompany'],
			],
			'DOCUMENT_CONTRACTOR_PERSON_NAME' => [
				'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_SD_ARRIVAL_FLD_DOCUMENT_CONTRACTOR_PERSON_NAME'),
				'VALUE' => [$this, 'getDocumentContractorPersonName'],
			],
			'DOCUMENT_CONTRACTOR_PHONE' => [
				'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_SD_ARRIVAL_FLD_DOCUMENT_CONTRACTOR_PHONE'),
				'VALUE' => [$this, 'getDocumentContractorPhone'],
			],
			'DOCUMENT_CONTRACTOR_INN' => [
				'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_SD_ARRIVAL_FLD_DOCUMENT_CONTRACTOR_INN'),
				'VALUE' => [$this, 'getDocumentContractorInn'],
			],
			'DOCUMENT_CONTRACTOR_KPP' => [
				'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_SD_ARRIVAL_FLD_DOCUMENT_CONTRACTOR_KPP'),
				'VALUE' => [$this, 'getDocumentContractorKpp'],
			],
			'DOCUMENT_CONTRACTOR_ADDRESS' => [
				'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_SD_ARRIVAL_FLD_DOCUMENT_CONTRACTOR_ADDRESS'),
				'VALUE' => [$this, 'getDocumentContractorAddress'],
			],
		];

		$this->fields = array_merge(parent::getFields(), $fields);

		return $this->fields;
	}

	/**
	 * @return array
	 */
	protected function getGetListParameters()
	{
		return array_merge_recursive(parent::getGetListParameters(), [
			'select' => [
				'CONTRACTOR_PERSON_TYPE' => 'CONTRACTOR.PERSON_TYPE',
				'CONTRACTOR_PERSON_NAME' => 'CONTRACTOR.PERSON_NAME',
				'CONTRACTOR_PHONE' => 'CONTRACTOR.PHONE',
				'CONTRACTOR_COMPANY' => 'CONTRACTOR.COMPANY',
				'CONTRACTOR_INN' => 'CONTRACTOR.INN',
				'CONTRACTOR_KPP' => 'CONTRACTOR.KPP',
				'CONTRACTOR_ADDRESS' => 'CONTRACTOR.ADDRESS',
			],
		]);
	}

	/**
	 * @inheritDoc
	 */
	protected function fetchData()
	{
		parent::fetchData();

		if (!Loader::includeModule('catalog'))
		{
			return;
		}

		if (Manager::getActiveProvider())
		{
			$contractor = Manager::getActiveProvider()::getContractorByDocumentId((int)$this->data['ID']);
			if ($contractor)
			{
				$this->data['CONTRACTOR_PERSON_NAME'] = $contractor->getContactPersonFullName();
				$this->data['CONTRACTOR_PHONE'] = $contractor->getPhone();
				$this->data['CONTRACTOR_NAME'] = $contractor->getName();
				$this->data['CONTRACTOR_COMPANY'] = $contractor->getName();
				$this->data['CONTRACTOR_INN'] = $contractor->getInn();
				$this->data['CONTRACTOR_KPP'] = $contractor->getKpp();
				$this->data['CONTRACTOR_ADDRESS'] = $contractor->getAddress();
			}
		}
		else
		{
			$contractorName = null;
			if ($this->data['CONTRACTOR_PERSON_TYPE'] === ContractorTable::TYPE_INDIVIDUAL)
			{
				$contractorName = $this->data['CONTRACTOR_PERSON_NAME'];
			}
			elseif ($this->data['CONTRACTOR_PERSON_TYPE'] === ContractorTable::TYPE_COMPANY)
			{
				$contractorName = $this->data['CONTRACTOR_COMPANY'];
			}
			$this->data['CONTRACTOR_NAME'] = $contractorName;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function isPrintable(): Result
	{
		return new Result();
	}

	/**
	 * @return string
	 */
	public function getDocumentContractorName(): string
	{
		return (string)$this->data['CONTRACTOR_NAME'];
	}

	/**
	 * @return string
	 */
	public function getDocumentContractorCompany(): string
	{
		return (string)$this->data['CONTRACTOR_COMPANY'];
	}

	/**
	 * @return string
	 */
	public function getDocumentContractorPersonName(): string
	{
		return (string)$this->data['CONTRACTOR_PERSON_NAME'];
	}

	/**
	 * @return string
	 */
	public function getDocumentContractorPhone(): string
	{
		return (string)$this->data['CONTRACTOR_PHONE'];
	}

	/**
	 * @return string
	 */
	public function getDocumentContractorInn(): string
	{
		$result = (is_null($this->data['CONTRACTOR_INN']) || $this->data['CONTRACTOR_INN'] === '')
			? '-'
			: $this->data['CONTRACTOR_INN']
		;

		return (string)$result;
	}

	/**
	 * @return string
	 */
	public function getDocumentContractorKpp(): string
	{
		return (string)$this->data['CONTRACTOR_KPP'];
	}

	/**
	 * @return string
	 */
	public function getDocumentContractorAddress(): string
	{
		return (string)$this->data['CONTRACTOR_ADDRESS'];
	}
}
