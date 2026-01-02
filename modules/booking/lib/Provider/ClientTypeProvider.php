<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Booking\Entity\Client\ClientType;
use Bitrix\Booking\Entity\Client\ClientTypeCollection;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Repository\ClientTypeRepositoryInterface;
use Bitrix\Booking\Provider\Params\GridParams;

class ClientTypeProvider
{
	private ClientTypeRepositoryInterface $repository;

	public function __construct()
	{
		$this->repository = Container::getClientTypeRepository();
	}

	public function get(string $code, string $moduleId): ?ClientType
	{
		return
			$this
				->repository
				->get($code, $moduleId)
			;
	}

	public function getList(GridParams $gridParams): ClientTypeCollection
	{
		return
			$this
				->repository
				->getList(
					filter: $gridParams->getFilter(),
				)
			;
	}
}
