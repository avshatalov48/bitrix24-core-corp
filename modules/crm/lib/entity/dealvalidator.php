<?php
namespace Bitrix\Crm\Entity;

use Bitrix\Main\Localization\Loc;

class DealValidator extends EntityValidator
{
	/** @var array|null */
	protected $fieldInfos = null;
	/** @var DealClientValidator|null  */
	protected $clientValidator = null;

	public function __construct($entityID, array $entityFields)
	{
		parent::__construct($entityID, $entityFields);
		$this->clientValidator = new DealClientValidator($entityID, $entityFields);
	}

	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Deal;
	}

	public function getFieldInfos()
	{
		if($this->fieldInfos === null)
		{
			$this->fieldInfos = \CCrmDeal::GetFieldsInfo();
		}
		return $this->fieldInfos;
	}
	public function checkFieldPresence($fieldName, array &$messages = null)
	{
		$message = null;
		if($fieldName === 'OPPORTUNITY_WITH_CURRENCY')
		{
			$result = !$this->isNeedToCheck('OPPORTUNITY')
				||  (isset($this->entityFields['OPPORTUNITY']) && $this->entityFields['OPPORTUNITY'] > 0);

			if(!$result)
			{
				$message = Loc::getMessage(
					'CRM_ENTITY_VALIDATOR_FIELD_MUST_BE_GREATER_THEN_ZERO',
					array('%FIELD_NAME%' => \CCrmDeal::GetFieldCaption('OPPORTUNITY'))
				);
			}
		}
		elseif($fieldName === 'CLIENT')
		{
			$result = $this->clientValidator->checkPresence();
		}
		else
		{
			if($fieldName === 'OBSERVER')
			{
				$effectiveFieldName = 'OBSERVER_IDS';
			}
			else
			{
				$effectiveFieldName = $fieldName;
			}
			$result = $this->innerCheckFieldPresence($effectiveFieldName);
		}

		if(!$result)
		{
			if($message === null)
			{
				$message = Loc::getMessage(
					'CRM_ENTITY_VALIDATOR_FIELD_IS_MISSING',
					array('%FIELD_NAME%' => \CCrmDeal::GetFieldCaption($fieldName))
				);
			}

			if(!is_array($messages))
			{
				$messages = array();
			}
			$messages[] = array('id' => $fieldName, 'text' => $message);
		}
		return $result;
	}
}