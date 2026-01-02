<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\Internal\Response;

use \Bitrix\Baas;

class GetAdsInfoResult extends \Bitrix\Main\Result
{
	public function __construct(
		public ?Baas\Model\EO_ServiceAds $serviceAds,
	)
	{
		parent::__construct();
	}
}
