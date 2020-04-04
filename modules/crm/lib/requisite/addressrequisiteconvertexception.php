<?php
namespace Bitrix\Crm\Requisite;
use Bitrix\Main;
class AddressRequisiteConvertException extends RequisiteConvertException
{
	//region ERROR CODES
	const NONE = 0;
	const GENERAL = 10;
	const ACCESS_DENIED = 20;
	const CREATION_FAILED = 30;
	//endregion

	/** @var int */
	protected $entityTypeID = 0;
	/** @var int */
	protected $presetID = 0;

	/**
	 * @param string $entityTypeID Entity type ID.
	 * @param int $presetID Preset ID.
	 * @param int $code Error code.
	 * @param string $file File path.
	 * @param int $line line number.
	 * @param \Exception|null $previous Previous error.
	 */
	public function __construct($entityTypeID, $presetID = 0, $code = 0, $file = '', $line = 0, \Exception $previous = null)
	{
		$this->entityTypeID = $entityTypeID;
		$this->presetID = $presetID;

		if($code === self::ACCESS_DENIED)
		{
			$message = "Access denied.";
		}
		elseif($code === self::CREATION_FAILED)
		{
			$message = "Failed to create requisite.";
		}
		else
		{
			$message = 'General error';
		}

		parent::__construct($message, $code, $file, $line, $previous);
	}
	/**
	 * Get entity type ID
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}
	/**
	 * @return int
	 */
	public function getPresetID()
	{
		return $this->presetID;
	}
	/**
	 * Get localized error message
	 * @return string
	 */
	public function getLocalizedMessage()
	{
		Main\Localization\Loc::loadMessages(__FILE__);

		$code = $this->getCode();
		$entityTypeID =  $this->getEntityTypeID();
		$entityTypeName =  \CCrmOwnerType::ResolveName($entityTypeID);

		if($code === self::ACCESS_DENIED)
		{
			return GetMessage("CRM_ADDR_CONV_EX_{$entityTypeName}_ACCESS_DENIED");
		}
		elseif($code === self::CREATION_FAILED)
		{
			return GetMessage("CRM_ADDR_CONV_EX_{$entityTypeName}_CREATION_FAILED");
		}
		return $this->getMessage();
	}
}