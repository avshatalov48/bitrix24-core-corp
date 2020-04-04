<?php
namespace Bitrix\Crm\Entity;

abstract class ClientValidator extends FieldValidator
{
	public function __construct($entityTypeID, $entityID, array $entityFields)
	{
		parent::__construct($entityTypeID, $entityID, $entityFields);
	}

	abstract protected function getCompanyID();
	abstract protected function getContactIDs();
	abstract public function isNeedToCheck();

	public function checkPresence(array $params = null)
	{
		if(!$this->isNeedToCheck())
		{
			return true;
		}

		if($this->getCompanyID() > 0)
		{
			return true;
		}

		$contactIDs = $this->getContactIDs();
		foreach($contactIDs as $contactID)
		{
			if($contactID > 0)
			{
				return true;
			}
		}
		return false;
	}
}