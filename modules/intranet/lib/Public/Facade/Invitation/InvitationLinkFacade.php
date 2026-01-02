<?php

namespace Bitrix\Intranet\Public\Facade\Invitation;

use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Internal\Repository\Mapper\UserMapper;
use Bitrix\Intranet\Internal\Strategy\Registration\PortalLinkRegistrationStrategy;
use Bitrix\Intranet\Public\Service\RegistrationService;
use Bitrix\Intranet\Repository\UserRepository;
use Bitrix\Main\Application;
use Bitrix\Main\SystemException;
use Bitrix\Socialservices\Network;

abstract class InvitationLinkFacade
{
	private RegistrationService $registrationService;

	public function __construct(
		protected mixed $payload,
	)
	{
		$this->registrationService = new RegistrationService(
			new PortalLinkRegistrationStrategy(
				new UserRepository()
			),
		);

		\Bitrix\Main\EventManager::getInstance()->addEventHandlerCompatible(
			'main',
			'OnBeforeUserRegister',
			[$this, 'onBeforeUserRegister'],
		);
	}

	public function getInvitingUserId(): int
	{
		return $this->payload->inviting_user_id;
	}

	abstract public function isActual(): bool;

	abstract public function checkAccess(): void;

	abstract protected function afterRegister(User $user): User;

	public function register(User $user): User
	{
		$connection = Application::getConnection();
		try
		{
			$connection->startTransaction();

			$user = $this->registrationService->register($user);
			$user = $this->afterRegister($user);

			foreach (GetModuleEvents("main", "OnUserInitialize", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, [$user->getId(), (new UserMapper())->convertToArray($user)]);
			}

			$connection->commitTransaction();
			$this->afterCommitTransaction($user);

			return $user;
		}
		catch (SystemException $e)
		{
			$message = $e->getMessage();
			Network::setLastUserStatus(!empty($message)
				? [
					"error" => "no_email",
					"error_message" => $message,
				]
				: "no_email",
			);

			throw $e;
		}
		catch (\Exception $e)
		{
			$connection->rollbackTransaction();
			throw $e;
		}
	}

	public function onBeforeUserRegister(array &$data): void
	{
	}

	public function processAuthUser(User $user): void
	{
	}

	protected function afterCommitTransaction(User $user): void
	{
	}
}