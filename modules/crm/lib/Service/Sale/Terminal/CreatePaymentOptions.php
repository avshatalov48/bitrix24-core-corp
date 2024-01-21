<?php

declare(strict_types = 1);

namespace Bitrix\Crm\Service\Sale\Terminal;

use Bitrix\Crm\ItemIdentifier;

final class CreatePaymentOptions
{
	private ?string $currency = null;

	private ?ItemIdentifier $entity = null;

	private ?int $responsibleId = null;

	private ?ItemIdentifier $client = null;

	private ?string $phoneNumber = null;

	private ?string $clientName = null;

	public static function createFromArray(array $options): self
	{
		$result = new self();

		if (array_key_exists('currency', $options))
		{
			$result->setCurrency((string)$options['currency']);
		}

		if (array_key_exists('entity', $options) && $options['entity'] instanceof ItemIdentifier)
		{
			$result->setEntity($options['entity']);
		}

		if (array_key_exists('responsibleId', $options))
		{
			$result->setResponsibleId((int)$options['responsibleId']);
		}

		if (array_key_exists('client', $options) && $options['client'] instanceof ItemIdentifier)
		{
			$result->setClient($options['client']);
		}

		if (array_key_exists('phoneNumber', $options))
		{
			$result->setPhoneNumber((string)$options['phoneNumber']);
		}

		if (array_key_exists('clientName', $options))
		{
			$result->setClientName((string)$options['clientName']);
		}

		return $result;
	}

	public function getCurrency(): ?string
	{
		return $this->currency;
	}

	public function setCurrency(?string $currency): CreatePaymentOptions
	{
		$this->currency = $currency;

		return $this;
	}

	public function getEntity(): ?ItemIdentifier
	{
		return $this->entity;
	}

	public function setEntity(?ItemIdentifier $entity): CreatePaymentOptions
	{
		$this->entity = $entity;

		return $this;
	}

	public function getResponsibleId(): ?int
	{
		return $this->responsibleId;
	}

	public function setResponsibleId(?int $responsibleId): CreatePaymentOptions
	{
		$this->responsibleId = $responsibleId;

		return $this;
	}

	public function getClient(): ?ItemIdentifier
	{
		return $this->client;
	}

	public function setClient(?ItemIdentifier $client): CreatePaymentOptions
	{
		$this->client = $client;

		return $this;
	}

	public function getPhoneNumber(): ?string
	{
		return $this->phoneNumber;
	}

	public function setPhoneNumber(?string $phoneNumber): CreatePaymentOptions
	{
		$this->phoneNumber = $phoneNumber;

		return $this;
	}

	public function getClientName(): ?string
	{
		return $this->clientName;
	}

	public function setClientName(?string $clientName): CreatePaymentOptions
	{
		$this->clientName = $clientName;

		return $this;
	}
}
