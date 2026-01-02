<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Link;

use Bitrix\Tasks\Internals\Trait\SingletonTrait;
use Bitrix\Tasks\Slider\Path\PathMaker;
use Bitrix\Tasks\Slider\Path\TaskPathMaker;
use Bitrix\Tasks\Slider\Path\TemplatePathMaker;

class LinkBuilderFactory
{
	use SingletonTrait;

	public function create(
		string $entityType,
		int $entityId = 0,
		int $ownerId = 0,
		string $context = PathMaker::PERSONAL_CONTEXT,
		string $action = PathMaker::DEFAULT_ACTION,
	): ?PathMaker
	{
		$class = match ($entityType)
		{
			'task' => TaskPathMaker::class,
			'template' => TemplatePathMaker::class,
			default => null
		};

		if ($class === null)
		{
			return null;
		}

		return new $class(
			entityId: $entityId,
			action: $action,
			ownerId: $ownerId,
			context: $context,
		);
	}
}