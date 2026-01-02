<?php

namespace Bitrix\Intranet\Internal\Util;

use Bitrix\Crm\Order\BuyerGroup;
use Bitrix\Intranet\Contract\Repository\UserRepository;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Internals\InvitationTable;
use Bitrix\Intranet\Invitation;
use Bitrix\Intranet\Internal\Repository\Mapper\UserMapper;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Security\Random;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;

class TransferUser
{
	private UserMapper $userMapper;

	public function __construct(
		private UserRepository $userRepository
	)
	{
		$this->userMapper = new UserMapper();
	}

	/**
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function transfer(User $user, $isIntranet = true): ?User
	{
		if ($user->getExternalAuthId() === "shop" && Loader::includeModule("crm"))
		{
			$groupIds = array_merge($user->getGroupIds(), [BuyerGroup::getSystemGroupId()]);
			$user->setGroupIds($groupIds);
		}

		if (empty($user->getGroupIds()))
		{
			$user->setGroupIds($isIntranet
				? \CIntranetInviteDialog::getUserGroups(SITE_ID)
				: (\CExtranet::GetExtranetUserGroupID() !== false ? [\CExtranet::GetExtranetUserGroupID()] : [])
			);
		}

		$user->setConfirmCode(Random::getString(8));
		$user->setExternalAuthId('');

		$user->setPassword(\CUser::GeneratePasswordByPolicy($user->getGroupIds()));

		$userFields = $this->userMapper->convertToArray($user);

		foreach(GetModuleEvents("intranet", "OnTransferEMailUser", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$userFields)) === false)
			{
				return null;
			}
		}

		$user = $this->userMapper->convertFromArray($userFields);

		$this->userRepository->update($user);

		foreach(GetModuleEvents("intranet", "OnAfterTransferEMailUser", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, [$this->userMapper->convertToArray($user)]);
		}

		$event = new Event(
			'intranet',
			'onAfterUserRegistration',
			[
				'user' => $user,
			],
		);
		$event->send();

		foreach(GetModuleEvents("intranet", "OnRegisterUser", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, [$this->userMapper->convertToArray($user)]);
		}

		$res = InvitationTable::getList([
			'filter' => [
				'USER_ID' => $user->getId(),
			],
			'select' => [ 'ID' ]
		]);
		while ($invitationFields = $res->fetch())
		{
			InvitationTable::update($invitationFields['ID'], [
				'TYPE' => Invitation::TYPE_EMAIL,
				'ORIGINATOR_ID' => CurrentUser::get()?->getId(),
				'DATE_CREATE' => new DateTime()
			]);
		}

		return $user;
	}
}