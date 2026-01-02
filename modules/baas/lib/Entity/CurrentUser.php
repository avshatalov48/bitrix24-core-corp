<?php

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage bitrix24
 * @copyright 2001-2024 Bitrix
 */

namespace Bitrix\Baas\Entity;

use Bitrix\Main;
use Bitrix\Bitrix24;
/**
 * @method int|null getId()
 * @method mixed getLogin()
 * @method mixed getEmail()
 * @method mixed getFullName()
 * @method mixed getFirstName()
 * @method mixed getLastName()
 * @method mixed getSecondName()
 * @method array getUserGroups()
 * @method string getFormattedName()
 */
final class CurrentUser
{
	private Main\Engine\CurrentUser $currentUser;

	private static CurrentUser $instance;

	public static function get(): self
	{
		if (!isset(self::$instance))
		{
			self::$instance = self::create();
		}

		return self::$instance;
	}

	public function __call($name, $arguments)
	{
		return call_user_func_array([$this->currentUser, $name], $arguments);
	}

	public static function create(): self
	{
		$self = new self();
		$self->currentUser = Main\Engine\CurrentUser::get();

		return $self;
	}

	public function isAdmin(): bool
	{
		return Main\Loader::includeModule('bitrix24')
			? Bitrix24\CurrentUser::get()->isAdmin() : $this->currentUser->isAdmin();
	}
}
