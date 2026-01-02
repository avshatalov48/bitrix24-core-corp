<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

use Bitrix\Main\Type\Contract\Arrayable;

interface EntityCollectionInterface extends \Bitrix\Main\Entity\EntityCollectionInterface, Arrayable
{
	public function __construct(EntityInterface ...$entities);

	public function getIds(): array;

	public static function mapFromArray(array $props): static;
}