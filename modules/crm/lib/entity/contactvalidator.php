<?php
namespace Bitrix\Crm\Entity;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Tracking;

class ContactValidator extends EntityValidator
{
	/** @var array|null */
	protected $fieldInfos = null;
	/** @var MultifieldValidator|null */
	protected $multifieldValidator = null;

	public function __construct($entityID, array $entityFields)
	{
		parent::__construct($entityID, $entityFields);

		$this->multifieldValidator = new MultifieldValidator(
			\CCrmOwnerType::Contact,
			$entityID,
			$entityFields
		);
	}

	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Contact;
	}

	public function getFieldInfos()
	{
		if($this->fieldInfos === null)
		{
			$this->fieldInfos = \CCrmContact::GetFieldsInfo();
		}
		return $this->fieldInfos;
	}
	public function checkFieldPresence($fieldName, array &$messages = null)
	{
		$message = null;
		if ($fieldName === 'PHOTO')
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
			if($fieldName === 'COMPANY')
			{
				$result = $this->innerCheckAnyFieldPresence(['COMPANY_IDS', 'COMPANY_ID']);
			}
			else
			{
				$result = $this->innerCheckFieldPresence($fieldName);
			}
		}

		if(!$result)
		{
			if($message === null)
			{
				$message = Loc::getMessage(
					'CRM_ENTITY_VALIDATOR_FIELD_IS_MISSING',
					array('%FIELD_NAME%' => \CCrmContact::GetFieldCaption($fieldName))
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