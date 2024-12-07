<?php
declare(strict_types=1);

namespace Bitrix\AI\Engine\Cloud\ActionFilter;

use Bitrix\Main\Context;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Web\JWT;
use Bitrix\Main\Web\Uri;
use UnexpectedValueException;

final class Authorization extends ActionFilter\Base
{
	private readonly string $secretKey;
	private readonly array $integrityGetKeys;

	public function __construct($secretKey, array $integrityGetKeys = [])
	{
		parent::__construct();

		$this->secretKey = $secretKey;
		$this->integrityGetKeys = $integrityGetKeys;
	}

	/**
	 * Handler for before action event.
	 * @param Event $event Event.
	 * @return EventResult|null
	 */
	public function onBeforeAction(Event $event): ?EventResult
	{
		$authorizationHeader = $this->getAuthorizationHeader();
		if (!$authorizationHeader)
		{
			Context::getCurrent()?->getResponse()->setStatus('400 Bad Request');
			$this->addError(new Error('Empty authorization header'));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		$oldValue = JWT::$leeway;
		JWT::$leeway = 30;

		try
		{
			$authorizationHeader = substr($authorizationHeader, \strlen('Bearer '));

			$data = JWT::decode($authorizationHeader, $this->secretKey, ['HS256']);
			JWT::$leeway = $oldValue;

			if (!$this->integrityGetKeys && $data)
			{
				return null;
			}

			if (!$data || !$data->payload || !$data->payload->url)
			{
				throw new UnexpectedValueException('Could not find url in payload to check url.');
			}

			$uri = new Uri($data->payload->url);
			mb_parse_str($uri->getQuery(), $params);

			$httpRequest = $this->getAction()->getController()->getRequest();
			foreach ($this->integrityGetKeys as $keyName)
			{
				if (!isset($params[$keyName]))
				{
					throw new UnexpectedValueException("Could not find {{$keyName}} in auth header.");
				}

				if (!$httpRequest || !$httpRequest->getQuery($keyName))
				{
					throw new UnexpectedValueException("Could not find {{$keyName}} in request (GET).");
				}

				if ($httpRequest->getQuery($keyName) !== $params[$keyName])
				{
					throw new UnexpectedValueException("Invalid malformed value for {{$keyName}}");
				}
			}

		}
		catch (UnexpectedValueException $e)
		{
			JWT::$leeway = $oldValue;

			$this->addError(new Error('Invalid authorization header', 0, [
				'invalidBearer' => $authorizationHeader,
				'message' => $e->getMessage(),
			]));
		}

		if (!$this->errorCollection->isEmpty())
		{
			$context = Context::getCurrent();
			$context?->getResponse()->setStatus('400 Bad Request');

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}

	private function getAuthorizationHeader(): ?string
	{
		$context = Context::getCurrent();
		if (!$context)
		{
			return null;
		}

		$auth = $context->getRequest()->getHeader('Authorization');
		if (!$auth)
		{
			$auth = $context->getServer()->get('REMOTE_USER');
		}

		return $auth;
	}
}