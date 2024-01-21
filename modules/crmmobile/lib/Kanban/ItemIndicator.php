<?php

namespace Bitrix\CrmMobile\Kanban;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\ArgumentException;

final class ItemIndicator
{
	use Singleton;

	private const OWN_TYPE = 'own';
	private const SOMEONE_TYPE = 'someone';

	public function getOwnIndicator(): array
	{
		return $this->getIndicator(self::OWN_TYPE);
	}

	public function getSomeoneIndicator(): array
	{
		return $this->getIndicator(self::SOMEONE_TYPE);
	}

	private function getIndicator(string $type): array
	{
		$indicators = $this->getIndicators();
		if (!isset($indicators[$type]))
		{
			throw new ArgumentException('Indicator "' . $type . '" not known');
		}

		return $indicators[$type];
	}

	private function getIndicators(): array
	{
		return [
			self::OWN_TYPE => [
				'id' => self::OWN_TYPE,
			],
			self::SOMEONE_TYPE => [
				'id' => self::SOMEONE_TYPE,
			],
		];
	}
}
