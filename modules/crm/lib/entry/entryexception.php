<?php
namespace Bitrix\Crm\Entry;
use Bitrix\Main;
class EntryException extends Main\SystemException
{
	const GENERAL = 0;
	const NOT_FOUND = 1;
	const DEPENDENCIES_FOUND = 2;

	/** @var int  */
	protected $entityTypeID = \CCrmOwnerType::Undefined;
	/** @var int  */
	protected $entityID = 0;

	public function __construct($entityTypeID, $entityID, array $errorMessages, $code = 0, $file = '', $line = 0, \Exception $previous = null)
	{
		$this->entityTypeID = $entityTypeID;
		$this->entityID = $entityID;
		$message = implode("\r\n", $errorMessages);
		if($message === '')
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($this->entityTypeID);
			if($code === self::NOT_FOUND)
			{
				$message = "The {$entityTypeName} entity is not found.";
			}
			elseif($code === self::DEPENDENCIES_FOUND)
			{
				$message = "The {$entityTypeName} entity has external dependencies.";
			}
		}

		parent::__construct($message, $code, $file, $line, $previous);
	}

	/**
	 * Get Localized error message
	 * @return string
	 */
	public function getLocalizedMessage()
	{
		Main\Localization\Loc::loadMessages(__FILE__);

		$code = $this->getCode();
		$entityTypeName = \CCrmOwnerType::ResolveName($this->entityTypeID);

		if($code === self::NOT_FOUND)
		{
			return GetMessage("CRM_ENTRY_EX_{$entityTypeName}_NOT_FOUND");
		}
		if($code === self::DEPENDENCIES_FOUND)
		{
			return GetMessage(
				"CRM_ENTRY_EX_{$entityTypeName}_DEPENDENCIES_FOUND",
				array('#CAPTION#' => \CCrmOwnerType::GetCaption($this->entityTypeID, $this->entityID, false))
			);
		}

		return $this->getMessage();
	}
}