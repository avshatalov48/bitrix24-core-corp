<?php
namespace Bitrix\Crm\Conversion;

use Bitrix\Crm\Tracking;


abstract class EntityConverter
{
	/** @var EntityConversionConfig */
	protected $config = null;
	/** @var int */
	protected $entityID = 0;
	/** @var int */
	protected $currentPhase = 0;
	/** @var array */
	protected $contextData = array();
	/** @var array */
	protected $resultData = array();
	/** @var \CCrmPerms|null  */
	protected $userPermissions = null;
	/** @var bool */
	private $enableUserFieldCheck = true;
	/** @var bool */
	private $enableBizProcCheck = true;

	/** @var bool */
	private $skipBizProcAutoStart = false;

	/**
	 * @param EntityConversionConfig $config
	 */
	public function __construct(EntityConversionConfig $config)
	{
		$this->config = $config;
	}
	/**
	 * Initialize converter.
	 * @return void
	 */
	public function initialize()
	{
	}
	//region Access to member fields
	/**
	 * Get converter entity type ID.
	 * @return int
	 */
	abstract public function getEntityTypeID();
	/**
	 * Get converter entity ID.
	 * @return int
	 */
	public function getEntityID()
	{
		return $this->entityID;
	}
	/**
	 * Set converter entity ID.
	 * @param int $entityID Entity ID.
	 * @return void
	 */
	public function setEntityID($entityID)
	{
		$this->entityID = $entityID;
	}
	/**
	 * Get current converter phase.
	 * @return int
	 */
	public function getCurrentPhase()
	{
		return $this->currentPhase;
	}
	/**
	 * Check if current phase is final.
	 * @return bool
	 */
	public function isFinished()
	{
		return false;
	}
	/**
	 * Get conversion configuration by entity type ID.
	 * @param int $entityTypeID Entity Type ID.
	 * @return EntityConversionConfigItem|null
	 */
	public function getEntityConfig($entityTypeID)
	{
		return $this->config->getItem($entityTypeID);
	}
	/**
	 * Get converter context data.
	 * @return array
	 */
	public function getContextData()
	{
		return $this->contextData;
	}
	/**
	 * Set converter context data.
	 * @param array $contextData Converter context data.
	 * @return void
	 */
	public function setContextData(array $contextData)
	{
		$this->contextData = $contextData;
	}
	/**
	 * Get converter result data.
	 * @return array
	 */
	public function getResultData()
	{
		return $this->resultData;
	}
	/**
	 * Get current user ID.
	 * @return int
	 */
	public function getUserID()
	{
		return \CCrmSecurityHelper::GetCurrentUserID();
	}
	/**
	 * Get current user permissions
	 * @return \CCrmPerms
	 */
	protected function getUserPermissions()
	{
		if($this->userPermissions === null)
		{
			$this->userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		}

		return $this->userPermissions;
	}
	/**
	 * Get converter configuration
	 * @return EntityConversionConfig
	 */
	public function getConfig()
	{
		return $this->config;
	}
	/**
	 * Check if User Field check is enabled.
	 * User Field checking is performed during a creation and update operation. It is enabled by default.
	 * @return bool
	 */
	public function isUserFieldCheckEnabled()
	{
		return $this->enableUserFieldCheck;
	}
	/**
	 * Enable/disable User Field check.
	 * User Field checking is performed during a creation and update operation.
	 * @param bool $enable Flag of enabling User Field checking.
	 */
	public function enableUserFieldCheck($enable)
	{
		$this->enableUserFieldCheck = $enable;
	}
	/**
	 * Check if checking for parametrized business process is enabled.
	 * Checking is performed before a creation operation. It is enabled by default.
	 * @return bool
	 */
	public function isBizProcCheckEnabled()
	{
		return $this->enableBizProcCheck;
	}
	/**
	 * Enable/disable checking for parametrized business process.
	 * Checking is performed before a creation operation.
	 * @param bool $enable Flag of enabling User Field checking.
	 */
	public function enableBizProcCheck($enable)
	{
		$this->enableBizProcCheck = $enable;
	}

	/**
	 * Check should auto start BP after update.
	 * @return bool
	 */
	public function shouldSkipBizProcAutoStart(): bool
	{
		return $this->skipBizProcAutoStart;
	}
	/**
	 * Enable/disable auto start BP after update
	 * @param bool $enable Flag of enabling User Field checking.
	 */
	public function setSkipBizProcAutoStart(bool $enable)
	{
		$this->skipBizProcAutoStart = $enable;
	}

