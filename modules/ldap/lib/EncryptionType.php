<?php

namespace Bitrix\Ldap;

enum EncryptionType: int
{
	case None = 0;
	case Ssl = 1;
	case Tls = 2;

	public function port(): int
	{
		return match ($this)
		{
			self::Ssl => 636,
			default => 389,
		};
	}

	public function scheme(): string
	{
		return match($this)
		{
			self::Ssl => 'ldaps',
			default => 'ldap',
		};
	}
}
