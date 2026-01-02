<?php

namespace Bitrix\Baas\UseCase\External\Sender;


class BaseClientSender extends BaseDomainSender
{
	protected string $clientKey;

	public function setHostKey(string $clientKey): static
	{
		$this->clientKey = $clientKey;

		return $this;
	}

	public function performRequest($action, array $parameters = []): Response\SenderResult
	{
		$parameters['hostKey'] = $this->clientKey;

		return parent::performRequest($action, $parameters);
	}

	protected function formatActionName(string $action): string
	{
		return "baascontroller.Client.$action";
	}
}
