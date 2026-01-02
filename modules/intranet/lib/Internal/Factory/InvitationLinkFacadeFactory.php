<?php
namespace Bitrix\Intranet\Internal\Factory;

use Bitrix\Intranet\Public\Facade\Invitation\CollabInvitationLinkFacade;
use Bitrix\Intranet\Public\Facade\Invitation\DepartmentInvitationLinkFacade;
use Bitrix\Intranet\Public\Facade\Invitation\InvitationLinkFacade;
use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Main\AccessDeniedException;
use Bitrix\Main\LoaderException;

class InvitationLinkFacadeFactory
{

	public function __construct(
		private readonly string $token,
	)
	{
	}

	/**
	 * @throws LoaderException
	 * @throws AccessDeniedException
	 */
	public function create(): InvitationLinkFacade
	{
		$payload = ServiceContainer::getInstance()->inviteTokenService()->parse($this->token);
		if (isset($payload->collab_id))
		{
			return new CollabInvitationLinkFacade($payload);
		}
		elseif (isset($payload->inviting_user_id) && isset($payload->departments_ids))
		{
			return new DepartmentInvitationLinkFacade($payload);
		}
		else
		{
			throw new AccessDeniedException('The invitation link is invalid or expired.');
		}
	}
}