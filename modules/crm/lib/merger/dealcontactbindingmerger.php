<?php
namespace Bitrix\Crm\Merger;
use \Bitrix\Crm\Binding;

class DealContactBindingMerger extends EntityBindingMerger
{
	public function __construct()
	{
		parent::__construct(\CCrmOwnerType::Contact, 'CONTACT_BINDINGS');
	}

	protected function getBindings(array $entityFields)
	{
		$entityID = isset($entityFields['ID']) ? (int)$entityFields['ID'] : 0;
		if($entityID > 0)
		{
			return Binding\DealContactTable::getDealBindings($entityID);
		}
		elseif(isset($entityFields['CONTACT_BINDINGS']) && is_array($entityFields['CONTACT_BINDINGS']))
		{
			return $entityFields['CONTACT_BINDINGS'];
		}
		elseif(isset($entityFields['CONTACT_ID'])
			|| (isset($entityFields['CONTACT_IDS']) && is_array($entityFields['CONTACT_IDS']))
		)
		{
			$contactBindings = Binding\EntityBinding::prepareEntityBindings(
				\CCrmOwnerType::Contact,
				isset($entityFields['CONTACT_IDS']) && is_array($entityFields['CONTACT_IDS'])
					? $entityFields['CONTACT_IDS']
					: array($entityFields['CONTACT_ID'])
			);
			Binding\EntityBinding::markFirstAsPrimary($contactBindings);
			return $contactBindings;
		}
		return null;
	}
	protected function getMappedIDs(array $map)
	{
		if(!isset($map['CONTACT_IDS']))
		{
			return null;
		}

		return isset($map['CONTACT_IDS']['SOURCE_ENTITY_IDS']) && is_array($map['CONTACT_IDS']['SOURCE_ENTITY_IDS'])
			? $map['CONTACT_IDS']['SOURCE_ENTITY_IDS'] : array();
	}
}