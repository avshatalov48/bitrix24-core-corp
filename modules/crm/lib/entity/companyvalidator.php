<?php
namespace Bitrix\Crm\Entity;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Tracking;

class CompanyValidator extends EntityValidator
{
	/** @var array|null */
	protected $fieldInfos = null;
	/** @var MultifieldValidator|null */
	protected $multifieldValidator = null;

	public function __construct($entityID, array $entityFields)
	{
		parent::__construct($entityID, $entityFields);

		$this->multifieldValidator = new MultifieldValidator(
			\CCrmOwnerType::Company,
			$entityID,
			$entityFields
		);
	}

	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Company;
	}

	public function getFieldInfos()
	{
		if($this->fieldInfos === null)
		{
			$this->fieldInfos = \CCrmCompany::GetFieldsInfo();
		}
		return $this->fieldInfos;
	}
	public function checkFieldPresence($fieldName, array &$messages = null)
	{
		$message = null;
		if ($fieldName === 'REVENUE_WITH_CURRENCY')
		{
			$result = !$this->isNeedToCheck('REVENUE')
				||  (isset($this->entityFields['REVENUE']) && $this->entityFields['REVENUE'] > 0);

			if(!$result)
			{
				$message = Loc::getMessage(
					'CRM_ENTITY_VALIDATOR_FIELD_MUST_BE_GREATER_THEN_ZERO',
					array('%FIELD_NAME%' => \CCrmCompany::GetFieldCaption('REVENUE'))
				);
			}
		}
		else if ($fieldName === 'LOGO')
		{
			$isNeedToCheck = $this->isNeedToCheck($fieldName);
			$isFilled = (isset($this->entityFields[$fieldName]) && $this->entityFields[$fieldName] > 0);
			$isDeleted = (isset($this->entityFields[$fieldName.'_del'])
				&& $this->entityFields[$fieldName.'_del'] === $this->entityFields[$fieldName]);
			$result = !$isNeedToCheck || ($isFilled && !$isDeleted);
			unset($isNeedToCheck, $isFilled, $isDeleted);
		}
		else if (\CCrmFieldMulti::IsSupportedType($fieldName))
		{
			$result = $this->multifieldValidator->checkPresence(array('TYPE_ID' => $fieldName));
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
			if($fieldName === 'CONTACT')
			{
				$effectiveFieldName = 'CONTACT_ID';
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
					array('%FIELD_NAME%' => \CCrmCompany::GetFieldCaption($fieldName))
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