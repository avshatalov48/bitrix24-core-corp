<?php

namespace Bitrix\HumanResources\Service;

use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item\Collection\UserCollection;
use Bitrix\HumanResources\Item\Collection\NodeMemberCollection;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Item\User;
use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CSite;
use CUser;

final class UserService implements Contract\Service\UserService
{
	private Contract\Repository\UserRepository $userRepository;
	private Contract\Repository\NodeMemberRepository $nodeMemberRepository;
	private Contract\Util\CacheManager $cacheManager;

	public const USER_DEPARTMENT_EXISTS_KEY = 'user/department/exists/%d';

	public function __construct()
	{
		$this->userRepository = Container::getUserRepository();
		$this->nodeMemberRepository = Container::getNodeMemberRepository();
		$this->cacheManager = Container::getCacheManager()->setTtl(86400);
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
			true,
			false,
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
			true,
		);

		if ($fileTmp !== null && isset($fileTmp['src']))
		{
			return $fileTmp['src'];
		}

		switch ($user->personalGender)
		{
			case "M":
				$suffix = "male";
				break;
			case "F":
				$suffix = "female";
				break;
			default:
				$suffix = "unknown";
		}

		return Option::get('socialnetwork', 'default_user_picture_' . $suffix, false, SITE_ID);
	}

	public function getUserUrl(User $user): string
	{
		return str_replace(
			'#USER_ID#',
			$user->id,
			\COption::GetOptionString('intranet', 'path_user', '/company/personal/user/#USER_ID#/'),
		);
	}

	/**
	 * Check if user have relation with department node
	 * @param int $userId
	 *
	 * @return bool
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function isEmployee(int $userId): bool
	{
		$cacheKey = sprintf(self::USER_DEPARTMENT_EXISTS_KEY, $userId);

		$cacheValue = $this->cacheManager->getData($cacheKey);

		if (is_array($cacheValue) && array_key_exists('exists', $cacheValue))
		{
			return $cacheValue['exists'];
		}

		$exists = $this->nodeMemberRepository->findFirstByEntityIdAndEntityTypeAndNodeTypeAndActive(
				$userId,
				MemberEntityType::USER,
				NodeEntityType::DEPARTMENT,
		) !== null;

		$this->cacheManager->setData($cacheKey, ['exists' => $exists]);

		return $exists;
	}

	/**
	 * @param array<int> $userIds
	 *
	 * @return array<int>
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function filterEmployees(array $userIds): array
	{
		$employees = [];

		foreach ($userIds as $userId)
		{
			if ($this->isEmployee($userId))
			{
				$employees[] = $userId;
			}
		}

		return $employees;
	}

	/**
	 * Returns an array of basic user information
	 * @param User $user
	 *
	 * @return array {
	 *     id: int,
	 *     name: string,
	 *     avatar: ?string,
	 *     url: string,
	 *     workPosition: ?string,
	 * }
	 */
	public function getBaseInformation(User $user): array
	{
		return [
			'id' => $user->id,
			'name' => $this->getUserName($user),
			'avatar' => $this->getUserAvatar($user, 45),
			'url' => $this->getUserUrl($user),
			'workPosition' => $user->workPosition,
			'gender' => $user->personalGender,
			'isInvited' => $this->isUserInvited($user),
		];
	}

	public function findByNodeAndSearchQuery(Node $node, string $searchQuery): array
	{
		$userCollection = $this->userRepository->findByNodeAndSearchQuery($node, $searchQuery);

		$result = [];

		foreach ($userCollection as $user)
		{
			$result[] = $this->getBaseInformation($user);
		}

		return $result;
	}

	/**
	 * @param User $user
	 *
	 * @return bool
	 */
	public function isUserInvited(User $user): bool
	{
		return $user->active && $user->hasConfirmCode;
	}
}