<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Entity\Type;

abstract class BaseInvitation
{
	abstract public function toArray(): array;
}