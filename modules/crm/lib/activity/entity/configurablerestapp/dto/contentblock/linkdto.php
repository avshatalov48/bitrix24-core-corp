<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ContentBlock;

use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ActionDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;

final class LinkDto extends BaseContentBlockDto
{
	public ?Dto\TextWithTranslationDto $text = null;
	public ?bool $bold = null;
	public ?ActionDto $action = null;

	protected function getValidators(array $fields): array
	{
		return [
			new \Bitrix\Crm\Dto\Validator\RequiredField($this, 'text'),
			new Dto\Validator\TextWithTranslationField($this, 'text'),
			new \Bitrix\Crm\Dto\Validator\RequiredField($this, 'action'),
			new \Bitrix\Crm\Dto\Validator\ObjectField($this, 'action'),
		];
	}
}
