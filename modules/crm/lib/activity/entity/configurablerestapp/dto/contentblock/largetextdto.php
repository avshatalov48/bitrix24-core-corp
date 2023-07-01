<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ContentBlock;

use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;

final class LargeTextDto extends BaseContentBlockDto
{
	public ?Dto\TextWithTranslationDto $value = null;

	protected function getValidators(array $fields): array
	{
		return [
			new \Bitrix\Crm\Dto\Validator\RequiredField($this, 'value'),
			new Dto\Validator\TextWithTranslationField($this, 'value'),
		];
	}
}
