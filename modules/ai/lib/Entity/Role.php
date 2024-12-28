<?php

declare(strict_types=1);

namespace Bitrix\AI\Entity;

use Bitrix\AI\Model\EO_Role;

class Role extends EO_Role
{
	use TranslateTrait;

	/**
	 * Return role name
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->get('ROLE_TRANSLATE_NAME')?->getText() ?? $this->getDefaultName();
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
	 * Return role description
	 *
	 * @return string
	 */
	public function getDescription(): string
	{
		return $this->get('ROLE_TRANSLATE_DESCRIPTION')?->getText() ?? $this->getDefaultDescription();
	}
}