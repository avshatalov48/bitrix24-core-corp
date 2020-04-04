<?php
namespace Bitrix\Crm\Merger;
use \Bitrix\Crm\Binding;

class CompanyContactBindingMerger extends EntityBindingMerger
{
	public function __construct()
	{
		parent::__construct(
			\CCrmOwnerType::Contact,
			'CONTACT_BINDINGS',
			'CONTACT_ID'
		);
	}

	protected function getBindings(array $entityFields)
	{
		$entityID = isset($entityFields['ID']) ? (int)$entityFields['ID'] : 0;
		if($entityID > 0)
		{
			return Binding\ContactCompanyTable::getCompanyBindings($entityID);
		}
		elseif(isset($entityFields['CONTACT_BINDINGS']) && is_array($entityFields['CONTACT_BINDINGS']))
		{
			return $entityFields['CONTACT_BINDINGS'];
		}
		elseif(isset($entityFields['CONTACT_ID']))
		{
			$contactBindings = Binding\EntityBinding::prepareEntityBindings(
				\CCrmOwnerType::Contact,
				is_array($entityFields['CONTACT_ID'])
					? $entityFields['CONTACT_ID'] : array($entityFields['CONTACT_ID'])
			);
			Binding\EntityBinding::markFirstAsPrimary($contactBindings);
			return $contactBindings;
		}
		return null;
	}
	protected function getMappedIDs(array $map)
	{
		if(!isset($map['CONTACT_ID']))
		{
			return null;
		}

		return isset($map['CONTACT_ID']['SOURCE_ENTITY_IDS']) && is_array($map['CONTACT_ID']['SOURCE_ENTITY_IDS'])
			? $map['CONTACT_ID']['SOURCE_ENTITY_IDS'] : array();
	}
}