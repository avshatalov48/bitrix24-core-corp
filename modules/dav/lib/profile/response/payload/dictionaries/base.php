<?php

namespace Bitrix\Dav\Profile\Response\Payload\Dictionaries;

/**
 * Class Base
 * @package Bitrix\Dav\Profile\Response\Payload\Dictionaries
 */
abstract class Base
{
	private $user;

	/**
	 * @return mixed
	 */
	abstract public function prepareBodyContent();

	/**
	 * @return bool
	 */
	abstract public function isAvailable();

	/**
	 * @param array $user User array.
	 */
	public function setUser($user)
	{
		$this->user = $user;
	}

	/**
	 * @return mixed
	 */
	public function getUser()
	{
		return $this->user;
	}


	/**
	 * @return string
	 */
	public function getProfileIdentifier()
	{
		$requester = $this->getUser();
		return sha1(SITE_ID . $requester['ID']);
	}
}