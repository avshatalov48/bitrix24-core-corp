<?php

namespace Bitrix\Disk\Search;


use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\User;

final class FullTextBuilder
{
	protected $tokens = array();

	/** @var  ErrorCollection */
	protected $errorCollection;

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	public static function create()
	{
		return new static();
	}

	/**
	 * Adds arbitrary string content to the builder.
	 * @param string $token Arbitrary string.
	 * @return $this.
	 */
	public function addText($token)
	{
		$token = (string)$token;
		if ($token == '')
		{
			return $this;
		}

		$value = static::prepareToken($token);
		$this->tokens[$value] = true;

		return $this;
	}

	/**
	 * Adds full user name to the builder.
	 *
	 * @param int|User|\CUser $user Id of the user or User model.
	 *
	 * @return $this
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function addUser($user)
	{
		if ($user instanceof User)
		{
			return $this->addText($user->getFormattedName());
		}

		$userId = User::resolveUserId($user);
		if ($userId <= 0)
		{
			return $this;
		}

		return self::addUser(User::loadById($userId));
	}

	public function getSearchValue()
	{
		return implode(" ", array_keys($this->tokens));
	}

	/**
	 * Applies ROT13 transform to search token, in order to bypass default mysql search blacklist.
	 * @param string $token Search token.
	 * @return string
	 */
	protected static function prepareToken($token)
	{
		return str_rot13($token);
	}
}