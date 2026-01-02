<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Repository\User\Profile;

use Bitrix\Intranet\Internal\Entity\User\Profile\SocialMediaCollection;
use Bitrix\Intranet\Internal\Repository\Mapper\User\Profile\SocialMediaMapper;
use CUserTypeManager;

class SocialMediaRepository
{
	private const USER_FIELD_ENTITY_ID = 'USER';

	public function __construct(
		private readonly CUserTypeManager $userFieldManager,
		private readonly SocialMediaMapper $mapper,
	)
	{
	}

	public static function createByDefault(): self
	{
		return new self(
			$GLOBALS['USER_FIELD_MANAGER'],
			new SocialMediaMapper(),
		);
	}

	public function getAllByUserId(int $userId): SocialMediaCollection
	{
		$userFieldSocialMediaTypeMap = $this->mapper->getUserFieldSocialMediaTypeMap();

		$userFields = $this->userFieldManager->getUserFields(
			self::USER_FIELD_ENTITY_ID,
			$userId,
			LANGUAGE_ID,
			null,
			array_keys($userFieldSocialMediaTypeMap),
		);

		$socialMediaCollection = new SocialMediaCollection();

		foreach ($userFieldSocialMediaTypeMap as $userFieldName => $socialMediaType)
		{
			if (array_key_exists($userFieldName, $userFields))
			{
				$socialMedia = $this->mapper->createSocialMediaFromUserField($userFields[$userFieldName]);

				if (isset($socialMedia))
				{
					$socialMediaCollection->add($socialMedia);
				}
			}
		}

		return $socialMediaCollection;
	}
}
