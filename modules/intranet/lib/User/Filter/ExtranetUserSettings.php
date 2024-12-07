<?php

namespace Bitrix\Intranet\User\Filter;

use Bitrix\Intranet\CurrentUser;
use Bitrix\Main\Filter\UserSettings;
use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\UserToGroupTable;

class ExtranetUserSettings extends UserSettings
{
	public function getCurrentUserId(): int
	{
		return CurrentUser::get()->getId();
	}

	public function isCurrentUserExtranet(): bool
	{
		return Loader::includeModule('extranet') && !\CExtranet::isIntranetUser();
	}

	public function isCurrentUserExtranetAdmin(): bool
	{
		return Loader::includeModule('extranet') && \CExtranet::IsExtranetAdmin();
	}

	public function getWorkgroupIdList(): array
	{
		$workgroupIdList = [];
		$res = UserToGroupTable::getList([
			'filter' => [
				'=USER_ID' => $this->getCurrentUserId(),
				'@ROLE' => UserToGroupTable::getRolesMember(),
				'=GROUP.ACTIVE' => 'Y'
			],
			'select' => [ 'GROUP_ID' ]
		]);

		while ($userToGroupFields = $res->fetch())
		{
			$workgroupIdList[] = $userToGroupFields['GROUP_ID'];
		}

		return array_unique($workgroupIdList);
	}

	public function getPublicUserIdList(): array
	{
		$res = \Bitrix\Main\UserTable::getList([
			'filter' => [
				'!UF_DEPARTMENT' => false,
				'=UF_PUBLIC' => true,
			],
			'select' => [ 'ID' ]
		]);
		$publicUserIdList = [];

		while($userFields = $res->fetch())
		{
			$publicUserIdList[] = (int)$userFields['ID'];
		}

		return $publicUserIdList;
	}
}