<?php
declare(strict_types=1);

namespace Bitrix\Intranet\Public\Facade\Invitation;

use Bitrix\Intranet\Entity\Collection\DepartmentCollection;
use Bitrix\Intranet\Public\Type\EmailInvitation;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Enum\InvitationType;
use Bitrix\Intranet\Internal\Util\TransferUser;
use Bitrix\Intranet\Integration\HumanResources\DepartmentAssigner;
use Bitrix\Intranet\Repository\UserRepository;
use Bitrix\Intranet\Public\Service\InvitationService;
use Bitrix\Intranet\Public\Service\RegistrationService;
use Bitrix\Intranet\Internal\Factory\Message\IntranetInvitationMessageFactory;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

class IntranetInvitationFacade extends InvitationFacade
{
	/**
	 * @throws ArgumentException
	 */
	public function __construct(
		private readonly DepartmentCollection $departmentCollection,
		private readonly array $groupIds = [],
	)
	{
		parent::__construct(new InvitationService(
			new UserRepository(),
			RegistrationService::createByIntranet(),
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

		EventManager::getInstance()->addEventHandlerCompatible(
			'main',
			'OnAfterUserAdd',
			[$this, 'onAfterUserAdd'],
			false,
			10,
		);

		EventManager::getInstance()->addEventHandler(
			'intranet',
			'onSendInviteUser',
			[$this, 'sendInvitation'],
		);
	}

	public function sendInvitation(Event $event): void
	{
		$user = $event->getParameter('invitedUser');
		$invitationType = $event->getParameter('invitation')->getType();
		$isFirstInvitation = $event->getParameter('isFirstInvitation');
		$transport = new IntranetInvitationMessageFactory(
			$user,
			$this->departmentCollection,
		);

		if ($invitationType === InvitationType::EMAIL)
		{
			$transport->createEmailEvent()->sendImmediately();
		}
		elseif ($invitationType === InvitationType::PHONE && !$isFirstInvitation)
		{
			$transport->createSmsEvent()->sendImmediately();
		}
	}

	public function onAfterUserAdd($fields): void
	{
		if (!is_array($fields) || empty($fields['ID']))
		{
			return;
		}
		$user = User::initByArray($fields);
		$departmentAssigner = new DepartmentAssigner($this->departmentCollection);
		$departmentAssigner->assignUser($user);

		$groupCodes = array_map(fn($code) => "SG{$code}", $this->groupIds);
		if (!empty($groupCodes))
		{
			\CIntranetInviteDialog::RequestToSonetGroups(
				$user->getId(),
				$groupCodes,
				"",
			);
		}
	}

	/**
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function transfer(User $user): void
	{
		(new TransferUser(new UserRepository()))->transfer($user);
	}
}