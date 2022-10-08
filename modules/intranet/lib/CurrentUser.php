<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\Intranet;

use Bitrix\Main;
use Bitrix\Main\Loader;

class CurrentUser
{
	/** @var Main\Engine\CurrentUser */
	private $currentUser;

	public static function get()
	{
		$self = new static();
		$self->currentUser = Main\Engine\CurrentUser::get();

		return $self;
	}

	public function __call($name, $arguments)
	{
		return call_user_func_array(array($this->currentUser, $name), $arguments);
	}

	public function isAdmin(): bool
	{
		return (
			Loader::includeModule('bitrix24')
			&& \CBitrix24::IsPortalAdmin($this->currentUser->getId())
		)
		|| $this->currentUser->isAdmin();
	}

	public function getDepartmentIds(): ?array
	{
		if ($this->currentUser->getId() > 0)
		{
			return Main\Access\User\UserSubordinate::getDepartmentsByUserId($this->currentUser->getId());
		}

		return null;
	}
}