<?php

namespace Bitrix\Intranet\Infrastructure\Controller\ActionFilter;

use Bitrix\Intranet\Contract\Repository\UserRepository;
use Bitrix\Intranet\Public\Type\Collection\InvitationCollection;
use Bitrix\Intranet\Internal\Integration\Socialnetwork\ExternalAuthType;
use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

class ActiveUserInvitation extends Engine\ActionFilter\Base
{
	public function __construct(
		private UserRepository $userRepository,
	)
	{
		parent::__construct();
	}

	final public function onBeforeAction(Event $event)
	{
		Loc::loadLanguageFile($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/intranet/lib/invitation/register.php');

		/** @var \Bitrix\Main\Engine\Action $action */
		$action = $event->getParameter('action');
		$arguments = $action->getArguments();
		/** @var \Bitrix\Intranet\Public\Type\Collection\InvitationCollection $invitationCollection */
		$invitationCollection = $this->extractInvitationCollection($arguments);

		if (!$invitationCollection)
		{
			return null;
		}
		$emailInvitation = $invitationCollection->filter(fn ($invitation) => $invitation instanceof \Bitrix\Intranet\Public\Type\EmailInvitation);
		$phoneInvitation = $invitationCollection->filter(fn ($invitation) => $invitation instanceof \Bitrix\Intranet\Public\Type\PhoneInvitation);
		$emailLogins = $emailInvitation->map(fn($invitation) => $invitation->getEmail());
		$phoneLogins = $phoneInvitation->map(fn($invitation) => $invitation->getPhone());

		$emailUserCollection = $this->userRepository->findActivatedUsersByLogins(
			$emailLogins,
			(new ExternalAuthType())->getAllTypeList(),
		);

		if (!$emailUserCollection->empty())
		{
			$this->addError(new Error(Loc::getMessage("INTRANET_INVITATION_USER_EXIST_ERROR", [
				"#EMAIL_LIST#" => implode(', ', $emailUserCollection->map(fn($user) => $user->getLogin())),
			])));
		}

		$phoneUserCollection = $this->userRepository->findActivatedUsersByLogins(
			$phoneLogins,
			(new ExternalAuthType())->getAllTypeList(),
		);

		if (!$phoneUserCollection->empty())
		{
			$this->addError(new Error(Loc::getMessage("INTRANET_INVITATION_USER_PHONE_EXIST_ERROR", [
				"#PHONE_LIST#" => implode(', ', $phoneUserCollection->map(fn($user) => $user->getLogin())),
			])));
		}

		if ($this->errorCollection->isEmpty())
		{
			return null;
		}

		return new EventResult(EventResult::ERROR, null, null, $this);
	}

	private function extractInvitationCollection(array $arguments): ?InvitationCollection
	{
		foreach ($arguments as $argument)
		{
			if ($argument instanceof InvitationCollection)
			{
				return $argument;
			}
		}

		return null;
	}
}