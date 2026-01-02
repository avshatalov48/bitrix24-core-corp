<?php

declare(strict_types=1);

namespace Bitrix\AI\Entity;

use Bitrix\AI\Container;
use Bitrix\AI\Enum\RoleAvatarSize;
use Bitrix\AI\Model\EO_Role;
use Bitrix\AI\ShareRole\Service\RoleService;

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

		if (isset($avatars['fileIds']))
		{
			$roleService = $this->getRoleService();

			return [
				'small' => $roleService->getAvatarLink($this->getId(), RoleAvatarSize::Small, $this->getHash()),
				'medium' => $roleService->getAvatarLink($this->getId(), RoleAvatarSize::Medium, $this->getHash()),
				'large' => $roleService->getAvatarLink($this->getId(), RoleAvatarSize::Large, $this->getHash()),
			];
		}

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

	private function getRoleService(): RoleService
	{
		return Container::init()->getItem(RoleService::class);
	}
}