<?php

namespace Bitrix\Crm\ItemMiniCard\Layout\Field;

use Bitrix\Crm\ItemMiniCard\Layout\Field\Value\Client;

final class ClientField extends AbstractField
{
	private array $clients = [];

	public function __construct(
		public string $title,
	)
	{
	}

	public function addValue(Client $client): self
	{
		$this->clients[] = $client;

		return $this;
	}

	public function getName(): string
	{
		return 'ClientField';
	}

	public function getProps(): array
	{
		return [
			'title' => $this->title,
			'clients' => array_map(static fn (Client $client) => $client->toArray(), $this->clients),
		];
	}
}
