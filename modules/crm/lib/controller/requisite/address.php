<?php

namespace Bitrix\Crm\Controller\Requisite;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\EntityAddress;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Error;

Loc::loadMessages(__FILE__);

class Address extends Base
{
	public function getLocationAddressJsonByFieldsAction(array $addresses)
	{
		$result = [];

		$actionCode = 'LOC_ADDR_JSON_BY_FIELDS';
		$error = '';

		$fieldsInfo = [
			'ADDRESS_1' => 1024,
			'ADDRESS_2' => 1024,
			'CITY' => 128,
			'POSTAL_CODE' => 16,
			'PROVINCE' => 128,
			'REGION' => 128,
			'COUNTRY' => 128,
		];

		$verifiedFields = [];

		foreach ($addresses as $addressTypeId => $addressFields)
		{
			foreach ($fieldsInfo as $fieldName => $fieldLength)
			{
				if (isset($addressFields[$fieldName]) && is_string($addressFields[$fieldName]))
				{
					$verifiedFields[$fieldName] =
						mb_substr($addressFields[$fieldName], 0, $fieldLength)
					;
				}
				else
				{
					$verifiedFields[$fieldName] = '';
				}
			}
			$locationAddress = EntityAddress::getLocationAddressByFields(
				$verifiedFields,
				EntityAddress::getDefaultLanguageId()
			);
			if ($locationAddress)
			{
				$result[$addressTypeId] = $locationAddress->toJson();
			}
			else
			{
				$error = 'ERR_GET_LOC_ADDR';
				$result = [];
				break;
			}
		}

		if ($error)
		{
			if (!is_array($error))
			{
				$error = [$error];
			}
			foreach ($error as $errorCode)
			{
				$this->addError(
					new Error(
						Loc::getMessage('CRM_CONTROLLER_REQUISITE_ADDRESS_'.$actionCode.'_'.$errorCode),
						$errorCode
					)
				);
			}
		}

		return $result;
	}
}
