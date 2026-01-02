<?php

declare(strict_types=1);

namespace Bitrix\Baas\Contract;

interface ServiceService
{
	public function getByCode(string $code): Service;
}
