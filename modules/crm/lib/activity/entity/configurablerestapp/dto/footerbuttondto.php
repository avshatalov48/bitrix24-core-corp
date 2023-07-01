<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;

use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;

final class FooterButtonDto extends \Bitrix\Crm\Dto\Dto
{
	public ?TextWithTranslationDto $title = null;
	public ?string $type = null;
	public ?ActionDto $action = null;
	public ?string $scope = null;
	public ?bool $hideIfReadonly = null;

	protected function getValidators(array $fields): array
	{
		return [
			new \Bitrix\Crm\Dto\Validator\RequiredField($this, 'title'),
			new Dto\Validator\TextWithTranslationField($this, 'title'),
			new \Bitrix\Crm\Dto\Validator\RequiredField($this, 'type'),
			new \Bitrix\Crm\Dto\Validator\RequiredField($this, 'action'),
			new \Bitrix\Crm\Dto\Validator\EnumField($this, 'type', [
				Button::TYPE_PRIMARY,
				Button::TYPE_SECONDARY,
			]),
			new \Bitrix\Crm\Dto\Validator\ObjectField($this, 'action'),
			new Dto\Validator\ScopeField($this, 'scope'),
		];
	}
}
