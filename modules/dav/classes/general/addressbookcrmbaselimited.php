<?php
use Bitrix\Main\Localization\Loc;

/**
 * Class CDavAddressbookCrmLimitedResource
 */
abstract class CDavAddressbookCrmBaseLimited
	extends CDavAddressbookCrmBase
{
	const RESOURCE_SYNC_OWNER = 'responsible';
	protected static $resourceSyncOrder = array('DATE_MODIFY' => 'DESC');
	protected static $resourceSyncSelectParams = array();

	/**
	 * Load CRM sub entities(contacts or company etc.),
	 * apply user settings from CRM settings, if user have not
	 * settings then apply default settings
	 *
	 * @param $collectionId
	 * @param $account
	 * @param array $filter
	 * @param $maxCount
	 * @return CDBResult|null
	 */
	protected function LoadCrmResourceEntitiesList($collectionId, $account, $filter = array(), $maxCount)
	{
		if ($account instanceof CDavPrincipal)
			$userId = $account->Id();
		else
			$userId = $account[1];

		$filter = static::GetResourceSyncFilter($userId, $filter);
		$order = static::GetResourceSyncOrder($userId);
		$selectParams = static::GetResourceSyncSelectParams($userId);
		return $this->LoadCrmResourceEntitiesListByParams($order, $filter, $selectParams, $maxCount);
	}

	/**
	 * Load user CardDAV settings from CUserOptions
	 * if user has not save settings return default settings
	 *
	 * @param $userId
	 * @return array
	 */
	protected static function LoadSavedSyncSettings($userId)
	{
		$defaultSettings = parent::LoadSavedSyncSettings($userId);
		$defaultSettings['FILTER'] = static::RESOURCE_SYNC_OWNER;
		return CUserOptions::GetOption('DAV_SYNC', static::RESOURCE_SYNC_SETTINGS_NAME, $defaultSettings, $userId);
	}


	/**
	 * Order params for selecting sub entities in synchronization
	 * with CardDAV
	 *
	 * @param $userId
	 * @return array
	 */
	protected static function GetResourceSyncOrder($userId)
	{
		if (!$userId)
		{
			return null;
		}
		return static::$resourceSyncOrder;
	}


	/**
	 * @param $userId
	 * @param array $filter
	 * @return array
	 */
	private static function GetResourceSyncFilter($userId, $filter = array())
	{
		$owner = static::GetResourceSyncFilterOwner($userId);
		$filter = static::PerformResourceSyncFilter($owner, $userId, $filter);
		return $filter;
	}

	/**
	 * Format array for selecting list of
	 * sub entities(CAllCrmContacts, CAllCrmCompanies)
	 *
	 * @param $owner
	 * @param $userId
	 * @param array $filter
	 * @return array
	 * @throws CDavArgumentTypeException
	 */
	protected static function PerformResourceSyncFilter($owner, $userId, $filter = array())
	{
		switch ($owner)
		{
			case 'all':
				break;
			case 'responsible':
				$filter['ASSIGNED_BY_ID'] = $userId;
				break;
			default:
				throw new CDavArgumentTypeException('owner');
		}

		return $filter;
	}


	/**
	 * Return whose entities will synchronized, from user CRM settings.
	 * If user has not saved settings, will return default
	 *
	 * @param $userId
	 * @return string (all|responsible|etc.)
	 */
	public static function GetResourceSyncFilterOwner($userId)
	{
		if (!$userId)
		{
			return null;
		}
		$setting = static::LoadSavedSyncSettings($userId);
		return $setting['FILTER'];
	}


	/**
	 * @param $userId
	 * @return string
	 */
	protected static function GetResourceSyncSelectParams($userId)
	{
		if (!$userId)
		{
			return null;
		}

		return static::$resourceSyncSelectParams;
	}


	/**
	 * @return array of available DAV filter params
	 */
	public static function GetListOfFilterItems()
	{
		return array(
			'all' => Loc::getMessage('DAV_EXPORT_FILTER_ALL'),
			'responsible' => Loc::getMessage('DAV_EXPORT_FILTER_RESPONSIBLE')
		);
	}

	/**
	 * @param $settings
	 * @return array
	 */
	protected static function PrepareForSaveSyncSettings($settings)
	{
		$params = parent::PrepareForSaveSyncSettings($settings);
		if (isset($settings['FILTER']) && in_array($settings['FILTER'], array_keys(static::GetListOfFilterItems())))
			$params['FILTER'] = htmlspecialcharsbx($settings['FILTER']);
		else
			$params['FILTER'] = static::RESOURCE_SYNC_OWNER;
		return $params;
	}
}