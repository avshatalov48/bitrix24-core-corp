<?php

namespace Bitrix\Intranet\Infrastructure\Controller\ActionFilter;

use Bitrix\Intranet\Entity\Collection\DepartmentCollection;
use Bitrix\Intranet\Public\Type\Collection\InvitationCollection;
use Bitrix\Intranet\Internal\Integration\Socialnetwork\ExternalAuthType;
use Bitrix\Intranet\Integration\HumanResources\HrUserService;
use Bitrix\Intranet\Public\Type\EmailInvitation;
use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Intranet\Contract\Repository\UserRepository;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

class UserInvitedExtranet extends Engine\ActionFilter\Base
{
	public function __construct(
		private readonly UserRepository $userRepository,
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
		$invitationCollection = $this->extractInvitationCollection($arguments);
		$departmentCollection = $this->extractDepartmentCollection($arguments);
		if (!$invitationCollection)
		{
			return null;
		}

		$logins = $invitationCollection->map(function ($invitation) {
			if ($invitation instanceof EmailInvitation)
			{
				return $invitation->getEmail();
			}
			else
			{
				return $invitation->getPhone();
			}
		});

		if (empty($logins))
		{
			return null;
		}

		$invitedUserCollection = $this->userRepository->findInvitedUsersByLogins(
			$logins,
			(new ExternalAuthType())->getNotUserTypeList(),
		);
		$extranetUserCollection = (new HrUserService())->filterNotEmployees($invitedUserCollection);
		$inviteToIntranet = !$departmentCollection->empty();

		if ($inviteToIntranet && !$extranetUserCollection->empty())
		{
			$extranetUserLogins = $extranetUserCollection->map(fn($user) => $user->getLogin());

			$this->addError(new Error(Loc::getMessage("INTRANET_INVITATION_USER_EXIST_ERROR", [
				"#EMAIL_LIST#" => implode(', ', $extranetUserLogins),
			])));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
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

	private function extractDepartmentCollection(array $arguments): DepartmentCollection
	{
		foreach ($arguments as $argument)
		{
			if ($argument instanceof DepartmentCollection)
			{
				return $argument;
			}
		}

		return new DepartmentCollection();
	}
}