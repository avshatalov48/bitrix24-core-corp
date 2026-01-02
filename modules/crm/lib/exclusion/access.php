<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Exclusion;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;

/**
 * Class Access
 *
 * @package Bitrix\Crm\Exclusion
 */
class Access
{
	const READ = 'READ';
	const WRITE = 'WRITE';

	/** @var static $currentUser Instance for current user. */
	protected static $currentUser;

	/** @var int $userId User ID. */
	protected $userId;

	/**
	 * Get instance for current user.
	 *
	 * @return static
	 */
	public static function current()
	{
		if (!static::$currentUser)
		{
			static::$currentUser = new static();
		}

		return static::$currentUser;
	}

	/**
	 * Access constructor.
	 *
	 * @param int|null $userId User ID.
	 */
	public function __construct($userId = null)
	{
		if (!$userId)
		{
			$userId = Container::getInstance()->getContext()->getUserId();
		}

		$this->userId = $userId;
	}

	/**
	 * Return true if user can read.
	 *
	 * @return bool
	 */
	public function canRead(): bool
	{
		return Container::getInstance()->getUserPermissions($this->userId)->exclusion()->canReadItems();
	}

	/**
	 * Return true if user can write.
	 *
	 * @return bool
	 */
	public function canWrite(): bool
	{
		return Container::getInstance()->getUserPermissions($this->userId)->exclusion()->canEditItems();
	}

	/**
	 * Get error text by code.
	 *
	 * @return string
	 */
	public static function getErrorText($code)
	{
		return Loc::getMessage("CRM_EXCLUSION_ACCESS_ERROR_$code");
	}
}
