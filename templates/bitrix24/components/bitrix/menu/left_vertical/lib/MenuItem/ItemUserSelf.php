<?php
namespace Bitrix\Intranet\LeftMenu\MenuItem;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class ItemUserSelf extends ItemUser
{
	public function getCode(): string
	{
		return 'self';
	}
}
