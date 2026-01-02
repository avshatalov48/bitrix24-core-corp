<?php

declare(strict_types=1);

namespace Bitrix\Crm\Dto\Booking;

use Bitrix\Main\Type\Contract\Arrayable;

interface EntityFieldsInterface extends Arrayable
{
	public function getId(): int;

	public function getCreatedBy(): int;

	/**
	 * @return Client[]
	 */
	public function getClients(): array;

	/**
	 * @return ExternalData[]
	 */
	public function getExternalData(): array;
}
