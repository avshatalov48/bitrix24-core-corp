<?php

namespace Bitrix\Baas\UseCase\External;

use \Bitrix\Main;
use \Bitrix\Baas;

class RegisterDomain extends VerifyDomain
{
	private ?string $regionId;
	private string $languageId = 'en';

	public function __construct(
		protected Baas\UseCase\External\Request\BaseRequest $request,
	)
	{
		parent::__construct($request);

		$this->regionId = $this->server->getRegionId();
	}

	public function setLanguageId(string $languageId): static
	{
		$this->languageId = $languageId;

		return $this;
	}

	protected function run(): Main\Result
	{
		$data = [
			'host' => $this->host,
			'regionId' => $this->regionId,
			'syn' => $this->syn,
			'languageId' => $this->languageId,
		];

		Baas\Internal\Diag\Logger::getInstance()->info('Prepare for registration', $data);

		$result = $this->getSender()->performRequest('register', $data);

		Baas\Internal\Diag\Logger::getInstance()->info('Registration results', $result->getData());

		$registrationData = $result->getData();

		if (empty($registrationData['hostKey']) || empty($registrationData['hostSecret']))
		{
			throw new Exception\BaasControllerRespondsInWrongFormatException(['hostKey', 'hostSecret']);
		}

		$this->client->setRegistrationData($registrationData['hostKey'], $registrationData['hostSecret']);

		(new Main\Event(
			'baas',
			'onDomainRegistered',
			['host' => $this->host],
		))->send();

		return $result;
	}
}
