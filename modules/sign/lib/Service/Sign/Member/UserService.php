<?php

namespace Bitrix\Sign\Service\Sign\Member;

use Bitrix\Sign\File;
use Bitrix\Sign\Item;
use Bitrix\Sign\Repository;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Type;
use Bitrix\Sign\Service\Container;

use Bitrix\Main;

final class UserService
{
	private const AVATAR_SIZE = [
		'height' => 64,
		'width' => 64,
	];

	public function __construct(
		private ?Repository\MemberRepository $memberRepository = null,
		private ?Repository\DocumentRepository $documentRepository = null,
		private ?MemberService $memberService = null,
	)
	{
		$this->memberRepository ??= Container::instance()->getMemberRepository();
		$this->documentRepository ??= Container::instance()->getDocumentRepository();
		$this->memberService ??= Container::instance()->getMemberService();
	}

	public function getAvatarByMemberUid(string $uid): ?File
	{
		$member = $this->memberRepository->getByUid($uid);
		if ($member === null || $member->documentId === null)
		{
			return null;
		}

		$document = $this->documentRepository->getById($member->documentId);
		if ($document === null || !Type\DocumentScenario::isB2EScenario($document->scenario))
		{
			return null;
		}

		$userId = $this->memberService->getUserIdForMember($member);

		$model = Main\UserTable::query()
			->addSelect('PERSONAL_PHOTO')
			->addFilter('ID', $userId)
			->fetchObject()
		;

		if ($model === null)
		{
			return null;
		}

		$fileId = $model->getPersonalPhoto();
		if ((int) $fileId <= 0)
		{
			return null;
		}

		$file = new File($fileId);
		if (!$file->isExist() || !$file->isImage())
		{
			return null;
		}

		$file->resizeProportional(self::AVATAR_SIZE);
		return $file;
	}

	/**
	 * @param string[] $roles
	 */
	public function getMemberForSigning(int $userId, array $roles = []): ?Item\Member
	{
		$filter = \Bitrix\Main\ORM\Query\Query::filter();
		$filter->whereIn('SIGNED', Type\MemberStatus::getReadyForSigning());
		$roleValues = array_map(fn (string $role) => $this->memberRepository->convertRoleToInt($role), $roles);
		if (count($roles) > 0)
		{
			$filter->whereIn('ROLE', $roleValues);
		}

		// prefer newest
		$members = $this->memberRepository
			->listWithFilter($filter)
			->sort(static function (Item\Member $member1, Item\Member $member2) {
				return $member2->id <=> $member1->id;
			})
		;

		// get first for user
		foreach ($members as $member)
		{
			$userIdForMember = $this->memberService->getUserIdForMember($member);
			if ($userIdForMember === $userId)
			{
				return $member;
			}
		}

		return null;
	}

	public function checkAccessToMember(Item\Member $member, int $userId): bool
	{
		$userIdForMember = Container::instance()
			->getMemberService()
			->getUserIdForMember($member)
		;

		return $userIdForMember && $userIdForMember === $userId;
	}
}
