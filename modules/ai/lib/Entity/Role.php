<?php

declare(strict_types=1);

namespace Bitrix\AI\Entity;

use Bitrix\AI\Model\EO_Role;

class Role extends EO_Role
{
	use TranslateTrait;

	/**
	 * Return role name by langCode.
	 *
	 * @param string $langCode
	 * @return string
	 */
	public function getName(string $langCode): string
	{
		return self::translate($this->getNameTranslates(), $langCode);
	}

	/**
	 * Return role name by langCode.
	 *
	 * @return array
	 */
	public function getAvatar(): array
	{
		$avatars = parent::getAvatar();
		if ($avatars === '')
		{
			return [
				'small' => '',
				'medium' => '',
				'large' => '',
			];
		}

		return $avatars;
	}

	/**
	 * Return role description by langCode.
	 *
	 * @param string $langCode
	 * @return string
	 */
	public function getDescription(string $langCode): string
	{
		return self::translate($this->getDescriptionTranslates(), $langCode);
	}

}