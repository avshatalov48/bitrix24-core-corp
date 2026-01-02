<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;

use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Service\PhotoService;

class GroupMapper
{
	public function __construct(
		private readonly PhotoService $photoService
	)
	{

	}

	public function mapToUserCollection(array $members): Entity\UserCollection
	{
		$users = [];
		foreach ($members as $userId => $role)
		{
			$users[] = new Entity\User(id: (int)$userId, role: $role);
		}

		return new Entity\UserCollection(...$users);
	}

	public function mapToEntity(
		Workgroup $workgroup,
		?Entity\File $image = null,
	): Entity\Group
	{
		$image = $image ? ($this->photoService->resize($image) ?? $image) : null;

		return new Entity\Group(
			id: $workgroup->getId(),
			name: $workgroup->getName(),
			image: $image,
			type: $workgroup->getType()?->value,
			isVisible: $workgroup->isVisible(),
		);
	}
}
