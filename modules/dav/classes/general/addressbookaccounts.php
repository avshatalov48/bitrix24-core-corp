<?php

use Bitrix\Main\Localization\Loc;

/**
 * Class CDavAccounts
 */
class CDavAccounts
	extends CDavAccountsBaseLimited
{
	const RESOURCE_SYNC_SETTINGS_NAME = 'ACCOUNTS';
	const IS_RESOURCE_SYNC_ENABLED = true;

	/**
	 * CDavAccounts constructor.
	 * @param CDavGroupDav $groupdav
	 */
	public function __construct($groupdav)
	{
		parent::__construct($groupdav);
		$this->SetName(Loc::getMessage('DAV_ALL_USERS_ACCOUNTS'));
		$this->SetNamespace(CDavGroupDav::CARDDAV);
		$this->SetUri('accounts');
		$this->SetMinimumPrivileges(array('DAV::read'));
	}

}