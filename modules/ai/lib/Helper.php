<?php declare(strict_types=1);

namespace Bitrix\AI;

use Bitrix\Main\Security\Random;

class Helper
{
	/**
	 * @return string
	 */
	public static function generateUUID(): string
	{
		$data = Random::getBytes(16);

		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}
}
