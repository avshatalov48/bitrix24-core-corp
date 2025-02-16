<?php

declare(strict_types=1);

namespace Bitrix\Booking\Integration\Booking;

use Bitrix\Booking\Entity\Booking\Client;
use Bitrix\Booking\Entity\Booking\ClientCollection;
use Bitrix\Booking\Entity\Booking\ClientTypeCollection;
use Bitrix\Booking\Integration\Booking\Message\MessageSender;

interface ClientProviderInterface
{
	public function getClientTypeCollection(): ClientTypeCollection;

	public function getClientName(Client $client): string;

	/**
	 * @return MessageSender[]
	 */
	public function getMessageSenders(): array;

	public function pickPrimaryClient(ClientCollection $clientCollection): Client|null;

	public function isClientNew(Client $client): bool;

	public function doesClientExist(Client $client): bool;

	public function loadClientDataForCollection(...$clientCollections): void;

	public function getClientDataRecent(): array;
}
