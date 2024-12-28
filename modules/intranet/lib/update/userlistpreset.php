<?php

namespace Bitrix\Intranet\Update;

use Bitrix\Main\SiteTable;
use Bitrix\Main\Update\Stepper;
use Bitrix\Main\UserTable;

class UserListPreset extends Stepper
{
	protected static $moduleId = "intranet";
	private int $limit = 100;

	public function getUserIds($offset = 0): array
	{
		return UserTable::query()
			->setLimit($this->limit)
			->setSelect(['ID'])
			->setOffset($offset)
			->fetchCollection()
			->getIdList();
	}

	public function execute(array &$result): bool
	{
		$siteList = SiteTable::getList([
			'select' => ['LID', 'LANGUAGE_ID'],
			'filter' => ['=DEF' => 'Y', '=ACTIVE' => 'Y'],
			'cache' => ['ttl' => 86400],
		]);
		$siteIdList = [];

		while ($site = $siteList->fetch())
		{
			$siteIdList[] = $site['LID'];
		}

		if (empty($result))
		{
			$result["steps"] = 0;
			$result["count"] = ceil(UserTable::getCount() / $this->limit);
		}
		$userIds = $this->getUserIds($result["steps"] * $this->limit);
		foreach ($userIds as $id)
		{
			foreach ($siteIdList as $siteId)
			{
				$filterOptions = \CUserOptions::GetOption('main.ui.filter', 'INTRANET_USER_LIST_' . $siteId, false, $id);

				if ($filterOptions === false)
				{
					continue;
				}

				$filterOptions['update_default_presets'] = true;

				\CUserOptions::SetOption('main.ui.filter', 'INTRANET_USER_LIST_' . $siteId, $filterOptions, false, $id);
			}
		}
		$result["steps"]++;

		return ($result["steps"] <= $result["count"] ? self::CONTINUE_EXECUTION : self::FINISH_EXECUTION);
	}
}