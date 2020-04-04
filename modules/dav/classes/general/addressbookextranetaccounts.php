<?php

use Bitrix\Main\Localization\Loc;

/**
 * Class CDavExtranetAccounts
 */
class CDavExtranetAccounts
	extends CDavAddressbookAccountsBase
{
	const RESOURCE_SYNC_SETTINGS_NAME = 'EXTRANET_ACCOUNTS';
	const IS_RESOURCE_SYNC_ENABLED = false;

	/**
	 * CDavAccounts constructor.
	 * @param CDavGroupDav $groupdav
	 */
	public function __construct($groupdav)
	{
		parent::__construct($groupdav);
		$this->SetName(Loc::getMessage('DAV_EXTRANET_ACCOUNTS'));
		$this->SetNamespace(CDavGroupDav::CARDDAV);
		$this->SetUri('extranetAccounts');
		$this->SetMinimumPrivileges(array('DAV::read'));
	}


	/**
	 * @param $collectionId
	 * @param array $filter
	 * @return int unix timestamp
	 */
	protected function CatalogLastModifiedAt($collectionId, $filter = array())
	{
		$order = array('TIMESTAMP_X' => 'DESC');
		$accounts = $this->LoadExtraAccounts($collectionId[0], $order, $filter, 1);
		if (count($accounts))
		{
			$lastModifiedExtranetAccount = reset($accounts);
			return $lastModifiedExtranetAccount['TIMESTAMP_X'];
		}
		else
		{
			return 0;
		}
	}

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
		$order = array('TIMESTAMP_X' => 'DESC');
		return $this->LoadExtraAccounts($collectionId[0], $order, $filter, $maxCount);
	}


	/**
	 * @param $siteId
	 * @param array $order
	 * @param array $filter
	 * @param $maxCount
	 * @return array
	 */
	private function LoadExtraAccounts($siteId, $order = array(), $filter = array(), $maxCount)
	{
		$extraUserIds = CExtranet::GetMyGroupsUsersSimple($siteId);
		$extraUserIds = array_slice($extraUserIds, 0, $maxCount);

		$result = array();

		if (!empty($extraUserIds))
		{
			$userFilter = array();
			if (!empty($filter['ID']))
			{
				if (is_array($filter['ID']))
				{
					foreach ($filter['ID'] as $filterId)
					{
						if (in_array($filterId, $extraUserIds))
							$userFilter['@ID'][] = $filterId;
					}
				}
				elseif (in_array($filter['ID'], $extraUserIds))
				{
					$userFilter['ID'] = $filter['ID'];
				}
			}
			else
			{
				$userFilter['@ID'] = $extraUserIds;
			}

			$userFilter['UF_DEPARTMENT'] = false;
			$extraUsers = \Bitrix\Main\UserTable::getList(array(
				'filter' => $userFilter,
				'order' => $order,
			));

			while ($user = $extraUsers->Fetch())
			{
				$result[] = $user;
			}
		}




		return $result;
	}
}