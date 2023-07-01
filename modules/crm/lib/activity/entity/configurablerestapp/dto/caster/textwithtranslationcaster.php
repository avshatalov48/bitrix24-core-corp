<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\Caster;

use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\TextWithTranslationDto;
use Bitrix\Crm\Dto\Caster;

final class TextWithTranslationCaster extends Caster
{
	protected function castSingleValue($value)
	{
		return new TextWithTranslationDto($value);
	}
}
