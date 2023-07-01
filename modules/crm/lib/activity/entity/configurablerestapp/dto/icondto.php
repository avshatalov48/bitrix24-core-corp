<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Timeline\Entity\CustomIconTable;

final class IconDto extends Dto
{
	public ?string $code = null;

	protected function getValidators(array $fields): array
	{
		return [
			new \Bitrix\Crm\Dto\Validator\RequiredField($this, 'code'),
			new \Bitrix\Crm\Dto\Validator\EnumField($this, 'code', $this->getAllowedIconCodes()),
		];
	}

	private function getAllowedIconCodes(): array
	{
		$systemIcons = Icon::getSystemIcons();
		$customIcons = array_column(CustomIconTable::getList([
			'select' => [
				'CODE',
			],
			'cache' => ['ttl' => 864000]
		])->fetchAll(), 'CODE');

		return array_merge($systemIcons, $customIcons);
	}
}
