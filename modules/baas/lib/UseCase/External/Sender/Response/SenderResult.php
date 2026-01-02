<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Sender\Response;

use Bitrix\Main\Web\HttpClient;

class SenderResult extends \Bitrix\Main\Result
{
	protected HttpClient $httpClient;

	public function setClient(HttpClient $httpClient): static
	{
		$this->httpClient = $httpClient;

		return $this;
	}

	public function getClient(): HttpClient
	{
		return $this->httpClient;
	}
}
