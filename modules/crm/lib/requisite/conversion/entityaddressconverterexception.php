<?php

namespace Bitrix\Crm\Requisite\Conversion;

use Bitrix\Crm\Requisite\AddressRequisiteConvertException;
use Bitrix\Main\Localization\Loc;

class EntityAddressConverterException extends AddressRequisiteConvertException
{
	//region ERROR CODES
	const ERR_CANT_PICK_PRESET_FOR_REQUISITE = 1010;
	//endregion

	protected function getMessageByCode($code)
	{
		if ($code === self::ERR_CANT_PICK_PRESET_FOR_REQUISITE)
		{
			$message = "Could not pick up a preset for creating an object";
		}
		else
		{
			$message = parent::getMessageByCode($code);
		}

		return $message;
	}

	/**
	 * Get localized error message
	 *
	 * @return mixed|string|string[]
	 */
	public function getLocalizedMessage()
	{
		Loc::loadMessages(__FILE__);

		$code = $this->getCode();
		$entityTypeID =  $this->getEntityTypeID();
		$entityTypeName =  \CCrmOwnerType::ResolveName($entityTypeID);

		if($code === self::ERR_CANT_PICK_PRESET_FOR_REQUISITE)
		{
			return GetMessage("CRM_ADDR_CONV_EX_{$entityTypeName}_ERR_CANT_PICK_PRESET_FOR_REQUISITE");
		}

		return parent::getLocalizedMessage();
	}
}
