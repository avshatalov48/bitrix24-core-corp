<?php
declare(strict_types=1);

namespace Bitrix\Intranet\Public\Facade\Invitation;

use Bitrix\Intranet\Entity\Collection\UserCollection;
use Bitrix\Intranet\Public\Type\BaseInvitation;
use Bitrix\Intranet\Public\Type\Collection\InvitationCollection;
use Bitrix\Intranet\Public\Type\EmailInvitation;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Enum\InvitationType;
use Bitrix\Intranet\Internal\Util\TransferUser;
use Bitrix\Intranet\Repository\UserRepository;
use Bitrix\Intranet\Public\Service\InvitationService;
use Bitrix\Intranet\Public\Service\RegistrationService;
use Bitrix\Intranet\Internal\Factory\Message\ExtranetInvitationMessageFactory;
use Bitrix\Intranet\Internal\Strategy\Registration\ExtranetRegistrationStrategy;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;

class ExtranetInvitationFacade extends InvitationFacade
{
	/**
	 * @throws \Exception
	 */
	public function __construct(
		private readonly array $groupIds = [],
	)
	{
		parent::__construct(new InvitationService(
			new UserRepository(),
			RegistrationService::createForExtranet(),
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

		$transport = new ExtranetInvitationMessageFactory($user);

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
		foreach ($userCollection as $user)
		{
			$groupCodes = array_map(fn($code) => "SG{$code}", $this->groupIds);
			if (!empty($groupCodes))
			{
				\CIntranetInviteDialog::RequestToSonetGroups(
					$user->getId(),
					$groupCodes,
					"",
					true,
				);
			}
		}
	}

	public function transfer(User $user): void
	{
		(new TransferUser(new UserRepository()))->transfer($user, false);
	}
}