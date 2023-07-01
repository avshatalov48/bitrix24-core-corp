<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\ObjectField;
use Bitrix\Crm\Dto\Validator\RequiredField;

final class LayoutDto extends Dto
{
	public ?IconDto $icon = null;
	public ?HeaderDto $header = null;
	public ?BodyDto $body = null;
	public ?FooterDto $footer = null;

	protected function getValidators(array $fields): array
	{
		return [
			new RequiredField($this, 'icon'),
			new ObjectField($this, 'icon'),

			new RequiredField($this, 'header'),
			new ObjectField($this, 'header'),

			new RequiredField($this, 'body'),
			new ObjectField($this, 'body'),

			new ObjectField($this, 'footer'),
		];
	}
}