	//endregion
	/**
	 * Try to execute current conversion phase.
	 * @return bool
	 */
	abstract public function executePhase();
	/**
	 * Map entity fields to specified type.
	 * @param int $entityTypeID Entity type ID.
	 * @param array|null $options Mapping options.
	 * @return array
	 */
	abstract public function mapEntityFields($entityTypeID, array $options = null);
	//region Externalization/Internalization
	/**
	 * Externalize converter settings
	 * @return array
	 */
	public function externalize()
	{
		$params = array(
			'config' => $this->config->externalize(),
			'entityId' => $this->entityID,
			'currentPhase' => $this->currentPhase,
			'resultData' => $this->resultData
		);
		$this->doExternalize($params);
		return $params;
	}

	protected function doExternalize(array &$params)
	{
	}

	/**
	 * Internalize converter settings.
	 * @param array $params Income parameters.
	 * @return void
	 */
	public function internalize(array $params)
	{
		if(isset($params['config']) && is_array($params['config']))
		{
			$this->config->internalize($params['config']);
		}

		if(isset($params['entityId']))
		{
			$this->entityID = (int)$params['entityId'];
		}

		if(isset($params['currentPhase']))
		{
			$this->currentPhase = (int)$params['currentPhase'];
		}

		if(isset($params['resultData']) && is_array($params['resultData']))
		{
			$this->resultData = $params['resultData'];
		}

		$this->doInternalize($params);
	}

	protected function doInternalize(array $params)
	{
	}
	//endregion
	//region Misc.
	/**
	 * Get Supported Destination Types
	 * @return array
	 */
	public function getSupportedDestinationTypeIDs()
	{
		return array();
	}
	/**
	 * Get deal category IDs are allowed to use in converter
	 * @param \CCrmPerms|null $permissions
	 * @return array
	 */
	public static function getPermittedDealCategoryIDs(\CCrmPerms $permissions = null)
	{
		return \CAllCrmDeal::GetPermittedToCreateCategoryIDs($permissions);
	}

	public static function getDestinationEntityID($entityTypeName, array $data)
	{
		return isset($data[$entityTypeName]) ? (int)$data[$entityTypeName] : 0;
	}

	public static function setDestinationEntityID($entityTypeName, $entityID, array &$data, array $options = null)
	{
		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$data[$entityTypeName] = $entityID;
		if(isset($options['isNew']) && $options['isNew'])
		{
			$data["IS_RECENT_{$entityTypeName}"] = true;
		}
	}

	public static function isNewDestinationEntity($entityTypeName, $entityID, array $data)
	{
		$isNewKeyName = "IS_RECENT_{$entityTypeName}";
		return ($entityID > 0
			&& isset($data[$entityTypeName])
			&& $data[$entityTypeName] == $entityID
			&& isset($data[$isNewKeyName]) && $data[$isNewKeyName]
		);
	}

	/**
	 * Delete specified entity.
	 * @param int $entityTypeID Entity Type ID.
	 * @param int $entityID Entity ID.
	 */
	protected function removeEntity($entityTypeID, $entityID)
	{
	}

	/**
	 * Remove recently created entities that were created by converter.
	 * @return void
	 */
	public function undo()
	{
		foreach($this->getSupportedDestinationTypeIDs() as $entityTypeID)
		{
			$this->removeRecentEntity($entityTypeID);
		}
		$this->currentPhase = 0;
	}

	protected function removeRecentEntity($entityTypeID)
	{
		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		$entityID = isset($this->resultData[$entityTypeName]) ? $this->resultData[$entityTypeName] : 0;
		if($entityID <= 0)
		{
			return;
		}

		$isNewKeyName = "IS_RECENT_{$entityTypeName}";
		if(!(isset($this->resultData[$isNewKeyName]) && $this->resultData[$isNewKeyName]))
		{
			return;
		}

		$this->removeEntity($entityTypeID, $entityID);
		unset($this->resultData[$entityTypeName], $this->resultData[$isNewKeyName]);
	}

