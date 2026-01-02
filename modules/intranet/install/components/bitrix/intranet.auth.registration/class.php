<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Internal\Factory\InvitationLinkFacadeFactory;
use Bitrix\Intranet\Repository\UserRepository;
use Bitrix\Main\AccessDeniedException;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\SystemException;
use Bitrix\Intranet\Public\Provider\Portal;

class AuthRegistrationComponent extends \CBitrixComponent
{
	public function executeComponent(): void
	{
		if (ModuleManager::isModuleInstalled('bitrix24'))
		{
			LocalRedirect('/');
		}
		$request = \Bitrix\Main\Context::getCurrent()->getRequest();

		if ($request->get('login') === 'yes')
		{
			LocalRedirect('/?login=yes');
		}

		try
		{
			$invitationLinkFacade = (new InvitationLinkFacadeFactory($request->get('invite_token') ?? ''))->create();
			$invitationLinkFacade->checkAccess();
			if (CurrentUser::get()->isAuthorized())
			{
				$user = (new UserRepository())->getUserById(CurrentUser::get()->getId());
				$invitationLinkFacade->processAuthUser($user);

				LocalRedirect('/');
			}

			if ($request->isPost() && $request->getPost('Register'))
			{
				$user = $invitationLinkFacade->register($this->createUser($request->getValues()));

				if ($user->getActive())
				{
					LocalRedirect('/');
				}
				else
				{
					$this->arResult['AUTH_RESULT'] = [
						'TYPE' => 'OK',
						'ID' => $user->getId(),
					];
				}
			}
		}
		catch (AccessDeniedException $exception)
		{
			LocalRedirect('/?login=yes');
		}
		catch (SystemException $exception)
		{
			$this->arResult['AUTH_RESULT'] = $exception->getMessage();
		}

		$this->arResult['HOST_NAME'] = (new Portal\DomainProvider())->getHostName();

		$this->includeComponentTemplate();
	}

	private function createUser($userData): User
	{
		return new User(
			login: $userData['USER_LOGIN'] ?? '',
			email: $userData['USER_EMAIL'] ?? '',
			name: $userData['USER_NAME'] ?? '',
			lastName: $userData['USER_LAST_NAME'] ?? '',
			authPhoneNumber: $userData['USER_PHONE_NUMBER'] ?? '',
			lid: SITE_ID,
			password: $userData['USER_PASSWORD'] ?? '',
		);
	}
}