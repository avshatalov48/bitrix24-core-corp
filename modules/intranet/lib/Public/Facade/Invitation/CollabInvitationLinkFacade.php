<?php

namespace Bitrix\Intranet\Public\Facade\Invitation;

use Bitrix\Extranet\Service\ServiceContainer;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Enum\LinkEntityType;
use Bitrix\Intranet\Infrastructure\InvitationLinkValidator;
use Bitrix\Intranet\Integration\Socialnetwork\Group\MemberServiceFacade;
use Bitrix\Main\AccessDeniedException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\SystemException;
use Psr\Container\NotFoundExceptionInterface;

class CollabInvitationLinkFacade extends InvitationLinkFacade
{
	private MemberServiceFacade $memberServiceFacade;

	/**
	 * @throws ArgumentNullException
	 * @throws SystemException
	 */
	public function __construct(mixed $payload)
	{
		parent::__construct($payload);

		$this->memberServiceFacade = new MemberServiceFacade(
			groupId: $this->getCollabId(),
			userId: $this->getInvitingUserId(),
		);
	}

	public function isActual(): bool
	{
		return (new InvitationLinkValidator($this->payload->collab_id, LinkEntityType::COLLAB))
			->validate($this->payload->link_code);
	}

	public function getCollabName()
	{
		return $this->payload->collab_name;
	}

	public function getCollabId()
	{
		return $this->payload->collab_id;
	}

	/**
	 * @throws AccessDeniedException
	 */
	public function checkAccess(): void
	{
		if (!$this->isActual())
		{
			throw new AccessDeniedException('The invitation link is invalid or expired.');
		}
	}

	protected function afterRegister(User $user): User
	{
		$this->memberServiceFacade->addUser($user);

		return $user;
	}

	public function onBeforeUserRegister(array &$data): void
	{
		$data['SITE_ID'] = \CExtranet::GetExtranetSiteID();
		$data['GROUP_ID'] = [(int)\CExtranet::GetExtranetUserGroupID()];
	}

	public function processAuthUser(User $user): void
	{
		$this->memberServiceFacade->addUser($user);
	}

	protected function afterCommitTransaction(User $user): void
	{
		if (Loader::includeModule('extranet'))
		{
			ServiceContainer::getInstance()->getCollaberService()->clearCache();
		}
	}
}