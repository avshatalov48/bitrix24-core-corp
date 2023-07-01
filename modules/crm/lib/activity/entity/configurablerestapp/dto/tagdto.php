<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;

use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;

final class TagDto extends \Bitrix\Crm\Dto\Dto
{
	public ?TextWithTranslationDto $title = null;
	public ?string $type = null;
	public ?ActionDto $action = null;

	protected function getValidators(array $fields): array
	{
		return [
			new \Bitrix\Crm\Dto\Validator\RequiredField($this, 'title'),
			new Dto\Validator\TextWithTranslationField($this, 'title'),
			new \Bitrix\Crm\Dto\Validator\RequiredField($this, 'type'),
			new \Bitrix\Crm\Dto\Validator\EnumField($this, 'type', [
				Tag::TYPE_SUCCESS,
				Tag::TYPE_FAILURE,
				Tag::TYPE_WARNING,
				Tag::TYPE_PRIMARY,
				Tag::TYPE_SECONDARY,
			]),
			new \Bitrix\Crm\Dto\Validator\ObjectField($this, 'action'),
		];
	}
}
