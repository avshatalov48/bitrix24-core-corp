<?php

namespace Bitrix\Ldap\Internal\Security;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Security\Random;

/**
 * @package Bitrix\Ldap\Internal
 * You must not use classes from Internal namespace outside current module.
 */
final class Password
{
	private int $length;
	private int $chars;
	private string $value;

	/**
	 * @param int $length
	 * @param int $chars Use mask from \Bitrix\Main\Security\Random::ALPHABET_*
	 * @throws ArgumentException
	 */
	public function __construct(int $length = 32, int $chars = Random::ALPHABET_ALL)
	{
		if ($length <= 0)
		{
			throw new ArgumentException('Password cannot be empty');
		}

		$this->length = $length;
		$this->chars = $chars;
		$this->value = Random::getStringByAlphabet($this->length, $this->chars, true);
	}

	public function __toString()
	{
		return $this->value;
	}
}
