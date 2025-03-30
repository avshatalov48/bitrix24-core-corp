<?php

declare(strict_types=1);

namespace Bitrix\Disk\Controller\Integration\Filter;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Type\JwtHolder;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Web\JWT;

class JwtFilter extends Base
{

	public function __construct(
		private readonly string $secret,
		private readonly JwtHolder $jwtHolder
	)
	{
		parent::__construct();
	}

	public function onBeforeAction(Event $event): ?EventResult
	{
		$authorization = $this->getAuthorizationHeader();
		if (!$authorization)
		{
			$this->addError(new Error('Unauthorized', 401));
			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		$authorization = explode(' ', $authorization);
		$authorization = $authorization[1];

		try
		{
			$data = JWT::decode($authorization, $this->secret, ['HS256']);
		}
		catch (\UnexpectedValueException $e)
		{
			$this->addError(new Error('Unauthorized', 401));
			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		$this->jwtHolder->setJwtData($data);

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