	/**
	 * Finalization phase. Called for every entity.
	 * Uses for calling common conversion code for every converted entity.
	 *
	 * @return void
	 */
	protected function onFinalizationPhase()
	{
		foreach($this->getSupportedDestinationTypeIDs() as $entityTypeID)
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			$entityID = self::getDestinationEntityID($entityTypeName, $this->resultData);
			if($entityID <= 0)
			{
				continue;
			}

			Tracking\Entity::copyTrace(
				$this->getEntityTypeID(),
				$this->getEntityID(),
				$entityTypeID,
				$entityID
			);
		}
	}

	//endregion
	//region Permissions
	/**
	 * Check permission for CREATE operation.
	 * @param string $entityTypeName EntityTypeName.
	 * @param EntityConversionConfigItem $config Entity configuration.
	 * @return bool
	 */
	protected function checkCreatePermission($entityTypeName, EntityConversionConfigItem $config = null)
	{
		if(!$this->config->isPermissionCheckEnabled())
		{
			return true;
		}

		/** @var \CCrmPerms $permissions */
		$permissions = $this->getUserPermissions();

		if($entityTypeName === \CCrmOwnerType::CompanyName)
		{
			return \CCrmCompany::CheckCreatePermission($permissions);
		}
		elseif($entityTypeName === \CCrmOwnerType::ContactName)
		{
			return \CCrmContact::CheckCreatePermission($permissions);
		}
		elseif($entityTypeName === \CCrmOwnerType::DealName)
		{
			$initData = $config !== null ? $config->getInitData() : null;
			$categoryID = is_array($initData) && isset($initData['categoryId'])
				? max((int)$initData['categoryId'], 0) : 0;
			return \CCrmDeal::CheckCreatePermission($permissions, $categoryID);
		}
		elseif($entityTypeName === \CCrmOwnerType::InvoiceName)
		{
			return \CCrmInvoice::CheckCreatePermission($permissions);
		}
		elseif($entityTypeName === \CCrmOwnerType::QuoteName)
		{
			return \CCrmQuote::CheckCreatePermission($permissions);
		}

		return \CCrmAuthorizationHelper::CheckCreatePermission($entityTypeName, $permissions);
	}
	/**
	 * Check permission for READ operation.
	 * @param string $entityTypeName EntityTypeName.
	 * @param int $entityID Entity ID.
	 * @return bool
	 */
	protected function checkReadPermission($entityTypeName, $entityID)
	{
		if(!$this->config->isPermissionCheckEnabled())
		{
			return true;
		}

		/** @var \CCrmPerms $permissions */
		$permissions = $this->getUserPermissions();

		if($entityTypeName === \CCrmOwnerType::CompanyName)
		{
			return \CCrmCompany::CheckReadPermission($entityID, $permissions);
		}
		elseif($entityTypeName === \CCrmOwnerType::ContactName)
		{
			return \CCrmContact::CheckReadPermission($entityID, $permissions);
		}
		elseif($entityTypeName === \CCrmOwnerType::DealName)
		{
			return \CCrmDeal::CheckReadPermission($entityID, $permissions);
		}
		elseif($entityTypeName === \CCrmOwnerType::InvoiceName)
		{
			return \CCrmInvoice::CheckReadPermission($entityID, $permissions);
		}
		elseif($entityTypeName === \CCrmOwnerType::QuoteName)
		{
			return \CCrmQuote::CheckReadPermission($entityID, $permissions);
		}

		return \CCrmAuthorizationHelper::CheckReadPermission($entityTypeName, $entityID, $permissions);
	}
	/**
	 * Check permission for UPDATE operation.
	 * @param string $entityTypeName EntityTypeName.
	 * @param int $entityID Entity ID.
	 * @return bool
	 */
	protected function checkUpdatePermission($entityTypeName, $entityID)
	{
		if(!$this->config->isPermissionCheckEnabled())
		{
			return true;
		}

		/** @var \CCrmPerms $permissions */
		$permissions = $this->getUserPermissions();

		if($entityTypeName === \CCrmOwnerType::CompanyName)
		{
			return \CCrmCompany::CheckUpdatePermission($entityID, $permissions);
		}
		elseif($entityTypeName === \CCrmOwnerType::ContactName)
		{
			return \CCrmContact::CheckUpdatePermission($entityID, $permissions);
		}
		elseif($entityTypeName === \CCrmOwnerType::DealName)
		{
			return \CCrmDeal::CheckUpdatePermission($entityID, $permissions);
		}
		elseif($entityTypeName === \CCrmOwnerType::InvoiceName)
		{
			return \CCrmInvoice::CheckUpdatePermission($entityID, $permissions);
		}
		elseif($entityTypeName === \CCrmOwnerType::QuoteName)
		{
			return \CCrmQuote::CheckUpdatePermission($entityID, $permissions);
		}

		return \CCrmAuthorizationHelper::CheckUpdatePermission($entityTypeName, $entityID, $permissions);
	}
	//endregion
}