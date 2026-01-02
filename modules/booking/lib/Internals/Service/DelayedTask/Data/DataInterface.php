<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\DelayedTask\Data;

use Bitrix\Booking\Internals\Service\DelayedTask\DelayedTaskType;
use Bitrix\Main\Type\Contract\Arrayable;

interface DataInterface extends Arrayable, \JsonSerializable
{
	public function getType(): DelayedTaskType;
}
