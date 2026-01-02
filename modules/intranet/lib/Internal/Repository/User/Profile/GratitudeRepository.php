<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Repository\User\Profile;

use Bitrix\Intranet\Internal\Entity\User\Profile\BaseInfoCollection;
use Bitrix\Intranet\Internal\Entity\User\Profile\BaseInfo;
use Bitrix\Intranet\Internal\Entity\User\Profile\Gratitude;
use Bitrix\Intranet\Internal\Entity\User\Profile\GratitudeCollection;
use Bitrix\Intranet\Internal\Entity\User\Profile\GratitudeBadge;
use Bitrix\Intranet\Internal\Entity\User\Profile\GratitudeBadgeCollection;

class GratitudeRepository
{
	public function createGratitudeBadgesFromGratsBadgesData(array $gratsBadgesData): GratitudeBadgeCollection
	{
		$userProfileGratitudesCollection = new GratitudeBadgeCollection();

		foreach ($gratsBadgesData as $gratitudeTypeId => $gratitude)
		{
			$userProfileGratitudesCollection->add(
				new GratitudeBadge(
					gratitudeTypeId: $gratitudeTypeId,
					count: $gratitude['COUNT'],
					title: $gratitude['NAME'],
				)
			);
		}

		return $userProfileGratitudesCollection;
	}

	public function createGratitudeCollectionFromGratsPostsDataAndAuthorCollection(
		array $gratsPostsData,
		BaseInfoCollection $authorCollection,
	): GratitudeCollection
	{
		$userGratitudeCollection = new GratitudeCollection();

		foreach ($gratsPostsData as $postData)
		{
			$author = $authorCollection->findById((int)$postData['AUTHOR_ID']);

			if ($author instanceof BaseInfo)
			{
				$gratitude = new Gratitude(
					postId: (int)$postData['ID'],
					gratitudeTypeId: (int)$postData['BADGE_ID'],
					author: $author,
					title: $postData['TITLE'],
					dateTimeCreate: $postData['DATE_PUBLISH'],
				);
				$userGratitudeCollection->add($gratitude);
			}
		}

		return $userGratitudeCollection;
	}
}
