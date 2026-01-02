<?php

namespace Bitrix\Baas\UseCase\External;

use \Bitrix\Main;
use \Bitrix\Baas;

class VerifyAckDomain extends BaseDomainAction
{
	private readonly string $ack;
	private readonly string $syn;

	public function __construct(
		Request\VerifyAckRequest $request,
	)
	{
		parent::__construct($request);
		$this->ack = $request->ack;
		$this->syn = $request->syn;
	}

	protected function run(): Main\Result
	{
		if ($this->ack === $this->client->getSynCode())
		{
			return (new Baas\UseCase\External\Response\VerifyAckDomainResult())
				->setAck($this->syn)
			;
		}

		$result = (new Main\Result())
			->addError(new Main\Error('Acknowledge code is not valid', 'baas:9901'))
		;

		return $result;
	}
}
