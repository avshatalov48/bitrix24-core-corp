<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Adapter;

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Permission\Model;
use Bitrix\Tasks\V2\Internal\Entity;

class GroupModelAdapter implements EntityModelAdapterInterface
{
	public function __construct(
		private readonly Entity\EntityInterface $entity
	)
	{
	}

	public function transform(): ?Model\GroupModel
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return null;
		}

		if (!$this->entity instanceof Entity\Group)
		{
			return null;
		}

		$data['id'] = (int)$this->entity->getId();

		return Model\GroupModel::createFromArray($data);
	}

	public function create(): ?Model\GroupModel
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return null;
		}

		return Model\GroupModel::createFromId($this->entity->getId());
	}
}