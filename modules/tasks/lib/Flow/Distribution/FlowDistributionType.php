<?php

namespace Bitrix\Tasks\Flow\Distribution;

enum FlowDistributionType: string
{
	case MANUALLY = 'manually';
	case QUEUE = 'queue';
	case HIMSELF = 'himself';

	/**
	 * @return string[]
	 */
	public static function values(): array
	{
		return array_column(self::cases(), 'value');
	}
}
