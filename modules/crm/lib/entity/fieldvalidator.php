<?php
namespace Bitrix\Crm\Entity;

abstract class FieldValidator
{
	protected $entityTypeID = \CCrmOwnerType::Undefined;
	protected $entityID = 0;
	protected $entityFields = null;

	public function __construct($entityTypeID, $entityID, array $entityFields)
	{
		$this->entityTypeID = $entityTypeID;
		$this->entityID = $entityID;
		$this->entityFields = $entityFields;
	}

	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}

	public function getEntityID()
	{
		return $this->entityID;
	}

	abstract public function checkPresence(array $params = null);
}