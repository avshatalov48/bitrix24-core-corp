<?php

namespace Bitrix\Intranet\Infrastructure\Controller\ActionFilter;

use Bitrix\Intranet\Internal\Integration\Socialnetwork\ExternalAuthType;
use Bitrix\Intranet\Integration\HumanResources\HrUserService;
use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Intranet\Contract\Repository\UserRepository;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

class UserInvitedIntranet extends Engine\ActionFilter\Base
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
		$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
		$invitations = $request->getPost('invitations');
		$logins = [];
		foreach ($invitations as $invitation)
		{
			if (isset($invitation['EMAIL']))
			{
				$logins[] = $invitation['EMAIL'];
			}
			elseif (isset($invitation['PHONE']))
			{
				$logins[] = $invitation['PHONE'];
			}
		}

		if (empty($logins))
		{
			return null;
		}

		$invitedUserCollection = $this->userRepository->findInvitedUsersByLogins(
			$logins,
			(new ExternalAuthType())->getNotUserTypeList(),
		);
		$intranetUserCollection = (new HrUserService())->filterEmployees($invitedUserCollection);

		$inviteToExtranet = empty($request->getPost('departmentIds'));

		if ($inviteToExtranet && !$intranetUserCollection->empty())
		{
			$intranetUserLogins = $intranetUserCollection->map(fn($user) => $user->getLogin());

			$this->addError(new Error(Loc::getMessage("INTRANET_INVITATION_USER_EXIST_ERROR", [
				"#EMAIL_LIST#" => implode(', ', $intranetUserLogins),
			])));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}