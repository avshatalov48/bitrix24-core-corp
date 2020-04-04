<?php

/**
 * Class CDavAddressbookAccountsBaseLimited
 */
abstract class CDavAddressbookBaseLimited
	extends CDavAddressbookBase
{
	const RESOURCE_SYNC_SETTINGS_NAME = '';
	const MAX_SYNC_COUNT = 200;
	const IS_RESOURCE_SYNC_ENABLED = false;
	const SYNC_SETTINGS_SAVE_TIMESTAMP = 0;

	protected function AdditionalPrivilegesCheck($principal)
	{
		return parent::AdditionalPrivilegesCheck($principal)
			&& static::IsResourceSyncEnabled($principal);
	}

	/**
	 * Return entities array
	 * @param $collectionId
	 * @param $account
	 * @param array $filter
	 * @return mixed
	 */
	protected function LoadEntities($collectionId, $account, $filter = array())
	{
		if ($account instanceof CDavPrincipal)
			$userId = $account->Id();
		else
			$userId = $account[1];
		$maxCount = static::GetResourceSyncMaxCount($userId);

		return $this->LoadLimitedEntitiesList($collectionId, $account, $filter, $maxCount);
	}


	/**
	 * @param $collectionId
	 * @param $account
	 * @param array $filter
	 * @param $maxCount
	 * @return mixed
	 */
	abstract protected function LoadLimitedEntitiesList($collectionId, $account, $filter = array(), $maxCount);


	/**
	 * If user save settings, update cTag to that time
	 * @param $collectionId
	 * @param array $filter
	 * @return string getctag property
	 */
	public function GetCTag($collectionId, $filter = array())
	{
		$principalId = $this->groupdav->GetRequest()->GetPrincipal()->Id();
		$lastModifiedAt = MakeTimeStamp($this->CatalogLastModifiedAt($collectionId, $filter));
		$lastSyncSettingsSaveAt = $this->GetLastSyncSettingSaveTimestamp($principalId);
		if ($lastModifiedAt >= $lastSyncSettingsSaveAt)
			return parent::GetCTag($collectionId, $filter);
		else
			return 'BX:' . $lastSyncSettingsSaveAt;
	}

	/**
	 * Check is user turn on CardDAV synchronisation for
	 * entities(accounts, contacts or company etc.),
	 *
	 * @param $userId
	 * @return bool|null
	 */
	public static function IsResourceSyncEnabled($userId)
	{
		if (!$userId)
		{
			return null;
		}
		$setting = static::LoadSavedSyncSettings($userId);
		return ($setting['ENABLED'] === 'Y' || $setting['ENABLED'] === true);
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
		$defaultSettings = array(
			'ENABLED' => static::IS_RESOURCE_SYNC_ENABLED,
			'MAX_COUNT' => static::MAX_SYNC_COUNT,
			//'FILTER' => static::RESOURCE_SYNC_OWNER,
		);
		return CUserOptions::GetOption('DAV_SYNC', static::RESOURCE_SYNC_SETTINGS_NAME, $defaultSettings, $userId);
	}

	/**
	 * Save user SYNC settings from array like
	 * [
	 *      'MAX_COUNT' => maximum count of synchronize sub entities
	 *      'ENABLED' => boolean value of is enabled or no synchronization of sub entities
	 * ]
	 * @param $settings array
	 * @param $userId
	 */
	public static function SetResourceSyncSetting($settings, $userId)
	{
		$params = static::PrepareForSaveSyncSettings($settings);
		CUserOptions::SetOption('DAV_SYNC', static::RESOURCE_SYNC_SETTINGS_NAME, $params, false, $userId);
	}

	/**
	 * Max count of sub entities witch will synchronized
	 * with CardDAV protocol
	 *
	 * @param $userId
	 * @return int
	 */
	public static function GetResourceSyncMaxCount($userId)
	{
		if (!$userId)
		{
			return null;
		}
		$setting = static::LoadSavedSyncSettings($userId);
		return $setting['MAX_COUNT'];
	}

	/**
	 * @param $userId
	 * @return mixed|null
	 */
	private function GetLastSyncSettingSaveTimestamp($userId)
	{
		if (!$userId)
		{
			return null;
		}
		$setting = static::LoadSavedSyncSettings($userId);
		return $setting['SAVE_TIMESTAMP'];
	}

	/**
	 * @param $settings
	 * @return array
	 */
	protected static function PrepareForSaveSyncSettings($settings)
	{
		$params['MAX_COUNT'] = isset($settings['MAX_COUNT']) ? intval($settings['MAX_COUNT']) : static::MAX_SYNC_COUNT;
		$params['ENABLED'] = isset($settings['ENABLED']) ? htmlspecialcharsbx($settings['ENABLED']) : static::IS_RESOURCE_SYNC_ENABLED;
		$params['SAVE_TIMESTAMP'] = time() + CTimeZone::GetOffset();
		return $params;
	}

	/**
	 * Catalog getetag property for DAV protocol
	 * Add collection identifier to eTag for resolving collision in mac when contacts are with similar id and created at one time
	 *
	 * @param  array $collectionId
	 * @param $entity
	 * @return string getetag property
	 */
	public function GetETag($collectionId, $entity)
	{
		$eTag =  static::RESOURCE_SYNC_SETTINGS_NAME . ':' . parent::GetETag($collectionId, $entity);
		return $eTag;
	}

}