<?php

namespace Bitrix\HumanResources\Service;

use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item\Collection\UserCollection;
use Bitrix\HumanResources\Item\Collection\NodeMemberCollection;
use Bitrix\HumanResources\Item\User;
use Bitrix\HumanResources\Contract;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CSite;
use CUser;

	final class UserService implements Contract\Service\UserService
	{
		private Contract\Repository\UserRepository $userRepository;

		public function __construct()
		{
			$this->userRepository = Container::getUserRepository();
		}

		public function getUserById(int $userId): ?User
		{
			return $this->userRepository->getById($userId);
		}

		/**
		 * @throws ArgumentException
		 * @throws WrongStructureItemException
		 * @throws ObjectPropertyException
		 * @throws SystemException
		 */
		public function getUserCollectionFromMemberCollection(NodeMemberCollection $nodeMemberCollection): UserCollection
		{
			return $this->userRepository->getUserCollectionByMemberCollection($nodeMemberCollection);
		}

		public function getUserName(User $user): string
		{
			return CUser::FormatName(
				CSite::GetNameFormat(false),
				[
					'LOGIN' => '',
					'NAME' => $user->firstName,
					'LAST_NAME' => $user->lastName,
					'SECOND_NAME' => $user->secondName,
				],
				true, false
			);
		}

		public function getUserAvatar(User $user, int $size = 25): ?string
		{
			$fileTmp = \CFile::ResizeImageGet(
				$user->personalPhotoId,
				['width' => $size, 'height' => $size],
				BX_RESIZE_IMAGE_EXACT,
				false,
				false,
				true
			);

			if ($fileTmp !== null && isset($fileTmp['src']))
			{
				return $fileTmp['src'];
			}

			return null;
		}
	}