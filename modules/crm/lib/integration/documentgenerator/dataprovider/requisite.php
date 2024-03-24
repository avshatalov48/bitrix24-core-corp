<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\EntityRequisite;
use Bitrix\DocumentGenerator\Value\Name;

class Requisite extends BaseRequisite
{
	protected $nameData;
	protected $rawNameValues = [];

	/**
	 * Returns list of value names for this Provider.
	 *
	 * @return array
	 */
	public function getFields()
	{
		if($this->fields === null)
		{
			parent::getFields();
			foreach($this->getSmartNameFields() as $placeholder)
			{
				$this->fields[$placeholder]['TYPE'] = static::FIELD_TYPE_NAME;
				$this->fields[$placeholder]['VALUE'] = [$this, 'getNameValue'];
			}
			$this->fields['RQ_FIRST_NAME']['TYPE'] = static::FIELD_TYPE_NAME;
			$this->fields['RQ_FIRST_NAME']['VALUE'] = [$this, 'getNameValue'];

			$this->fields['RQ_SECOND_NAME']['TYPE'] = static::FIELD_TYPE_NAME;
			$this->fields['RQ_SECOND_NAME']['VALUE'] = [$this, 'getNameValue'];

			$this->fields['RQ_LAST_NAME']['TYPE'] = static::FIELD_TYPE_NAME;
			$this->fields['RQ_LAST_NAME']['VALUE'] = [$this, 'getNameValue'];

			$this->fields['RQ_SIGNATURE']['TYPE'] = static::FIELD_TYPE_STAMP;

			$this->fields['RQ_STAMP']['TYPE'] = static::FIELD_TYPE_STAMP;

			$this->fields['RQ_PHONE']['TYPE'] = 'PHONE';

			$this->fields = array_merge($this->fields, $this->getAddressFields());
		}

		return $this->fields;
	}

	/**
	 * @return array
	 */
	public function getAddressFields()
	{
		static $addressFields = false;

		if($addressFields === false)
		{
			$addressFields = [];
			foreach (EntityAddressType::getAvailableIds() as $addresTypeId)
			{
				$addresTypeName = EntityAddressType::resolveName($addresTypeId);
				$addresTypeTitle = EntityAddressType::getDescription($addresTypeId);
				$addressFields[$addresTypeName.'_ADDRESS'] = [
					'TITLE' => $addresTypeTitle,
					'PROVIDER' => Address::class,
					'VALUE' => $addresTypeName.'_ADDRESS_RAW',
					'OPTIONS' => [
						'TYPE_ID' => $addresTypeId,
						'COUNTRY_ID' => $this->getDocumentCountryId(),
					],
				];
			}
		}

		return $addressFields;
	}

	/**
	 * Loads data from the database.
	 *
	 * @return array|false
	 */
	protected function fetchData()
	{
		if(!$this->isLoaded())
		{
			if($this->source > 0)
			{
				$resuisite = EntityRequisite::getSingleInstance();
				$this->data = $resuisite?->getList(
					['select' => ['*', 'UF_*',], 'filter' => ['ID' => $this->source]]
				)->fetch();
				$this->data = array_merge($this->data, $resuisite?->getRqListFieldValueTitles($this->data));
				$this->loadAddresses();
				$this->nameData = [
					'NAME' => $this->data['RQ_FIRST_NAME'],
					'SECOND_NAME' => $this->data['RQ_SECOND_NAME'],
					'LAST_NAME' => $this->data['RQ_LAST_NAME'],
				];
				$this->rawNameValues['RQ_FIRST_NAME'] = $this->data['RQ_FIRST_NAME'];
				$this->rawNameValues['RQ_SECOND_NAME'] = $this->data['RQ_SECOND_NAME'];
				$this->rawNameValues['RQ_LAST_NAME'] = $this->data['RQ_LAST_NAME'];

				unset(
					$this->data['RQ_FIRST_NAME'],
					$this->data['RQ_SECOND_NAME'],
					$this->data['RQ_LAST_NAME']
				);

				foreach($this->getSmartNameFields() as $placeholder)
				{
					$this->rawNameValues[$placeholder] = $this->data[$placeholder];
					unset($this->data[$placeholder]);
				}
			}
		}

		return $this->data;
	}

