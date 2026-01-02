<?php
declare(strict_types=1);

namespace Bitrix\Intranet\Public\Facade\Invitation;

use Bitrix\Extranet\Service\ServiceContainer;
use Bitrix\Intranet\Contract\Strategy\InvitationMessageFactoryContract;
use Bitrix\Intranet\Entity\Collection\UserCollection;
use Bitrix\Intranet\Public\Type\BaseInvitation;
use Bitrix\Intranet\Public\Type\Collection\InvitationCollection;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Enum\InvitationType;
use Bitrix\Intranet\Integration\HumanResources\HrUserService;
use Bitrix\Intranet\Repository\HrDepartmentRepository;
use Bitrix\Intranet\Repository\UserRepository;
use Bitrix\Intranet\Public\Service\InvitationService;
use Bitrix\Intranet\Public\Service\RegistrationService;
use Bitrix\Intranet\Internal\Factory\Message\CollabInvitationMessageFactory;
use Bitrix\Intranet\Internal\Factory\Message\ExtranetInvitationMessageFactory;
use Bitrix\Intranet\Internal\Factory\Message\IntranetInvitationMessageFactory;
use Bitrix\Intranet\Internal\Strategy\Registration\ReInviteRegistrationStrategy;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Collab\Collab;

class ReInvitationFacade extends InvitationFacade
{
	/**
	 * @throws ArgumentException
	 */
	public function __construct()
	{
		parent::__construct(new InvitationService(
			new UserRepository(),
			new RegistrationService(
				new ReInviteRegistrationStrategy(),
			),
		));

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
		$messageFactory = $this->createMessageFactory($user);

		if ($invitationType === InvitationType::EMAIL)
		{
			$messageFactory->createEmailEvent()->sendImmediately();
		}
		elseif ($invitationType === InvitationType::PHONE)
		{
			$messageFactory->createSmsEvent()->sendImmediately();
		}
	}

	private function getFirstUserCollab(User $user): ?Collab
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return null;
		}

		$provider = \Bitrix\Socialnetwork\Collab\Provider\CollabProvider::getInstance();
		$filter = \Bitrix\Main\ORM\Query\Query::filter()
			->where('MEMBERS.ROLE', \Bitrix\Socialnetwork\Collab\Permission\UserRole::REQUEST)
			->where('MEMBERS.USER_ID', $user->getId());
		$query = (new \Bitrix\Socialnetwork\Collab\Provider\CollabQuery($user->getId()))
			->setWhere($filter)
			->setSelect(['ID', 'NAME']);

		return $provider->getList($query)->getFirst();
	}

	private function createMessageFactory(User $user): InvitationMessageFactoryContract
	{
		$isIntranet = (new HrUserService())->isEmployee($user);
		if ($isIntranet)
		{
			$departmentCollection = (new HrDepartmentRepository())->findAllByUserId($user->getId());
			return new IntranetInvitationMessageFactory(
				$user,
				$departmentCollection,
			);
		}
		elseif (
			Loader::includeModule('extranet')
			&& ServiceContainer::getInstance()->getCollaberService()->isCollaberById($user->getId())
			&& $collab = $this->getFirstUserCollab($user)
		)
		{
			return new CollabInvitationMessageFactory($user, $collab);
		}
		else
		{
			return new ExtranetInvitationMessageFactory($user);
		}
	}
}