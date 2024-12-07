<?php

declare(strict_types=1);

namespace Bitrix\AI\Entity;

use Bitrix\AI\Model\EO_ImageStylePrompt;

class ImageStylePrompt extends EO_ImageStylePrompt
{
	use TranslateTrait;

	/**
	 * Return industry name by langCode.
	 *
	 * @param string $langCode
	 * @return string
	 */
	public function getName(string $langCode): string
	{
		return self::translate($this->getNameTranslates(), $langCode);
	}
}