<?php

namespace Bitrix\Crm\UserField;

use Bitrix\Main\Security\Random;

final class FieldNameGenerator
{
	private string $pattern = 'UF_#ENTITY_ID#_#CODE#';
	private int $minCodeSalt = 1000;
	private int $maxCodeSalt = 9999;

	public function generate(string $entityId): string
	{
		$replace = [
			'#ENTITY_ID#' => $entityId,
			'#CODE#' => $this->generateCode(),
		];

		return strtr($this->pattern, $replace);
	}

	private function generateCode(): string
	{
		return time() . Random::getInt($this->minCodeSalt, $this->maxCodeSalt);
	}
}
