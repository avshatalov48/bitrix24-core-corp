<?php
namespace Bitrix\Crm\Requisite;
use Bitrix\Main;
class InvoiceRequisiteConvertException extends RequisiteConvertException
{
	//region ERROR CODES
	const NONE = 0;
	const GENERAL = 10;
	const PERSON_TYPE_NOT_FOUND = 20;
	const PROPERTY_NOT_FOUND = 30;
	const PRESET_NOT_BOUND = 40;
	//endregion

	/** @var int */
	protected $presetID = 0;
	/** @var int */
	protected $personTypeID = 0;

	public function __construct($presetID = 0, $personTypeID,  $code = 0, $file = '', $line = 0, \Exception $previous = null)
	{
		$this->presetID = $presetID;
		$this->personTypeID = $personTypeID;

		if($code === self::PRESET_NOT_BOUND)
		{
			$message = "There are no fields of preset to bind to invoice properties.";
		}
		elseif($code === self::PROPERTY_NOT_FOUND)
		{
			$message = "There are no required properties of invoice to process.";
		}
		else
		{
			$message = 'General error';
		}

		parent::__construct($message, $code, $file, $line, $previous);
	}
	/**
	 * Get preset ID.
	 * @return int
	 */
	public function getPresetID()
	{
		return $this->presetID;
	}
	/**
	 * Get person type ID.
	 * @return int
	 */
	public function getPersonTypeID()
	{
		return $this->personTypeID;
	}
	/**
	 * Get localized error message
	 * @return string
	 */
	public function getLocalizedMessage()
	{
		Main\Localization\Loc::loadMessages(__FILE__);

		$code = $this->getCode();
		if($code === self::GENERAL)
		{
			return GetMessage('CRM_INV_RQ_CONV_ERROR_GENERAL');
		}
		elseif($code === self::PERSON_TYPE_NOT_FOUND)
		{
			return GetMessage('CRM_INV_RQ_CONV_ERROR_PERSON_TYPE_NOT_FOUND');
		}
		elseif($code === self::PROPERTY_NOT_FOUND)
		{
			return GetMessage('CRM_INV_RQ_CONV_ERROR_PROPERTY_NOT_FOUND');
		}
		elseif($code === self::PRESET_NOT_BOUND)
		{
			return GetMessage('CRM_INV_RQ_CONV_ERROR_PRESET_NOT_BOUND');
		}

		return $this->getMessage();
	}
}