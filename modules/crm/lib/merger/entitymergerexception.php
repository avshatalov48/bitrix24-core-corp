<?php
namespace Bitrix\Crm\Merger;

use Bitrix\Main;
Main\Localization\Loc::loadMessages(__FILE__);

class EntityMergerException extends Main\SystemException
{
	const NONE                                      = 0;
	const GENERAL                                   = 10;
	const READ_DENIED                               = 20;
	const UPDATE_DENIED                             = 30;
	const DELETE_DENIED                             = 40;
	const NOT_FOUND                                 = 50;
	const UPDATE_FAILED                             = 60;
	const DELETE_FAILED                             = 70;
	const CONFLICT_RESOLUTION_NOT_SUPPORTED         = 300;
	const CONFLICT_OCCURRED                         = 310;
	//RESERVED BY DealMergerException: 600, 610
	//RESERVED BY LeadMergerException: 700

	protected $entityTypeID = \CCrmOwnerType::Undefined;
	protected $entityID = 0;
	protected $roleID = 0;
	protected $conflictResolutionMode = ConflictResolutionMode::UNDEFINED;

	public function __construct($entityTypeID = 0, $entityID = 0, $roleID = 0, $code = 0, $file = '', $line = 0, \Exception $previous = null, array $params = null)
	{
		$this->entityTypeID = $entityTypeID;
		$this->entityID = $entityID;
		$this->roleID = $roleID;

		if($params === null)
		{
			$params = array();
		}
		$this->conflictResolutionMode = isset($params['conflictResolutionMode'])
			? (int)$params['conflictResolutionMode'] : ConflictResolutionMode::UNDEFINED;

		$message = $this->getMessageByCode($code);
		if($previous)
		{
			$message .= ' Caused by: '.$previous->getMessage();
		}

		parent::__construct($message, $code, $file, $line, $previous);
	}
	protected function getMessageByCode($code)
	{
		if($code === self::CONFLICT_RESOLUTION_NOT_SUPPORTED)
		{
			$conflictResolutionModeName = ConflictResolutionMode::getName($this->conflictResolutionMode);
			$message = "Conflict resolution mode '{$conflictResolutionModeName}' is not supported in current context.";
		}
		elseif($code === self::CONFLICT_OCCURRED)
		{
			$message = "Conflict is occurred. Operation may cause the loss of data.";
		}
		else
		{
			$name = 'Entity';
			$entityID = $this->entityID;
			if($this->roleID === EntityMerger::ROLE_SEED)
			{
				$name = 'Seed entity';
			}
			elseif($this->roleID === EntityMerger::ROLE_TARG)
			{
				$name = 'Target entity';
			}

			if($code === self::READ_DENIED)
			{
				$message = "{$name} [{$entityID}] read permission denied.";
			}
			elseif($code === self::UPDATE_DENIED)
			{
				$message = "{$name} [{$entityID}] update permission denied.";
			}
			elseif($code === self::DELETE_DENIED)
			{
				$message = "{$name} [{$entityID}] delete permission denied.";
			}
			elseif($code === self::NOT_FOUND)
			{
				$message = "{$name} [{$entityID}] is not found.";
			}
			elseif($code === self::UPDATE_FAILED)
			{
				$message = "{$name} [{$entityID}] update operation failed.";
			}
			elseif($code === self::DELETE_FAILED)
			{
				$message = "{$name} [{$entityID}] delete operation failed.";
			}
			else
			{
				$message = 'General error.';
			}
		}
		return $message;
	}
	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}
	public function getEntityTypeName()
	{
		return \CCrmOwnerType::ResolveName($this->entityTypeID);
	}
	public function getEntityID()
	{
		return $this->entityID;
	}
	public function getRoleID()
	{
		return $this->roleID;
	}
	public function getLocalizedMessage()
	{
		switch($this->getCode())
		{
			case self::CONFLICT_RESOLUTION_NOT_SUPPORTED:
				return Main\Localization\Loc::getMessage(
					'CRM_ENTITY_MERGER_EXCEPTION_CONFLICT_RESOLUTION_NOT_SUPPORTED',
					array('#RESOLUTION_CAPTION#' => ConflictResolutionMode::getCaption($this->conflictResolutionMode))
				);
			case self::CONFLICT_OCCURRED:
				return Main\Localization\Loc::getMessage('CRM_ENTITY_MERGER_EXCEPTION_CONFLICT_OCCURRED');
			case self::READ_DENIED:
				return Main\Localization\Loc::getMessage(
					'CRM_ENTITY_MERGER_EXCEPTION_READ_DENIED',
					array(
						'#ID#' => $this->getEntityID(),
						'#TITLE#' => \CCrmOwnerType::GetCaption($this->getEntityTypeID(), $this->getEntityID())
					)
				);
			case self::UPDATE_DENIED:
				return Main\Localization\Loc::getMessage(
					'CRM_ENTITY_MERGER_EXCEPTION_UPDATE_DENIED',
					array(
						'#ID#' => $this->getEntityID(),
						'#TITLE#' => \CCrmOwnerType::GetCaption($this->getEntityTypeID(), $this->getEntityID())
					)
				);
			case self::DELETE_DENIED:
				return Main\Localization\Loc::getMessage(
					'CRM_ENTITY_MERGER_EXCEPTION_DELETE_DENIED',
					array(
						'#ID#' => $this->getEntityID(),
						'#TITLE#' => \CCrmOwnerType::GetCaption($this->getEntityTypeID(), $this->getEntityID())
					)
				);
			case self::NOT_FOUND:
				return Main\Localization\Loc::getMessage(
					'CRM_ENTITY_MERGER_EXCEPTION_NOT_FOUND',
					array('#ID#' => $this->getEntityID())
				);
			case self::UPDATE_FAILED:
				return Main\Localization\Loc::getMessage(
					'CRM_ENTITY_MERGER_EXCEPTION_UPDATE_FAILED',
					array(
						'#ID#' => $this->getEntityID(),
						'#TITLE#' => \CCrmOwnerType::GetCaption($this->getEntityTypeID(), $this->getEntityID())
					)
				);
			case self::DELETE_FAILED:
				return Main\Localization\Loc::getMessage(
					'CRM_ENTITY_MERGER_EXCEPTION_DELETE_FAILED',
					array(
						'#ID#' => $this->getEntityID(),
						'#TITLE#' => \CCrmOwnerType::GetCaption($this->getEntityTypeID(), $this->getEntityID())
					)
				);
		}

		return Main\Localization\Loc::getMessage(
			'CRM_ENTITY_MERGER_EXCEPTION_ERROR',
			array('#ERROR#' => $this->getMessage())
		);
	}
}