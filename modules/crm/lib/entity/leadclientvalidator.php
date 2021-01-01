<?php
namespace Bitrix\Crm\Entity;

use Bitrix\Crm;

class LeadClientValidator extends ClientValidator
{
	public function __construct($entityID, array $entityFields)
	{
		parent::__construct(\CCrmOwnerType::Lead, $entityID, $entityFields);
	}
	protected function getCompanyID()
	{
		return isset($this->entityFields['COMPANY_ID']) ? $this->entityFields['COMPANY_ID'] : 0;
	}
	protected function getContactIDs()
	{
		if(isset($this->entityFields['CONTACT_IDS']) && is_array($this->entityFields['CONTACT_IDS']))
		{
			return $this->entityFields['CONTACT_IDS'];
		}

		if(isset($this->entityFields['CONTACT_ID']))
		{
			return array($this->entityFields['CONTACT_ID']);
		}

		$entityID = $this->getEntityID();
		if($entityID > 0)
		{
			$this->entityFields['CONTACT_IDS'] = Crm\Binding\LeadContactTable::getLeadContactIDs($entityID);
		}
		else
		{
			$this->entityFields['CONTACT_IDS'] = array();
		}
		return $this->entityFields['CONTACT_IDS'];
	}

	public function isNeedToCheck()
	{
		return $this->entityID <= 0
			|| array_key_exists('COMPANY_ID', $this->entityFields)
			|| array_key_exists('CONTACT_ID', $this->entityFields)
			|| array_key_exists('CONTACT_IDS', $this->entityFields);
	}
}