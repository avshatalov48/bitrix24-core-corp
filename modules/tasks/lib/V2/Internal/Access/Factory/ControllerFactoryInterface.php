<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Factory;

use Bitrix\Main\Access\AccessibleController;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Tasks\V2\Internal\Access\Adapter\EntityModelAdapterInterface;
use Bitrix\Tasks\V2\Internal\Entity\EntityInterface;

interface ControllerFactoryInterface
{
	public function create(Type $type, int $userId): ?AccessibleController;
	public function createByClass(string $class, int $userId): ?AccessibleController;

	public function createAdapter(EntityInterface $entity): ?EntityModelAdapterInterface;

	public function createModel(Type $type): AccessibleItem;
}
