<?php
namespace Bitrix\Crm\Import;
use Bitrix\Main;
use Bitrix\Crm\VCard;

class VCardImport
{
	protected $fields = null;
	protected $mappedFields = null;
	protected $mappedMultiFields = null;

	/**
	* @return array
	*/
	public function mapContact(VCard\VCardElement $element)
	{
		$this->fields = array();
		$this->mappedFields = array();
		$this->mappedMultiFields = array();

		$this->tryMapNameAttribute($element, $this->fields);
		$this->tryMapBirthdayAttribute($element, $this->fields);
		$this->tryMapAddressAttribute($element, array('pref', 'work', 'home', ''), $this->fields);
		$this->tryMapTelAttribute($element, $this->fields);
		$this->tryMapEmailAttribute($element, $this->fields);
		$this->tryMapUrlAttribute($element, $this->fields);
		$this->tryMapFileAttribute($element, 'PHOTO', 'PHOTO', $this->fields);
		$this->tryMapFileAttribute($element, 'LOGO', 'COMPANY_LOGO', $this->fields);

		$this->tryMapAttribute($element, 'ORG', 'COMPANY_TITLE', $this->fields);
		$this->tryMapAttribute($element, 'TITLE', 'POST', $this->fields);
		$this->tryMapAttribute($element, 'NOTE', 'COMMENTS', $this->fields);

		return !empty($this->fields);
	}

	public function getFields()
	{
		return $this->fields;
	}

	public function getMappedFields()
	{
		return $this->mappedFields;
	}

	public function getMappedMultiFields()
	{
		return $this->mappedMultiFields;
	}

	/**
	* @return bool
	*/
	protected function tryMapNameAttribute(VCard\VCardElement $element, array &$fields)
	{
		$attr = $element->getFirstAttributeByName('N');
		if($attr === null)
		{
			return false;
		}

		$ary = explode(';', $attr->getValue());
		$qty = count($ary);

		$fields['LAST_NAME'] = trim($ary[0]);
		$fields['NAME'] = $qty > 1 ? trim($ary[1]) : '';
		$fields['SECOND_NAME'] = $qty > 2 ? trim($ary[2]) : '';

		$this->mappedFields[] = 'LAST_NAME';
		$this->mappedFields[] = 'NAME';
		$this->mappedFields[] = 'SECOND_NAME';

		return true;
	}

	/**
	* @return bool
	*/
	protected function tryMapBirthdayAttribute(VCard\VCardElement $element, array &$fields)
	{
		$attr = $element->getFirstAttributeByName('BDAY');
		if($attr === null)
		{
			return false;
		}

		$time = strtotime($attr->getValue());
		if($time === false)
		{
			return false;
		}

		$fields['BIRTHDATE'] = ConvertTimeStamp($time, 'SHORT');
		$this->mappedFields[] = 'BIRTHDATE';
		return true;
	}

	/**
	* @return bool
	*/
	protected function tryMapAttribute(VCard\VCardElement $element, $attrName, $fildName, array &$fields)
	{
		$attr = $element->getFirstAttributeByName($attrName);
		if($attr === null)
		{
			return false;
		}

		$fields[$fildName] = $attr->getValue();
		$this->mappedFields[] = $fildName;
		return true;
	}

	/**
	* @return bool
	*/
	protected function tryMapAddressAttribute(VCard\VCardElement $element, array $types, array &$fields)
	{
		foreach($types as $type)
		{
			$attr = $type !== ''
				? $element->findAttribute('ADR', array('TYPE' => $type))
				: $element->findAttribute('ADR');

			if($attr === null)
			{
				continue;
			}

			$addr = VCard\VCardAddress::createFromAttribute($attr);
			if($addr === null)
			{
				continue;
			}

			$fields['ADDRESS']= $addr->getStreet();

			$pobox = $addr->getPostOfficeBox();
			$ext = $addr->getExtendedAddress();
			if($pobox !== '' && $ext !== '')
			{
				$fields['ADDRESS_2'] = "{$pobox}, {$ext}";
			}
			elseif($pobox !== '')
			{
				$fields['ADDRESS_2'] = $pobox;
			}
			elseif($ext !== '')
			{
				$fields['ADDRESS_2'] = $ext;
			}
			
			$fields['ADDRESS_CITY'] = $addr->getLocality();
			$fields['ADDRESS_PROVINCE'] = $addr->getRegion();
			$fields['ADDRESS_POSTAL_CODE'] = $addr->getCode();
			$fields['ADDRESS_COUNTRY'] = $addr->getCountry();

			$this->mappedFields[] = 'ADDRESS';
			return true;
		}

		return false;
	}

