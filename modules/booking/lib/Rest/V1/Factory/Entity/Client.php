<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Factory\Entity;

use Bitrix\Booking\Entity\Client\ClientCollection;
use Bitrix\Booking\Provider\ClientTypeProvider;
use Bitrix\Main\Result;
use Bitrix\Booking\Entity\Client\ClientType;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Rest\V1\Factory\EntityFactory;
use Bitrix\Booking\Internals\Exception\Exception;

class Client extends EntityFactory
{
	private ClientTypeProvider $clientTypeProvider;

	public function __construct()
	{
		$this->clientTypeProvider = new ClientTypeProvider();
	}

	public function createCollection(): ClientCollection
	{
		return new ClientCollection();
	}

	public function validateRestFields(array $fields): Result
	{
		$validationResult = new Result();

		$clientType = $this->getClientType(
			code: (string)$fields['TYPE']['CODE'],
			moduleId: (string)$fields['TYPE']['MODULE'],
		);
		if (!$clientType)
		{
			$validationResult->addError(
				ErrorBuilder::build(
					message: "Client type not found",
					code: Exception::CODE_BOOKING_CLIENT_CREATE,
				)
			);
		}

		return $validationResult;
	}

	public function createFromRestFields(array $fields): \Bitrix\Booking\Entity\Client\Client
	{
		$client = new \Bitrix\Booking\Entity\Client\Client();

		$clientType = $this->getClientType(
			code: (string)$fields['TYPE']['CODE'],
			moduleId: (string)$fields['TYPE']['MODULE'],
		);
		$client->setType($clientType);

		$client->setId((int)$fields['ID']);

		return $client;
	}

	private function getClientType(string $code, string $moduleId): ?ClientType
	{
		return
			$this
				->clientTypeProvider
				->get($code, $moduleId)
			;
	}
}
