<?php

declare(strict_types=1);

namespace Bitrix\AI\Entity;

use Bitrix\AI\Model\EO_Prompt;

class Prompt extends EO_Prompt
{
	use TranslateTrait;

	/**
	 * Return industry description by langCode.
	 *
	 * @param string $langCode
	 *
	 * @return string
	 */
	public function getText(string $langCode): string
	{
		return self::translate($this->getTextTranslates(), $langCode);
	}

}