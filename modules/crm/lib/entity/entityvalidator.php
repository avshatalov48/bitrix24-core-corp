<?php
namespace Bitrix\Crm\Entity;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class EntityValidator
{
	protected $entityID = 0;
	protected $entityFields = null;

	public function __construct($entityID, array $entityFields)
	{
		$this->entityID = $entityID;
		$this->entityFields = $entityFields;
	}

	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Undefined;
	}

	public function getEntityID()
	{
		return $this->entityID;
	}

	public function getFieldInfos()
	{
		return array();
	}

	public function getFieldInfo($fieldName)
	{
		$fieldInfos = $this->getFieldInfos();
		return $fieldInfos[$fieldName] ? $fieldInfos[$fieldName] : null;
	}
	protected function isNeedToCheck($fieldName)
	{
		return $this->entityID <= 0 || array_key_exists($fieldName, $this->entityFields);
	}
	protected function checkAllFieldPresence(array $fieldNames)
	{
		foreach($fieldNames as $fieldName)
		{
			if(!$this->innerCheckFieldPresence($fieldName))
			{
				return false;
			}
		}
		return true;
	}
	protected function checkAnyFieldPresence(array $fieldNames)
	{
		foreach($fieldNames as $fieldName)
		{
			if($this->innerCheckFieldPresence($fieldName))
			{
				return true;
			}
		}
		return false;
	}
	public function checkFieldPresence($fieldName, array &$messages)
	{
		return $this->innerCheckFieldPresence($fieldName);
	}
	protected function innerCheckFieldPresence($fieldName)
	{
		if($this->entityID > 0 && !array_key_exists($fieldName, $this->entityFields))
		{
			return true;
		}

		$fieldInfo = $this->getFieldInfo($fieldName);
		$typeName = is_array($fieldInfo) && isset($fieldInfo['TYPE']) ? $fieldInfo['TYPE'] : '';
		if($typeName === 'boolean')
		{
			return true;
		}

		$value = isset($this->entityFields[$fieldName]) ? $this->entityFields[$fieldName] : null;
		if(is_array($value))
		{
			return !empty($value);
		}
		return $this->entityFields[$fieldName] <> '';
	}

	protected function innerCheckAnyFieldPresence(array $fieldNames)
	{
		foreach ($fieldNames as $fieldName)
		{
			if ($this->innerCheckFieldPresence($fieldName))
			{
				return true;
			}
		}

		return false;
	}
}