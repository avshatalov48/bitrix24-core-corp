<?php

namespace Bitrix\Intranet\Public\Service;

use Bitrix\HumanResources\Compatibility\Utils\DepartmentBackwardAccessCode;
use Bitrix\Intranet\Contract\Strategy\RegistrationStrategy;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Entity\Collection\InvitationCollection;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Enum\InvitationType;
use Bitrix\Intranet\Exception\CreationFailedException;
use Bitrix\Intranet\Internal\Repository\Mapper\UserMapper;
use Bitrix\Intranet\Internal\Strategy\Registration\CollabRegistrationStrategy;
use Bitrix\Intranet\Internal\Strategy\Registration\ExtranetRegistrationStrategy;
use Bitrix\Intranet\Internal\Strategy\Registration\IntranetRegistrationStrategy;
use Bitrix\Intranet\Repository\HrDepartmentRepository;
use Bitrix\Intranet\Repository\UserRepository;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Event;
use Bitrix\Main\Security\Random;

class RegistrationService
{
	public function __construct(
		private readonly RegistrationStrategy $registrationStrategy,
	)
	{}

	public static function createByIntranet(): static
	{
		return new static(
			new IntranetRegistrationStrategy(
				new UserRepository(),
			)
		);
	}

	/**
	 * @throws \Exception
	 */
	public static function createForCollab(): static
	{
		return new static(
			new CollabRegistrationStrategy(
				new UserRepository(),
			)
		);
	}

	/**
	 * @throws \Exception
	 */
	public static function createForExtranet(): static
	{
		return new static(
			new ExtranetRegistrationStrategy(
				new UserRepository(),
			)
		);
	}

	public function register(User $user): User
	{
		$user = $this->registrationStrategy->register($user);

		$event = new Event(
			'intranet',
			'onAfterUserRegistration',
			[
				'user' => $user,
			],
		);
		$event->send();

		$this->runBackwardEvent($user);

		return $user;
	}

	/**
	 * @throws ArgumentException
	 */
	private function runBackwardEvent(User $user): void
	{
		$userFields = (new UserMapper())->convertToArray($user);
		$userDepartments = (new HrDepartmentRepository())->findAllByUserId($user->getId());
		$userFields['UF_DEPARTMENTS'] = $userDepartments->map(
			fn($department) => DepartmentBackwardAccessCode::extractIdFromCode($department->getAccessCode())
		);

		foreach (GetModuleEvents('intranet', 'OnRegisterUser', true) as $event)
		{
			ExecuteModuleEventEx($event, [$userFields]);
		}
	}
}