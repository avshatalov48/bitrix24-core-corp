<?php
declare(strict_types=1);

namespace Bitrix\Intranet\Public\Facade\Invitation;

use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Public\Type\EmailInvitation;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Enum\InvitationType;
use Bitrix\Intranet\Internal\Util\TransferUser;
use Bitrix\Intranet\Integration\Socialnetwork\Group\MemberServiceFacade;
use Bitrix\Intranet\Repository\UserRepository;
use Bitrix\Intranet\Public\Service\InvitationService;
use Bitrix\Intranet\Public\Service\RegistrationService;
use Bitrix\Intranet\Internal\Factory\Message\CollabInvitationMessageFactory;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Socialnetwork\Collab\Collab;

class CollabInvitationFacade extends InvitationFacade
{
	/**
	 * @throws \Exception
	 */
	public function __construct(
		private readonly Collab $collab,
	)
	{
		parent::__construct(new InvitationService(
			new UserRepository(),
			RegistrationService::createForCollab(),
		));

		EventManager::getInstance()->addEventHandler(
			'intranet',
			'onBeforeInviteUser',
			function (Event $event) {
				$invitation = $event->getParameter('invitation');
				if (!($invitation instanceof EmailInvitation))
				{
					return;
				}
				$fields = $invitation->toArray();
				$userCollection = (new UserRepository)->findEmailOrShopUsersByLogins([$fields['LOGIN']]);
				if ($user = $userCollection->first())
				{
					$this->transfer($user);
				}
			},
		);

		EventManager::getInstance()->addEventHandler(
			'intranet',
			'onSendInviteUser',
			[$this, 'sendInvitation'],
		);

		EventManager::getInstance()->addEventHandler(
			'intranet',
			'onUserInvited',
			[$this, 'afterInviteUserAction'],
		);
	}

	public function sendInvitation(Event $event): void
	{
		$user = $event->getParameter('invitedUser');
		$invitationType = $event->getParameter('invitation')->getType();
		$isFirstInvitation = $event->getParameter('isFirstInvitation');

		$transport = new CollabInvitationMessageFactory($user, $this->collab);

		if ($invitationType === InvitationType::EMAIL)
		{
			$transport->createEmailEvent()->sendImmediately();
		}
		elseif ($invitationType === InvitationType::PHONE && !$isFirstInvitation)
		{
			$transport->createSmsEvent()->sendImmediately();
		}
	}

	public function afterInviteUserAction(Event $event): void
	{
		$userCollection = $event->getParameter('invitedUsers');
		$fromUser = CurrentUser::get()->getId() <= 0 ? 1 : (int)CurrentUser::get()->getId();
		(new MemberServiceFacade($this->collab->getId(), $fromUser))->inviteUserCollection($userCollection);
	}

	public function transfer(User $user): void
	{
		(new TransferUser(new UserRepository()))->transfer($user, false);
	}
}