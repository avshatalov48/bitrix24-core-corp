<?php
namespace Bitrix\Crm\Entity;

use Bitrix\Crm\EntityBankDetail;
use Bitrix\Main\Localization\Loc;

class BankDetailValidator extends EntityValidator
{
	/** @var array|null */
	protected $fieldInfos = null;

	protected int $countryId = 0;

	public function __construct($entityID, array $entityFields, int $countryId)
	{
		parent::__construct($entityID, $entityFields);

		if ($countryId > 0)
		{
			$this->countryId = $countryId;
		}
	}

	public function getEntityTypeID()
	{
		return \CCrmOwnerType::BankDetail;
	}

	public function getFieldInfos()
	{
		if($this->fieldInfos === null)
		{
			$this->fieldInfos = EntityBankDetail::getFieldsInfo();
		}
		return $this->fieldInfos;
	}

	public function checkFieldPresence($fieldName, array &$messages = null)
	{
		$message = null;

		$result = $this->innerCheckFieldPresence($fieldName);

		if(!$result)
		{
			if($message === null)
			{
				$bankDetail = EntityBankDetail::getSingleInstance();
				$fieldTitles = $bankDetail->getFieldsTitles($this->countryId);
				$fieldTitle =  $fieldTitles[$fieldName] ?? '';
				$message = Loc::getMessage(
					'CRM_ENTITY_VALIDATOR_FIELD_IS_MISSING',
					array('%FIELD_NAME%' => $fieldTitle)
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