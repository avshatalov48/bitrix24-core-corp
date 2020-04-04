<?php

namespace Bitrix\Disk;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class EmptyUser extends User
{
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
		return '';
	}

	public function getLogin()
	{
		return Loc::getMessage('DISK_EMPTY_USER_LOGIN');
	}

	public function getName()
	{
		return Loc::getMessage('DISK_EMPTY_USER_NAME');
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