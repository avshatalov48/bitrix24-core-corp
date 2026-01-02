<?php

namespace Bitrix\Baas\UseCase\External\Response;

use Bitrix\Main;

class VerifyAckDomainResult extends Main\Result
{
	protected string $code;

	public function setAck(string $code): static
	{
		$this->code = $code;

		$this->setData(['ack' => $this->code]);

		return $this;
	}
}
