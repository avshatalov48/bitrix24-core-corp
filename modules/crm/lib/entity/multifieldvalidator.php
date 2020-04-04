<?php
namespace Bitrix\Crm\Entity;

class MultifieldValidator extends FieldValidator
{
	public function __construct($entityTypeID, $entityID, array $entityFields)
	{
		parent::__construct($entityTypeID, $entityID, $entityFields);
	}

	public function isNeedToCheck()
	{
		return $this->entityID <= 0 || array_key_exists('FM', $this->entityFields);
	}

	public function checkPresence(array $params = null)
	{
		if(!$this->isNeedToCheck())
		{
			return true;
		}

		if(!is_array($params))
		{
			$params = array();
		}

		$typeID = isset($params['TYPE_ID']) ? $params['TYPE_ID'] : '';
		if(!\CCrmFieldMulti::IsSupportedType($typeID))
		{
			return isset($this->entityFields['FM'])
				&& is_array($this->entityFields['FM'])
				&& !empty($this->entityFields['FM']);
		}

		if(isset($this->entityFields['FM'])
			&& is_array($this->entityFields['FM'])
			&& isset($this->entityFields['FM'][$typeID])
			&& is_array($this->entityFields['FM'][$typeID])
		)
		{
			foreach($this->entityFields['FM'][$typeID] as $value)
			{
				if(isset($value['VALUE']) && $value['VALUE'] !== '')
				{
					return true;
				}
			}
		}
		return false;
	}
}