<?php

namespace Bitrix\Intranet\Service;

use Bitrix\Intranet\Contract\Repository\DepartmentRepository;
use Bitrix\Intranet\Contract\Repository\InvitationLinkRepository;
use Bitrix\Intranet\Repository\InvitationRepository;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectNotFoundException;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Bitrix\Intranet\Contract\Repository\UserRepository as UserRepositoryContract;

class ServiceContainer implements ContainerInterface
{
	private static ServiceContainer $instance;
	private ServiceLocator $serviceLocator;
	private string $prefix;

	private function __construct()
	{
		$this->serviceLocator = ServiceLocator::getInstance();
		$this->prefix = 'intranet.';
	}

	private function __clone()
	{}

	public static function getInstance(): self
	{
		if (!isset(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function has(string $id): bool
	{
		return $this->serviceLocator->has($this->prefix.$id);
	}

	/**
	 * @throws NotFoundExceptionInterface
	 * @throws ObjectNotFoundException
	 */
	public function get(string $id): mixed
	{
		return $this->serviceLocator->get($this->prefix.$id);
	}

	/**
	 * @throws NotFoundExceptionInterface
	 * @throws LoaderException
	 * @throws ObjectNotFoundException
	 */
	public function departmentRepository(): DepartmentRepository
	{
		return $this->get('repository.department');
	}

	public function invitationRepository(): InvitationRepository
	{
		return $this->get('repository.invitation');
	}

	public function invitationLinkRepository(): InvitationLinkRepository
	{
		return $this->get('repository.invitation.link');
	}

	public function registrationService(): RegistrationService
	{
		return $this->get('service.registration');
	}

	public function userRepository(): UserRepositoryContract
	{
		return $this->get('repository.user');
	}

	public function inviteService(): InviteService
	{
		return $this->get('service.invitation');
	}

	public function inviteStatusService(): InviteStatusService
	{
		return $this->get('service.invite.status');
	}

	public function inviteTokenService(): InviteTokenService
	{
		return $this->get('service.invitation.token');
	}

	public function getUserService(): UserService
	{
		return $this->get('service.user');
	}
}
