<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\Internal\Request;

use \Bitrix\Baas;

class GetAdsInfoRequest
{
	public function __construct(
		public Baas\Entity\Service $service,
		public string $languageId = 'en',
	)
	{
	}
}
