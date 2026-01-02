<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;

use Bitrix\Tasks\V2\Internal\Entity\UserCollection;
use Bitrix\Tasks\V2\Internal\Service\NameService;
use Bitrix\Tasks\V2\Internal\Service\PhotoService;
use Bitrix\Tasks\V2\Internal\Entity;

class UserMapper
{
	public function __construct(
		private readonly PhotoService $photoService,
		private readonly NameService $nameService
	)
	{

	}

	public function mapToCollection(
		array $users,
		?Entity\FileCollection $files = null
	): Entity\UserCollection
	{
		$result = [];

		foreach ($users as $user)
		{
			$personalPhoto = $files?->findOneById((int)$user['PERSONAL_PHOTO']);
			$gender = $user['PERSONAL_GENDER'] ?? '';
			$email = is_string($user['EMAIL'] ?? null) ? $user['EMAIL'] : null;

			$result[] = new Entity\User(
				id: (int)$user['ID'],
				name: $this->nameService->format($user),
				image: $personalPhoto ? $this->photoService->resize($personalPhoto) : null,
				gender: Entity\User\Gender::tryFrom($gender) ?? Entity\User\Gender::Male,
				email: $email,
			);
		}

		return new UserCollection(...$result);
	}
}
