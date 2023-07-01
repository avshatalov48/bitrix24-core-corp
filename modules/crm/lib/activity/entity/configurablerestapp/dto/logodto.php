<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Service\Timeline\Layout\Common\Logo;
use Bitrix\Crm\Timeline\Entity\CustomLogoTable;

final class LogoDto extends Dto
{
	public ?string $code = null;
	public ?ActionDto $action = null;

	protected function getValidators(array $fields): array
	{
		return [
			new \Bitrix\Crm\Dto\Validator\RequiredField($this, 'code'),
			new \Bitrix\Crm\Dto\Validator\EnumField($this, 'code', $this->getAllowedLogoCodes()),
			new \Bitrix\Crm\Dto\Validator\ObjectField($this, 'action'),
		];
	}

	private function getAllowedLogoCodes(): array
	{
		$systemLogos = Logo::getSystemLogoCodes();
		$customLogos = array_column(CustomLogoTable::getList([
			'select' => [
				'CODE',
			],
			'cache' => ['ttl' => 864000]
		])->fetchAll(), 'CODE');

		return array_merge($systemLogos, $customLogos);
	}
}
