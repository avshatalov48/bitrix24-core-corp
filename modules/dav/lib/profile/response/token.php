<?php

namespace Bitrix\Dav\Profile\Response;

use Bitrix\Dav\TokensTable;
use Bitrix\Main\Web\Json;

/**
 * Class Token
 * @package Bitrix\Dav\Profile\Response
 */
class Token extends Base
{
	private $user;

	/**
	 * Base constructor.
	 */
	public function __construct()
	{
		global $USER;
		$this->setUser($USER);
		$this->setHeader('Content-Type: application/json');
		if (!$this->isAccess())
		{
			$this->setHeader('HTTP/1.0 403 Forbidden');
			$this->errors[] = 'Access denied for this user. User is not authorized in system';
			$this->setErrorBodyContent();
		}
		else
		{
			$body = array('token' => $this->getToken($this->getUser()->GetID()));
			$this->setBody(Json::encode($body));
		}

	}

	/**
	 * @return bool Check is user is authorized.
	 */
	public function isAccess()
	{
		return $this->getUser()->IsAuthorized();
	}

	/**
	 * @param string $userId Identifier of user.
	 * @return array|false token object from database.
	 */
	protected function getToken($userId)
	{
		$token = TokensTable::getToken($userId);
		if (!$token)
		{
			$result = TokensTable::createToken($userId);
			$token = $result['TOKEN'];
		}
		elseif (!TokensTable::isTokenValid($token))
		{
			$result = TokensTable::updateToken($token, $userId);
			$token = $result['TOKEN'];
		}
		return $token;
	}


	/**
	 * @return \CAllUser|\CUser Return current object user property.
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * Set $user to current user property
	 * @param \CAllUser|\CUser $user Object of user.
	 * @return void
	 */
	public function setUser($user)
	{
		$this->user = $user;
	}
}