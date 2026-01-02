<?php

declare(strict_types=1);

namespace Bitrix\Baas\Controller\ActionFilter;

use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Web\JWT;
use Bitrix\Main\Engine\ActionFilter;
use UnexpectedValueException;

final class BaasControllerAuthentication extends ActionFilter\Base
{
	public function __construct(
		private readonly string $secretKey,
	)
	{
		parent::__construct();
	}

	public function onBeforeAction(Event $event): ?Main\EventResult
	{
		$authorizationHeader = $this->getAuthorizationHeader();

		if (!$authorizationHeader)
		{
			Main\Context::getCurrent()?->getResponse()->setStatus('400 Bad Request');
			$this->addError(new Main\Error('Empty authorization header'));

			return new Main\EventResult(Main\EventResult::ERROR, null, null, $this);
		}

		try
		{
			$authorizationHeader = substr($authorizationHeader, \strlen('Bearer '));

			$data = JWT::decode($authorizationHeader, $this->secretKey, ['HS256']);

			if (empty($data?->payload?->baasControllerUrl))
			{
				throw new UnexpectedValueException('Could not find url in payload to check url.');
			}

/*			$uri = new Uri($data->payload->baasControllerUrl);

			if ($uri->getHost() !== $this->billingUrl)
			{
				throw new UnexpectedValueException('Invalid url in payload');
			}*/
		}
		catch (UnexpectedValueException $e)
		{
			$this->addError(new Error('Invalid authorization header', 0, [
				'invalidBearer' => $authorizationHeader,
				'message' => $e->getMessage(),
			]));
		}

		if (!$this->errorCollection->isEmpty())
		{
			$context = Main\Context::getCurrent();
			$context?->getResponse()->setStatus('400 Bad Request');

			return new Main\EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}

	private function getAuthorizationHeader(): ?string
	{
		if (!($context = Main\Context::getCurrent()))
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

	public function getSecretKey(): string
	{
		return $this->secretKey;
	}
}
