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
use Bitrix\Main\UserAccessTable;

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
 * @method bool canDoOperation(string $operationName)
 */
class CurrentUser
{
	private Main\Engine\CurrentUser $currentUser;
	private ?array $userFields;

	private static CurrentUser $instance;

	public static function get(): CurrentUser
	{
		if (!isset(static::$instance))
		{
			static::$instance = static::create();
		}

		return static::$instance;
	}

	public static function create(): CurrentUser
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
		return call_user_func_array([$this->currentUser, $name], $arguments);
	}

	public function isAuthorized(): bool
	{
		return $this->getId() > 0;
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

	public function getPersonalPhotoId(): ?int
	{
		return isset($this->userFields['PERSONAL_PHOTO']) ? (int)$this->userFields['PERSONAL_PHOTO'] : null;
	}

	public function getWorkPosition(): ?string
	{
		return isset($this->userFields['WORK_POSITION']) ? (string)$this->userFields['WORK_POSITION'] : null;
	}

	public function getExternalAuthId(): ?string
	{
		return isset($this->userFields['EXTERNAL_AUTH_ID']) ? (string)$this->userFields['EXTERNAL_AUTH_ID'] : null;
	}
}