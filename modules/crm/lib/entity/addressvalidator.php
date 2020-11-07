<?php
namespace Bitrix\Crm\Entity;

class AddressValidator extends FieldValidator
{
	protected $fieldsMap = null;

	public function __construct($entityTypeID, $entityID, array $entityFields, array $fieldsMap = null)
	{
		parent::__construct($entityTypeID, $entityID, $entityFields);
		$this->fieldsMap = is_array($fieldsMap) ? $fieldsMap : array();
	}

	protected function getFieldValue($key)
	{
		$fieldName = isset($this->fieldsMap[$key]) ? $this->fieldsMap[$key] : $key;
		return isset($this->entityFields[$fieldName]) ? $this->entityFields[$fieldName] : '';
	}

	public function isNeedToCheck()
	{
		return $this->entityID <= 0
			|| array_key_exists('ADDRESS', $this->entityFields)
			|| array_key_exists('ADDRESS_2', $this->entityFields)
			|| array_key_exists('ADDRESS_CITY', $this->entityFields)
			|| array_key_exists('ADDRESS_REGION', $this->entityFields)
			|| array_key_exists('ADDRESS_PROVINCE', $this->entityFields)
			|| array_key_exists('ADDRESS_POSTAL_CODE', $this->entityFields);
	}

	public function checkPresence(array $params = null)
	{
		if(!$this->isNeedToCheck())
		{
			return true;
		}

		return $this->getFieldValue('ADDRESS') !== ''
			|| $this->getFieldValue('ADDRESS_2') !== ''
			|| $this->getFieldValue('ADDRESS_CITY') !== ''
			|| $this->getFieldValue('ADDRESS_REGION') !== ''
			|| $this->getFieldValue('ADDRESS_PROVINCE') !== ''
			|| $this->getFieldValue('ADDRESS_POSTAL_CODE') !== ''
			|| $this->getFieldValue('ADDRESS_LOC_ADDR_ID') !== ''
			|| $this->getFieldValue('ADDRESS_LOC_ADDR') !== '';
	}
}