	/**
	* @return bool
	*/
	protected function tryMapTelAttribute(VCard\VCardElement $element, array &$fields)
	{
		$attrs = $element->getAttributesByName('TEL');
		$mappedMultiFields = array();
		$qty = 0;
		foreach($attrs as $attr)
		{
			$phone = VCard\VCardPhone::createFromAttribute($attr);
			if($phone === null)
			{
				continue;
			}

			$value = $phone->getMultiFieldValue();
			if($value === '')
			{
				continue;
			}

			$valueType = $phone->getMultiFieldValueType();

			if(!isset($fields['FM']))
			{
				$fields['FM'] = array();
			}

			if(!isset($fields['FM']['PHONE']))
			{
				$fields['FM']['PHONE'] = array();
			}

			$qty++;
			$fields['FM']['PHONE']["n{$qty}"] = array('VALUE' => $value, 'VALUE_TYPE' => $valueType);

			if(!isset($mappedMultiFields[$valueType]))
			{
				$mappedMultiFields[$valueType] = true;
			}
		}

		if(!empty($mappedMultiFields))
		{
			$this->mappedMultiFields['PHONE'] = array_keys($mappedMultiFields);
		}
		return $qty > 0;
	}

	/**
	* @return bool
	*/
	protected function tryMapEmailAttribute(VCard\VCardElement $element, array &$fields)
	{
		$attrs = $element->getAttributesByName('EMAIL');
		$mappedMultiFields = array();
		$qty = 0;
		foreach($attrs as $attr)
		{
			$email = VCard\VCardEmail::createFromAttribute($attr);
			if($email === null)
			{
				continue;
			}

			$value = $email->getMultiFieldValue();
			if($value === '')
			{
				continue;
			}

			$valueType = $email->getMultiFieldValueType();

			if(!isset($fields['FM']))
			{
				$fields['FM'] = array();
			}

			if(!isset($fields['FM']['EMAIL']))
			{
				$fields['FM']['EMAIL'] = array();
			}

			$qty++;
			$fields['FM']['EMAIL']["n{$qty}"] = array('VALUE' => $value, 'VALUE_TYPE' => $valueType);

			if(!isset($mappedMultiFields[$valueType]))
			{
				$mappedMultiFields[$valueType] = true;
			}
		}

		if(!empty($mappedMultiFields))
		{
			$this->mappedMultiFields['EMAIL'] = array_keys($mappedMultiFields);
		}
		return $qty > 0;
	}

	/**
	* @return bool
	*/
	protected function tryMapUrlAttribute(VCard\VCardElement $element, array &$fields)
	{
		$attrs = $element->getAttributesByName('URL');
		$qty = 0;
		foreach($attrs as $attr)
		{
			$value = $attr->getValue();
			if($value === '')
			{
				continue;
			}

			if(!isset($fields['FM']))
			{
				$fields['FM'] = array();
			}

			if(!isset($fields['FM']['WEB']))
			{
				$fields['FM']['WEB'] = array();
			}

			$qty++;
			$fields['FM']['WEB']["n{$qty}"] = array('VALUE' => $value, 'VALUE_TYPE' => 'WORK');
		}

		if($qty > 0)
		{
			$this->mappedMultiFields['WEB'] = array('WORK');
		}
		return $qty > 0;
	}

	/**
	* @return bool
	*/
	protected function tryMapFileAttribute(VCard\VCardElement $element, $attrName, $fieildName, array &$fields)
	{
		$attr = $element->getFirstAttributeByName($attrName);
		if($attr === null)
		{
			return false;
		}

		$file = VCard\VCardFile::createFromAttribute($attr);
		if($file === null)
		{
			return false;
		}

		$fileInfo = $file->getFileInfo();
		if($fileInfo === null)
		{
			return false;
		}

		$fields[$fieildName] = array_merge($fileInfo, array('MODULE_ID' => 'crm'));
		$this->mappedFields[] = $fieildName;
		return true;
	}
}