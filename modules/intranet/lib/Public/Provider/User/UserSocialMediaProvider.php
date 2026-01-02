<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Public\Provider\User;

use Bitrix\Intranet\Internal\Entity\User\Profile\SocialMediaCollection;
use Bitrix\Intranet\Internal\Repository\User\Profile\SocialMediaRepository;

class UserSocialMediaProvider
{
	public function __construct(
		private readonly SocialMediaRepository $socialMediaRepository,
	)
	{
	}

	public static function createByDefault(): self
	{
		return new self(
			SocialMediaRepository::createByDefault(),
		);
	}

	public function getListByUserId(int $userId): SocialMediaCollection
	{
		return $this->socialMediaRepository->getAllByUserId($userId);
	}
}
