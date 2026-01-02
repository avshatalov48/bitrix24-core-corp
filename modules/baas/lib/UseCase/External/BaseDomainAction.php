<?php

namespace Bitrix\Baas\UseCase\External;

use Bitrix\Baas;

abstract class BaseDomainAction extends BaseAction
{
	protected string $host;

	public function __construct(
		protected Baas\UseCase\External\Request\BaseRequest $request,
	)
	{
		parent::__construct($this->request);
		$this->host = $this->client->getHost();
	}

	protected function getSender(): Baas\UseCase\External\Sender\BaseDomainSender
	{
		return new Baas\UseCase\External\Sender\BaseDomainSender($this->server, $this->client);
	}
}
