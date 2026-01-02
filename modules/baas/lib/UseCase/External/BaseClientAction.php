<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External;

use Bitrix\Baas;

abstract class BaseClientAction extends BaseAction
{
	protected string $hostKey;
	protected string $hostSecret;

	/**
	 * @param Request\BaseRequest $request
	 * @throws Exception\ClientIsNotRegistered
	 */
	public function __construct(
		Baas\UseCase\External\Request\BaseRequest $request,
	)
	{
		parent::__construct($request);
		[
			$hostKey,
			$hostSecret,
		] = array_values($this->client->getRegistrationData() ?? [null, null]);

		if (empty($hostKey) || empty($hostSecret))
		{
			throw new Exception\ClientIsNotRegistered();
		}

		$this->hostKey = $hostKey;
		$this->hostSecret = $hostSecret;
	}

	protected function getSender(): Baas\UseCase\External\Sender\BaseClientSender
	{
		$sender = new Baas\UseCase\External\Sender\BaseClientSender($this->server, $this->client);
		$sender->setHostKey($this->hostKey);

		return $sender;
	}
}
