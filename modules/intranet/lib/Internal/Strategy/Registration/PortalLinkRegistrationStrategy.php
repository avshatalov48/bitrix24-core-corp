<?php

namespace Bitrix\Intranet\Internal\Strategy\Registration;

use Bitrix\Intranet\Contract\Repository\UserRepository;
use Bitrix\Intranet\Contract\Strategy\RegistrationStrategy;
use Bitrix\Intranet\Entity\User;
use Bitrix\Main\Context;
use Bitrix\Main\SystemException;

class PortalLinkRegistrationStrategy implements RegistrationStrategy
{
	public function __construct(
		private UserRepository $userRepository,
	)
	{

	}

	/**
	 * @throws SystemException
	 */
	public function register(User $user): User
	{
		$request = Context::getCurrent()->getRequest();
		global $USER;

		if (!isset($USER) || !($USER instanceof \CUser))
		{
			throw new SystemException('Global object USER is not initialized');
		}

		$resultMessage = $USER->Register(
			$user->getLogin(),
			$user->getName(),
			$user->getLastName(),
			$user->getPassword(),
			$request->getPost('USER_CONFIRM_PASSWORD'),
			$user->getEmail(),
			$user->getLid(),
			$request->getPost('captcha_word'),
			$request->getPost('captcha_sid'),
			false,
			$user->getAuthPhoneNumber(),
		);

		if (($resultMessage['TYPE'] ?? '') !== 'OK' || (int)$resultMessage['ID'] <= 0)
		{
			throw new SystemException($resultMessage['MESSAGE']);
		}

		return $this->userRepository->getUserById((int)$resultMessage['ID']);
	}
}