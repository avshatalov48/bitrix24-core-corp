<?php
namespace Bitrix\Crm\Merger;
use \Bitrix\Crm\Binding;

class ContactCompanyBindingMerger extends EntityBindingMerger
{
	public function __construct()
	{
		parent::__construct(\CCrmOwnerType::Company, 'COMPANY_BINDINGS');
	}

	protected function getBindings(array $entityFields)
	{
		$entityID = isset($entityFields['ID']) ? (int)$entityFields['ID'] : 0;
		if($entityID > 0)
		{
			return Binding\ContactCompanyTable::getContactBindings($entityID);
		}
		elseif(isset($entityFields['COMPANY_BINDINGS']) && is_array($entityFields['COMPANY_BINDINGS']))
		{
			return $entityFields['COMPANY_BINDINGS'];
		}
		elseif(isset($entityFields['COMPANY_ID'])
			|| (isset($entityFields['COMPANY_IDS']) && is_array($entityFields['COMPANY_IDS']))
		)
		{
			$companyBindings = Binding\EntityBinding::prepareEntityBindings(
				\CCrmOwnerType::Company,
				isset($entityFields['COMPANY_IDS']) && is_array($entityFields['COMPANY_IDS'])
					? $entityFields['COMPANY_IDS']
					: array($entityFields['COMPANY_ID'])
			);
			Binding\EntityBinding::markFirstAsPrimary($companyBindings);
			return $companyBindings;
		}
		return null;
	}
	protected function getMappedIDs(array $map)
	{
		if(!isset($map['COMPANY_IDS']))
		{
			return null;
		}

		return isset($map['COMPANY_IDS']['SOURCE_ENTITY_IDS']) && is_array($map['COMPANY_IDS']['SOURCE_ENTITY_IDS'])
			? $map['COMPANY_IDS']['SOURCE_ENTITY_IDS'] : array();
	}
}