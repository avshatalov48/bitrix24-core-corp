<?php

namespace Bitrix\Baas\UseCase\External\Response;

use Bitrix\Main;

class GetBaasSalesStatusResult extends Main\Result
{
	public function __construct(
		public readonly int $header,
		public readonly string $statusCode,
		public readonly string $statusDescription,
	)
	{
		parent::__construct();
	}
}
