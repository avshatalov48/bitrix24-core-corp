<?php
namespace Bitrix\Crm\Entity;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Crm\Tracking;

class LeadValidator extends EntityValidator
{
	/** @var array|null */
	protected $fieldInfos = null;
	/** @var int */
	protected $customerType = Crm\CustomerType::GENERAL;
	/** @var AddressValidator|null  */
	protected $addressValidator = null;
	/** @var MultifieldValidator|null */
	protected $multifieldValidator = null;
    /** @var LeadClientValidator|null  */
    protected $clientValidator = null;

	/** @var array */
	protected static $exclusiveFields = array(
		Crm\CustomerType::GENERAL => array(
			'HONORIFIC' => true,
			'LAST_NAME' => true,
			'NAME' => true,
			'SECOND_NAME' => true,
			'BIRTHDATE' => true,
			'POST' => true,
			'COMPANY_TITLE' => true,
			'ADDRESS' => true,
			'PHONE' => true,
			'EMAIL' => true,
			'WEB' => true,
			'IM' => true
		)
	);

	public function __construct($entityID, array $entityFields)
	{
		parent::__construct($entityID, $entityFields);
		$this->customerType = $this->entityID > 0
			? \CCrmLead::GetCustomerType($this->entityID) : Crm\CustomerType::GENERAL;

		$this->addressValidator = new AddressValidator(
			\CCrmOwnerType::Lead,
			$entityID,
			$entityFields
		);
		$this->multifieldValidator = new MultifieldValidator(
			\CCrmOwnerType::Lead,
			$entityID,
			$entityFields
		);
        $this->clientValidator = new LeadClientValidator($entityID, $entityFields);
    }

	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Lead;
	}

	public function getCustomerType()
	{
		return $this->customerType;
	}

	public function getFieldInfos()
	{
		if($this->fieldInfos === null)
		{
			$this->fieldInfos = \CCrmLead::GetFieldsInfo();
		}
		return $this->fieldInfos;
	}

	public function checkFieldAvailability($fieldName)
	{
		foreach(self::$exclusiveFields as $customerType => $fieldMap)
		{
			if($this->customerType === $customerType)
			{
				continue;
			}

			if(isset($fieldMap[$fieldName]))
			{
				return false;
			}
		}

		return true;
	}

	public function checkFieldPresence($fieldName, array &$messages)
	{
		//If field is not available ignore it.
		if(!$this->checkFieldAvailability($fieldName))
		{
			return true;
		}

		$message = null;
		if ($fieldName === 'TITLE')
		{
			$result = !$this->isNeedToCheck($fieldName)
				|| (is_string($this->entityFields[$fieldName])
					&& $this->entityFields[$fieldName] !== ''
					&& $this->entityFields[$fieldName] !== \CCrmLead::GetDefaultTitle());
		}
		elseif($fieldName === 'OPPORTUNITY_WITH_CURRENCY')
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
		elseif($fieldName === 'ADDRESS')
		{
			$result = $this->addressValidator->checkPresence();
		}
		elseif(\CCrmFieldMulti::IsSupportedType($fieldName))
		{
			$result = $this->multifieldValidator->checkPresence(array('TYPE_ID' => $fieldName));
		}
        elseif($fieldName === 'CLIENT')
        {
            $result = $this->clientValidator->checkPresence();
        }
		else if (Tracking\UI\Details::isTrackingField($fieldName))
		{
			$isNeedToCheck = $this->isNeedToCheck($fieldName);
			$isFilled = Tracking\UI\Details::isTrackingFieldFilled($this->entityFields);
			$result = !$isNeedToCheck || $isFilled;
			unset($isNeedToCheck, $isFilled);
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
					array('%FIELD_NAME%' => \CCrmLead::GetFieldCaption($fieldName))
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