<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ContentBlock;

use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ContentBlockDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;

final class WithTitleDto extends BaseContentBlockDto
{
	public ?Dto\TextWithTranslationDto $title = null;
	public ?bool $inline = null;
	public ?ContentBlockDto $block = null;

	protected function getValidators(array $fields): array
	{
		return [
			new \Bitrix\Crm\Dto\Validator\RequiredField($this, 'block'),
			new \Bitrix\Crm\Dto\Validator\ObjectField($this, 'block'),
			new Dto\Validator\SimpleContentBlockField($this, 'block', false),
			new Dto\Validator\TextWithTranslationField($this, 'title'),
		];
	}
}
