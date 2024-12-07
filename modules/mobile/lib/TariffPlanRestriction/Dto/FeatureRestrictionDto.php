<?php

namespace Bitrix\Mobile\TariffPlanRestriction\Dto;

use Bitrix\Mobile\Dto\Dto;

final class FeatureRestrictionDto extends Dto
{
	public string $code;
	public string $title;
	public bool $isRestricted;
	public bool $isPromo;
}
