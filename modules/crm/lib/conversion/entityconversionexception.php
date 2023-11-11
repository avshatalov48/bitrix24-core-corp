<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;

class EntityConversionException extends Main\SystemException
{
	public const TARG_UNDEFINED = 0;
	public const TARG_SRC = 1;
	public const TARG_DST = 2;

	public const GENERAL = 10;
	public const NOT_FOUND = 20;
	public const NOT_SYNCHRONIZED = 30;
	public const INVALID_OPERATION = 40;
	public const AUTOCREATION_DISABLED = 50;
	public const HAS_WORKFLOWS = 60;
	public const EMPTY_FIELDS = 70;
	public const INVALID_FIELDS = 80;
	public const READ_DENIED = 90;
	public const CREATE_DENIED = 100;
	public const CREATE_FAILED = 110;
	public const UPDATE_DENIED = 120;
	public const NOT_SUPPORTED = 130;
	public const NO_ACTIVE_DESTINATIONS = 140;

	protected $srcEntityTypeID = \CCrmOwnerType::Undefined;
	protected $dstEntityTypeID = \CCrmOwnerType::Undefined;
	protected $targ = 0;
	protected $extMessage = '';

	public function __construct(
		$srcEntityTypeID = 0,
		$dstEntityTypeID = 0,
		$targ = 0,
		$code = 0,
		$extMessage = '',
		$file = '',
		$line = 0,
		\Exception $previous = null
	)
	{
		$this->srcEntityTypeID = $srcEntityTypeID;
		$this->dstEntityTypeID = $dstEntityTypeID;
		$this->targ = $targ;

		if(is_string($extMessage) && $extMessage !== '')
		{
			$this->extMessage = $extMessage;
		}

		$entityTypeName = ucfirst(\CCrmOwnerType::ResolveName($this->getTargetEntityTypeID()));
		if($code === self::NOT_FOUND)
		{
			$message = "The {$entityTypeName} entity is not found.";
		}
		elseif($code === self::NOT_SYNCHRONIZED)
		{
			$message = "The {$entityTypeName} entity user fields are not synchronized.";
		}
		elseif($code === self::INVALID_OPERATION)
		{
			$message = "Invalid operation.";
		}
		elseif($code === self::HAS_WORKFLOWS)
		{
			$message = "The {$entityTypeName} entity has autostarting workflows.";
		}
		elseif($code === self::AUTOCREATION_DISABLED)
		{
			$message = "Entity autocreation is disabled.";
		}
		elseif($code === self::EMPTY_FIELDS)
		{
			$message = "The {$entityTypeName} entity fields are empty.";
		}
		elseif($code === self::INVALID_FIELDS)
		{
			$message = "The {$entityTypeName} entity fields are invalid.";
		}
		elseif($code === self::READ_DENIED)
		{
			$message = "The {$entityTypeName} entity read is not permitted.";
		}
		elseif($code === self::CREATE_DENIED)
		{
			$message = "The {$entityTypeName} entity creation is not permitted.";
		}
		elseif($code === self::CREATE_FAILED)
		{
			$message = "Could not create a {$entityTypeName} entity.";
		}
		elseif($code === self::UPDATE_DENIED)
		{
			$message = "The {$entityTypeName} entity modification is not permitted.";
		}
		elseif($code === self::NOT_SUPPORTED)
		{
			$message = "The {$entityTypeName} entity type is not supported in current context.";
		}
		elseif ($code === self::NO_ACTIVE_DESTINATIONS)
		{
			$message = "No destinations selected for an entity of type {$entityTypeName}";
		}
		else
		{
			$message = 'A general error has occurred';
		}

		parent::__construct($message, $code, $file, $line, $previous);
	}
	public function getSourceEntityTypeID()
	{
		return $this->srcEntityTypeID;
	}
	public function getDestinationEntityTypeID()
	{
		return $this->dstEntityTypeID;
	}
	public function getTargetType()
	{
		return $this->targ;
	}
	public function getTargetEntityTypeID()
	{
		return $this->targ === self::TARG_SRC ? $this->srcEntityTypeID : $this->dstEntityTypeID;
	}
	public function getExtendedMessage()
	{
		return $this->extMessage;
	}
	public function getLocalizedMessage()
	{
		Main\Localization\Loc::loadMessages(__FILE__);

		$code = $this->getCode();
		$entityTypeID =  $this->getTargetEntityTypeID();
		$entityTypeName =  \CCrmOwnerType::ResolveName($entityTypeID);

		$message = null;

		if ($code === EntityConversionException::NOT_FOUND)
		{
			$message = GetMessage("CRM_CONV_EX_{$entityTypeName}_NOT_FOUND");
		}
		elseif ($code === EntityConversionException::NOT_SYNCHRONIZED)
		{
			$message = GetMessage("CRM_CONV_EX_NOT_SYNCHRONIZED");
		}
		elseif ($code === EntityConversionException::EMPTY_FIELDS)
		{
			$message = GetMessage("CRM_CONV_EX_{$entityTypeName}_EMPTY_FIELDS");
		}
		elseif ($code === EntityConversionException::INVALID_OPERATION)
		{
			$message = $this->extMessage !== '' ? $this->extMessage : GetMessage("CRM_CONV_EX_INVALID_OPERATION");
		}
		elseif ($code === EntityConversionException::HAS_WORKFLOWS)
		{
			$message = GetMessage("CRM_CONV_EX_{$entityTypeName}_HAS_WORKFLOWS");
		}
		elseif ($code === EntityConversionException::AUTOCREATION_DISABLED)
		{
			$message = GetMessage("CRM_CONV_EX_{$entityTypeName}_AUTOCREATION_DISABLED");
			if (!$message)
			{
				$message = GetMessage("CRM_CONV_EX_{$entityTypeName}_AUTOCREATION_DISABLED_MSGVER_1");
			}
		}
		elseif ($code === EntityConversionException::INVALID_FIELDS)
		{
			$message = GetMessage("CRM_CONV_EX_{$entityTypeName}_INVALID_FIELDS").preg_replace('/<br\s*\/?>/i', "\r\n", $this->extMessage);
		}
		elseif ($code === EntityConversionException::CREATE_DENIED)
		{
			$message = GetMessage("CRM_CONV_EX_{$entityTypeName}_CREATE_DENIED");
		}
		elseif ($code === EntityConversionException::CREATE_FAILED)
		{
			$message = GetMessage("CRM_CONV_EX_{$entityTypeName}_CREATE_FAILED").preg_replace('/<br\s*\/?>/i', "\r\n", $this->extMessage);
		}
		elseif ($code === EntityConversionException::READ_DENIED)
		{
			$message = GetMessage("CRM_CONV_EX_{$entityTypeName}_READ_DENIED");
		}
		elseif ($code === EntityConversionException::UPDATE_DENIED)
		{
			$message = GetMessage("CRM_CONV_EX_{$entityTypeName}_UPDATE_DENIED");
		}
		elseif ($code === EntityConversionException::NOT_SUPPORTED)
		{
			$message = GetMessage(
				'CRM_CONV_EX_ENTITY_NOT_SUPPORTED',
				array('#ENTITY_TYPE_NAME#' => \CCrmOwnerType::GetDescription($entityTypeID))
			);
		}

		return $message ?: $this->getMessage();
	}

	public function externalize()
	{
		return array(
			'srcEntityTypeID' => $this->srcEntityTypeID,
			'dstEntityTypeID' => $this->dstEntityTypeID,
			'targ' => $this->targ,
			'code' => $this->code
		);
	}
	public function internalize(array $params)
	{
		if(isset($params['srcEntityTypeID']))
		{
			$this->srcEntityTypeID = (int)$params['srcEntityTypeID'];
		}

		if(isset($params['dstEntityTypeID']))
		{
			$this->dstEntityTypeID = (int)$params['dstEntityTypeID'];
		}

		if(isset($params['targ']))
		{
			$this->targ = (int)$params['targ'];
		}

		if(isset($params['code']))
		{
			$this->code = (int)$params['code'];
		}
	}
}
