<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Resource\ResourceLinkedEntityData;

use Bitrix\Main\Type\Contract\Arrayable;

interface ResourceLinkedEntityDataInterface extends Arrayable, \JsonSerializable
{
	public static function mapFromArray(array $props): static;
}
