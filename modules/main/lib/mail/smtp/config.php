<?php

namespace Bitrix\Main\Mail\Smtp;

use \Bitrix\Main;
use \Bitrix\Mail;

class Config
{

	protected $from, $host, $port, $protocol, $login, $password;

	public function __construct(array $params = null)
	{
		if (!empty($params) && is_array($params))
		{
			foreach ($params as $name => $value)
			{
				$setter = sprintf('set%s', $name);
				if (is_callable(array($this, $setter)))
					$this->$setter($value);
			}
		}
	}

	public function setFrom($from)
	{
		$this->from = $from;
		return $this;
	}

	public function setHost($host)
	{
		$this->host = $host;
		return $this;
	}

	public function setPort($port)
	{
		$this->port = (int) $port;
		return $this;
	}

	public function setProtocol($protocol)
	{
		$this->protocol = $protocol;
		return $this;
	}

	public function setLogin($login)
	{
		$this->login = $login;
		return $this;
	}

	public function setPassword($password)
	{
		$this->password = $password;
		return $this;
	}

	public function getFrom()
	{
		return $this->from;
	}

	public function getHost()
	{
		return $this->host;
	}

	public function getPort()
	{
		return $this->port;
	}

	public function getProtocol()
	{
		return $this->protocol;
	}

	public function getLogin()
	{
		return $this->login;
	}

	public function getPassword()
	{
		return $this->password;
	}

	public static function canCheck()
	{
		return Main\Loader::includeModule('mail') && class_exists('Bitrix\Mail\Smtp');
	}

	public function check(&$error = null, Main\ErrorCollection &$errors = null)
	{
		$error = null;
		$errors = null;

		if (!$this->canCheck())
		{
			return null;
		}

		$client = new Mail\Smtp(
			$this->host,
			$this->port,
			('smtps' === $this->protocol || ('smtp' !== $this->protocol && 465 === $this->port)),
			true,
			$this->login,
			$this->password
		);

		if (!$client->authenticate($error))
		{
			$errors = $client->getErrors();
			return false;
		}

		return true;
	}

}
