<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Public\Provider\User;

use Bitrix\Intranet\Component\UserProfile\Grats;
use Bitrix\Intranet\Internal\Entity\User\Profile\BaseInfoCollection;
use Bitrix\Intranet\Internal\Entity\User\Profile\GratitudeCollection;
use Bitrix\Intranet\Internal\Entity\User\Profile\GratitudeBadgeCollection;
use Bitrix\Intranet\Internal\Repository\User\Profile\GratitudeRepository;
use Bitrix\Intranet\Internal\Repository\User\Profile\ProfileRepository;
use Bitrix\Main\Provider\Params\GridParams;

class UserGratitudeProvider
{
	public function __construct(
		private GratitudeRepository $gratitudeRepository,
		private ProfileRepository $profileRepository,
	)
	{}

	public static function createByDefault(): UserGratitudeProvider
	{
		return new UserGratitudeProvider(
			new GratitudeRepository(),
			ProfileRepository::createByDefault(),
		);
	}

	public function getList(
		int $userId,
		GridParams $gridParams
	): GratitudeCollection
	{
		$limit = $gridParams->getLimit();
		$offset = $gridParams->getOffset();

		if ($limit <= 0 || $offset < 0)
		{
			return new GratitudeCollection();
		}

		$userGrats = new Grats([
			'profileId' => $userId,
			'pageSize' => $limit,
		]);

		$page = (int)floor($offset / $limit) + 1;
		$userGratsData = $userGrats->getGratitudePostListAction([
			'pageSize' => $limit,
			'pageNum' => $page,
		]);
		$authorCollection = new BaseInfoCollection();

		foreach ($userGratsData['AUTHORS'] as $author)
		{
			$authorCollection->add(
				$this->profileRepository->getUserBaseInfoByUserData($author),
			);
		}

		return $this->gratitudeRepository->createGratitudeCollectionFromGratsPostsDataAndAuthorCollection(
			$userGratsData['POSTS'],
			$authorCollection,
		);
	}

	public function getProfileBadges(int $userId): GratitudeBadgeCollection
	{
		$userGrats = new Grats([
			'profileId' => $userId,
		]);

		return $this->gratitudeRepository->createGratitudeBadgesFromGratsBadgesData(
			$userGrats->getGratitudes()['BADGES'],
		);
	}
}
