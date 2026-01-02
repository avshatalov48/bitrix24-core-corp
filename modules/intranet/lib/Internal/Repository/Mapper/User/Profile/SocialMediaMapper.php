<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Repository\Mapper\User\Profile;

use Bitrix\Intranet\Internal\Entity\User\Profile\SocialMedia;
use Bitrix\Intranet\Internal\Enum\User\Profile\SocialMediaType;

class SocialMediaMapper
{
	/** @var array<string, SocialMediaType> $userFieldSocialMediaTypeMap */
	private array $userFieldSocialMediaTypeMap;

	public function createSocialMediaFromUserField(array $userField): ?SocialMedia
	{
		if (!isset($userField['FIELD_NAME']))
		{
			return null;
		}

		$socialMediaType = $this->getUserFieldSocialMediaTypeMap()[$userField['FIELD_NAME']] ?? null;

		if ($socialMediaType instanceof SocialMediaType)
		{
			return new SocialMedia(
				id: (int)$userField['ID'],
				type: $socialMediaType,
				value: $userField['VALUE'] ?? '',
				title: $userField['EDIT_FORM_LABEL'] ?? $userField['FIELD_NAME'],
			);
		}

		return null;
	}

	public function getUserFieldSocialMediaTypeMap(): array
	{
		if (isset($this->userFieldSocialMediaTypeMap))
		{
			return $this->userFieldSocialMediaTypeMap;
		}

		$this->userFieldSocialMediaTypeMap = [];

		foreach (SocialMediaType::cases() as $socialMediaType)
		{
			$userFieldName = $this->getSocialMediaUserFieldName($socialMediaType);
			$this->userFieldSocialMediaTypeMap[$userFieldName] = $socialMediaType;
		}

		return $this->userFieldSocialMediaTypeMap;
	}

	private function getSocialMediaUserFieldName(SocialMediaType $socialMediaType): string
	{
		return 'UF_' . $socialMediaType->name;
	}
}
