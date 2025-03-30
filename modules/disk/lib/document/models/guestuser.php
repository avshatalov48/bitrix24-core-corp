<?php

namespace Bitrix\Disk\Document\Models;

use Bitrix\Disk\User;
use Bitrix\Main\Localization\Loc;

class GuestUser extends User
{
	public const GUEST_USER_ID = -1;

	protected function init()
	{
		parent::init();

		$this->lastName = $this->getLastName();
		$this->login = $this->getLogin();
		$this->name = $this->getName();
		$this->secondName = $this->getSecondName();
	}

	/**
	 * @return static
	 */
	public static function create()
	{
		return new static;
	}

	public static function isGuestUserId($id): bool
	{
		return is_numeric($id) && ($id < 0);
	}

	public function getId()
	{
		return self::GUEST_USER_ID;
	}

	public function getUniqueId()
	{
		return -time();
	}

	public function isIntranetUser()
	{
		return false;
	}

	public function isExtranetUser()
	{
		return true;
	}

	public function getLastName()
	{
		return '';
	}

	public function getLogin()
	{
		return Loc::getMessage('DISK_ONLYOFFICE_GUEST_USER_LOGIN');
	}

	public function getName()
	{
		return Loc::getMessage('DISK_ONLYOFFICE_GUEST_USER_NAME');
	}

	public function getSecondName()
	{
		return '';
	}

	public function getShortName()
	{
		return '';
	}
}