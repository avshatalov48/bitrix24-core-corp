<?php
declare(strict_types=1);

namespace Bitrix\Intranet\Public\Facade\Invitation;

use Bitrix\Intranet\Entity\Collection\UserCollection;
use Bitrix\Intranet\Public\Type\BaseInvitation;
use Bitrix\Intranet\Public\Type\Collection\InvitationCollection;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Public\Service\InvitationService;
use Bitrix\Main\ArgumentException;

abstract class InvitationFacade
{
	protected InvitationService $invitationService;

	public function __construct(InvitationService $invitationService)
	{
		$this->invitationService = $invitationService;
	}

	/**
	 * @throws \Exception
	 */
	public function invite(BaseInvitation $invitation): User
	{
		return $this->invitationService->invite($invitation);
	}

	/**
	 * @throws ArgumentException
	 * @throws \Exception
	 */
	public function inviteByCollection(InvitationCollection $collection): UserCollection
	{
		return $this->invitationService->inviteByCollection($collection);
	}
}