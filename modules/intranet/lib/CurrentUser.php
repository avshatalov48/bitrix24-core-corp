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
use Bitrix\Main\Type\DateTime;

class CurrentUser
{
	private Main\Engine\CurrentUser $currentUser;
	private ?array $userFields;

	public static function get(): CurrentUser
	{
		$self = new static();
		$self->currentUser = Main\Engine\CurrentUser::get();
		$result = \CUser::GetById($self->currentUser->getId());

		if ($result)
		{
			$fields = $result->fetch();
			$self->userFields = is_array($fields) ? $fields : null;
		}

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

	public function getDateRegister(): ?DateTime
	{
		return isset($this->userFields['DATE_REGISTER']) ? DateTime::createFromText($this->userFields['DATE_REGISTER'])
			: null;
	}
}