<?php

/**
 * Class CDavAccountsBaseLimited
 */
abstract class CDavAccountsBaseLimited
	extends CDavAddressbookAccountsBase
{
	const RESOURCE_SYNC_OWNER = 'all';
	protected static $resourceSyncOrder = array('DATE_MODIFY' => 'DESC');
	protected static $defaultDepartments = array();
	protected static $resourceSyncSelectParams = array();

	/**
	 * Return accounts array
	 * @param $collectionId
	 * @param $account
	 * @param array $filter
	 * @param $maxCount
	 * @return mixed
	 */
	protected function LoadLimitedEntitiesList($collectionId, $account, $filter = array(), $maxCount)
	{
		if ($account instanceof CDavPrincipal)
			$userId = $account->Id();
		else
			$userId = $account[1];
		$filter['UF_DEPARTMENT'] = self::GetResourceSyncUfDepartments($userId);
		if (empty($filter['UF_DEPARTMENT']))
			unset($filter['UF_DEPARTMENT']);

		return CDavAccount::GetAddressbookContactsList($collectionId, $filter);
	}

	/**
	 * @param $collectionId
	 * @param array $filter
	 * @return int unix timestamp
	 */
	protected function CatalogLastModifiedAt($collectionId, $filter = array())
	{
		return CDavAccount::getAddressbookModificationLabel($collectionId);
	}

	/**
	 * @param $userId
	 * @return null
	 */
	public static function GetResourceSyncUfDepartments($userId)
	{
		if (!$userId)
		{
			return null;
		}
		$setting = static::LoadSavedSyncSettings($userId);
		return $setting['UF_DEPARTMENT'];
	}

	/**
	 * @param $settings
	 * @return array
	 */
	protected static function PrepareForSaveSyncSettings($settings)
	{
		$params = parent::PrepareForSaveSyncSettings($settings);
		if (isset($settings['UF_DEPARTMENT']) && is_array($settings['UF_DEPARTMENT']))
			$params['UF_DEPARTMENT'] = array_filter($settings['UF_DEPARTMENT']);
		else
			$params['UF_DEPARTMENT'] = static::$defaultDepartments;
		return $params;
	}

	/**
	 * @param $userId
	 * @return mixed
	 */
	protected static function LoadSavedSyncSettings($userId)
	{
		$defaultSettings = parent::LoadSavedSyncSettings($userId);
		$defaultSettings['UF_DEPARTMENT'] = static::$defaultDepartments;
		return CUserOptions::GetOption('DAV_SYNC', static::RESOURCE_SYNC_SETTINGS_NAME, $defaultSettings, $userId);
	}
}