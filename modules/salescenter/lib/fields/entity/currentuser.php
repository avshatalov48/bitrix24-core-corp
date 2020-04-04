<?php

namespace Bitrix\SalesCenter\Fields\Entity;

use Bitrix\Main\Localization\Loc;

class CurrentUser extends User
{
	public function getName(): string
	{
		parent::getName();
		return Loc::getMessage('SALESCENTER_FIELDS_ENTITY_CURRENT_USER');
	}

	public function getCode(): string
	{
		return 'USER';
	}

	public function getCurrentUserId(): int
	{
		$id = 0;
		$user = \Bitrix\Main\Engine\CurrentUser::get();
		if($user)
		{
			$id = (int) $user->getId();
		}

		return $id;
	}
}