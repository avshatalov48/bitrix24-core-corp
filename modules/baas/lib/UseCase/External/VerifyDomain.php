<?php

namespace Bitrix\Baas\UseCase\External;

use \Bitrix\Main;

class VerifyDomain extends BaseDomainAction
{
	protected string $syn;

	public function __construct(
		protected Request\BaseRequest $request,
	)
	{
		parent::__construct($this->request);

		$this->syn = Main\Security\Random::getString(16);
		$this->client->setSynCode($this->syn);
	}

	protected function run(): Main\Result
	{
		$data = [
			'host' => $this->host,
			'syn' => $this->syn,
		];

		return $this->getSender()->performRequest('verify', $data);
	}
}