	/**
	 * @return array
	 */
	protected function getSmartNameFields()
	{
		return [
			'RQ_NAME', 'RQ_CEO_NAME', 'RQ_ACCOUNTANT', 'RQ_DIRECTOR', 'RQ_CONTACT'
		];
	}

	protected function loadAddresses()
	{
		$addresses = EntityRequisite::getAddresses($this->source);
		foreach($addresses as $typeId => $address)
		{
			$fieldName = $this->getAddressFieldNameByTypeId($typeId);
			if($fieldName)
			{
				$this->data[$fieldName.'_RAW'] = $address;
			}
		}
	}

	/**
	 * @param int $addressTypeId
	 * @return string|null
	 */
	protected function getAddressFieldNameByTypeId($addressTypeId)
	{
		static $types = null;
		if($types === null)
		{
			$types = [];
			foreach (EntityAddressType::getAvailableIds() as $typeId)
			{
				$types[$typeId] = EntityAddressType::resolveName($typeId).'_ADDRESS';
			}
		}

		if(isset($types[$addressTypeId]))
		{
			return $types[$addressTypeId];
		}

		return null;
	}

	/**
	 * @return array
	 */
	protected function getInterfaceLanguageTitles()
	{
		if($this->interfaceTitles === null)
		{
			$this->interfaceTitles = EntityRequisite::getSingleInstance()->getFieldsTitles($this->getInterfaceCountryId());
		}

		return $this->interfaceTitles;
	}

	/**
	 * @return array
	 */
	protected function getDocumentLanguageTitles()
	{
		if($this->documentTitles === null)
		{
			$documentRegion = $this->getDocumentCountryId();
			if($documentRegion == $this->getInterfaceCountryId())
			{
				$this->documentTitles = $this->getInterfaceLanguageTitles();
			}
			else
			{
				$this->documentTitles = EntityRequisite::getSingleInstance()->getFieldsTitles($documentRegion);
			}
		}

		return $this->documentTitles;
	}

	/**
	 * @param $placeholder
	 * @return Name
	 */
	public function getNameValue($placeholder)
	{
		if ($placeholder === 'RQ_FIRST_NAME')
		{
			return new Name($this->nameData, ['format' => '#NAME#']);
		}
		elseif ($placeholder === 'RQ_SECOND_NAME')
		{
			return new Name($this->nameData, ['format' => '#SECOND_NAME#']);
		}
		elseif ($placeholder === 'RQ_LAST_NAME')
		{
			return new Name($this->nameData, ['format' => '#LAST_NAME#']);
		}
		elseif (in_array($placeholder, $this->getSmartNameFields()))
		{
			$data = $this->getNameDataFromString($this->rawNameValues[$placeholder]);
			if ($data)
			{
				return new Name($data, ['format' => Contact::getNameFormat()]);
			}
			else
			{
				return $this->rawNameValues[$placeholder];
			}
		}
	}

	/**
	 * @param $name
	 * @return array|false
	 */
	protected function getNameDataFromString($name)
	{
		$nameParts = explode(' ', $name);
		$lastName = $nameParts[0] ?? '';
		$firstName = $nameParts[1] ?? '';
		$secondName = $nameParts[2] ?? '';

		if(!empty($firstName) && !empty($secondName))
		{
			return [
				'NAME' => $firstName,
				'SECOND_NAME' => $secondName,
				'LAST_NAME' => $lastName,
			];
		}

		return false;
	}

	public function getRawNameValue(string $placeholder): ?string
	{
		return $this->rawNameValues[$placeholder] ?? null;
	}
}
