<?php

namespace Bitrix\Sign\Compatibility\Document;

use Bitrix\Sign\Type\Document\SchemeType;
use Bitrix\Sign\Type\ProviderCode;

class Scheme
{
	public static function createDefaultSchemeByProviderCode(string $providerCode): string
	{
		return $providerCode === ProviderCode::SES_RU ? SchemeType::ORDER : SchemeType::DEFAULT;
	}
}