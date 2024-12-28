<?php

namespace Bitrix\Extranet\Enum\User;

enum ExtranetRole: string
{
	case Extranet = 'extranet';
	case FormerExtranet = 'former-extranet';
	case Collaber = 'collaber';
	case FormerCollaber = 'former-collaber';

	public static function getValues(): array
	{
		$values = [];
		$cases = self::cases();

		foreach ($cases as $case)
		{
			$values[] = $case->value;
		}

		return $values;
	}

	public static function getCurrentExtranetRole(): array
	{
		return [
			self::Extranet,
			self::Collaber,
		];
	}

	public static function getFormerExtranetRole(): array
	{
		return [
			self::FormerExtranet,
			self::FormerCollaber,
		];
	}

	public function isFormerExtranet(): bool
	{
		return in_array($this, self::getFormerExtranetRole(), true);
	}

	public function isChargeable(): bool
	{
		return $this->value !== self::Collaber->value;
	}
}
