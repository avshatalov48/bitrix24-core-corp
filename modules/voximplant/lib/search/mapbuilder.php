<?php

namespace Bitrix\Voximplant\Search;

class MapBuilder
{
	/** @var array [search_token => true] */
	protected $tokens = array();

	/**
	 * StringBuilder constructor.
	 */
	public function __construct()
	{

	}

	/**
	 * Creates instance of the StringBuilder
	 * @return static
	 */
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
		if($token == '')
			return $this;

		$value = Content::prepareToken($token);
		$this->tokens[$value] = true;
		return $this;
	}

	/**
	 * Adds phone number to the builder.
	 * @param string $phone Phone number.
	 * @return $this
	 */
	public function addPhone($phone)
	{
		$phone = (string)$phone;
		$value = preg_replace("/[^0-9\#\*]/i", "", $phone);
		if($value == '')
			return $this;

		$length = strlen($value);
		if($length >= 10 && substr($value, 0, 1) === '7')
		{
			$altPhone = '8'.substr($value, 1);
			$this->tokens[$altPhone] = true;
		}

		//Right bound. We will stop when 3 digits are left.
		$bound = $length - 2;
		if($bound > 0)
		{
			for($i = 0; $i < $bound; $i++)
			{
				$key = substr($value, $i);
				$this->tokens[$key] = true;
			}
		}

		return $this;
	}

	/**
	 * Adds full user name to the builder.
	 * @param int $userId Id of the user.
	 * @return $this
	 */
	public function addUser($userId)
	{
		if($userId <= 0)
		{
			return $this;
		}

		$user = \Bitrix\Main\UserTable::getList(Array(
			'select' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'TITLE'),
			'filter' => array('=ID' => $userId)
		))->fetch();

		if(!is_array($user))
		{
			return $this;
		}

		$value = \CUser::FormatName(
			\CSite::GetNameFormat(),
			$user,
			true,
			false
		);

		$value = Content::prepareToken($value);
		if($value != '')
		{
			$this->tokens[$value] = true;
		}
		return $this;
	}

	/**
	 * Builds search string.
	 * @return string
	 */
	public function build()
	{
		return implode(" ", array_keys($this->tokens));
	}
}
