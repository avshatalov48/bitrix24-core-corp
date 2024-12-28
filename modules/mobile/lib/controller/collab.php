<?php

namespace Bitrix\Mobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Mobile\Collab\ActionFilter\CollabAccessControl;
use Bitrix\Mobile\Collab\Dto\CollabPermissionSettingsDto;
use Bitrix\Mobile\Collab\Dto\CollabSecuritySettingsDto;
use Bitrix\Mobile\Collab\Dto\CollabSettingsUserDto;
use Bitrix\Main\Engine\JsonController;
use Bitrix\Main\Loader;
use Bitrix\Mobile\Trait\PublicErrorsTrait;
use Bitrix\Intranet\Service\InviteLinkGenerator;
use Bitrix\SocialNetwork\Collab\Access\CollabAccessController;
use Bitrix\SocialNetwork\Collab\Access\CollabDictionary;

final class Collab extends JsonController
{
	use PublicErrorsTrait;

	public function configureActions(): array
	{
		$actions = [];

		foreach ($this->getQueryActionNames() as $queryActionName)
		{
			$actions[$queryActionName] = [
				'+prefilters' => [
					new CloseSession(),
				],
			];
		}

		return $actions;
	}

	protected function getQueryActionNames(): array
	{
		return [
			'getInviteSettings',
			'getCreateSettings',
			'getIsCollabNameExistsStatus',
		];
	}

	protected function getDefaultPreFilters(): array
	{
		$preFilters = parent::getDefaultPreFilters();
		$preFilters[] = new CollabAccessControl();

		return $preFilters;
	}

	protected function init()
	{
		parent::init();

		Loader::requireModule('socialnetwork');
		Loader::requireModule('im');
	}

	/**
	 * @restMethod mobile.Collab.getInviteSettings
	 * @return array
	 */
	public function getInviteSettingsAction(int $collabId): array
	{
		$canCurrentUserInvite = CollabAccessController::can($this->getCurrentUser()->getId(), CollabDictionary::INVITE, $collabId);
		$isBitrix24Included = Loader::includeModule('bitrix24');
		$inviteLink = null;
		if ($canCurrentUserInvite)
		{
			$linkGenerator = InviteLinkGenerator::createByCollabId($collabId);
			$inviteLink = empty($linkGenerator) ? '' : $linkGenerator->getShortCollabLink();
		}

		return [
			'canCurrentUserInvite' => $canCurrentUserInvite,
			'inviteLink' => $inviteLink,
			'isBitrix24Included' => $isBitrix24Included,
		];
	}

	/**
	 * @restMethod mobile.Collab.getCreateSettings
	 * @return array
	 */
	public function getCreateSettingsAction(): array
	{
		$result = [
			'permissions' => null,
			'security' => new CollabSecuritySettingsDto(),
		];
		$user = $this->getCurrentUser();

		if ($user)
		{
			$result['permissions'] = new CollabPermissionSettingsDto(
				$owner = new CollabSettingsUserDto(
					$user->getId(),
					$user->getFirstName(),
					$user->getLastName(),
					$user->getFullName(),
				),
				$moderators = [],
				$inviters = 'K',
				$messageWriters = 'K',
			);
		}

		return $result;
	}

	/**
	 * @restMethod mobile.Collab.getIsCollabNameExistsStatus
	 * @return array
	 */
	public function getIsCollabNameExistsStatusAction(string $name): array
	{
		return [
			'isExists' => \Bitrix\Socialnetwork\Provider\GroupProvider::getInstance()->isExistingGroup($name),
			'name' => $name,
		];
	}
}
