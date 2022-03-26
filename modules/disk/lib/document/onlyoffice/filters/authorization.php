<?php

namespace Bitrix\Disk\Document\OnlyOffice\Filters;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Web\JWT;
use Bitrix\Main\Web\Uri;

class Authorization extends ActionFilter\Base
{
	/** @var string */
	private $secretKey;
	/** @var array */
	private $integrityGetKeys;

	public function __construct($secretKey, array $integrityGetKeys = [])
	{
		parent::__construct();

		$this->secretKey = $secretKey;
		$this->integrityGetKeys = $integrityGetKeys;
	}

	public function onBeforeAction(Event $event)
	{
		$authorizationHeader = $this->getAuthorizationHeader();
		if (!$authorizationHeader)
		{
			Context::getCurrent()->getResponse()->setStatus('400 Bad Request');
			$this->addError(new Error('Empty authorization header'));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		$oldValue = JWT::$leeway;
		JWT::$leeway = 30;

		try
		{
			$authorizationHeader = substr($authorizationHeader, strlen('Bearer '));

			$data = JWT::decode($authorizationHeader, $this->secretKey, ['HS256']);
			JWT::$leeway = $oldValue;

			if (!$this->integrityGetKeys && $data)
			{
				return null;
			}

			if (!$data || !$data->payload || !$data->payload->url)
			{
				throw new \UnexpectedValueException("Could not find url in payload to check url.");
			}

			$uri = new Uri($data->payload->url);
			mb_parse_str($uri->getQuery(), $params);

			$httpRequest = $this->getAction()->getController()->getRequest();
			foreach ($this->integrityGetKeys as $keyName)
			{
				if (!isset($params[$keyName]))
				{
					throw new \UnexpectedValueException("Could not find {{$keyName}} in auth header.");
				}

				if (!$httpRequest || !$httpRequest->getQuery($keyName))
				{
					throw new \UnexpectedValueException("Could not find {{$keyName}} in request (GET).");
				}

				if ($httpRequest->getQuery($keyName) !== $params[$keyName])
				{
					throw new \UnexpectedValueException("Invalid malformed value for {{$keyName}}");
				}
			}

		}
		catch (\UnexpectedValueException $e)
		{
			JWT::$leeway = $oldValue;

			$this->addError(new Error('Invalid authorization header', 0, [
				'invalidBearer' => $authorizationHeader,
				'message' => $e->getMessage(),
			]));
		}

		if (!$this->errorCollection->isEmpty())
		{
			Context::getCurrent()->getResponse()->setStatus('400 Bad Request');

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}

	protected function getAuthorizationHeader(): ?string
	{
		$context = Context::getCurrent();
		$auth = $context->getRequest()->getHeader('Authorization');
		if (!$auth)
		{
			$auth = $context->getServer()->get('REMOTE_USER');
		}

		return $auth;
	}
}