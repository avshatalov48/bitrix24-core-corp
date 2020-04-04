<?php

namespace Bitrix\Disk;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class SystemUser extends User
{
	const SYSTEM_USER_ID = 0;

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

	public static function isSystemUserId($id)
	{
		return is_numeric($id) && ((int)$id) === self::SYSTEM_USER_ID;
	}

	public function getId()
	{
		return self::SYSTEM_USER_ID;
	}

	public function isIntranetUser()
	{
		return true;
	}

	public function isExtranetUser()
	{
		return false;
	}

	public function getLastName()
	{
		return Loc::getMessage('DISK_SYSTEM_LAST_NAME');
	}

	public function getLogin()
	{
		return Loc::getMessage('DISK_SYSTEM_USER_LOGIN');
	}

	public function getName()
	{
		return Loc::getMessage('DISK_SYSTEM_USER_NAME');
	}

	public function getSecondName()
	{
		return Loc::getMessage('DISK_SYSTEM_USER_SECOND_NAME');
	}

	public function getShortName()
	{
		return Loc::getMessage('DISK_SYSTEM_USER_SHORT_NAME');
	}
}