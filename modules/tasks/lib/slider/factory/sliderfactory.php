<?php

namespace Bitrix\Tasks\Slider\Factory;

use Bitrix\Tasks\Slider\Exception\UnknownEntityContextException;
use Bitrix\Tasks\Slider\Exception\UnknownEntityTypeException;
use Bitrix\Tasks\Slider\Path\PathMaker;
use Bitrix\Tasks\Slider\Path\TaskPathMaker;
use Bitrix\Tasks\Slider\Path\TaskTagsPathMaker;
use Bitrix\Tasks\Slider\Path\TemplatePathMaker;
use Bitrix\Tasks\Slider\TasksSliderInterface;
use Bitrix\Tasks\Slider\TasksSlider;

class SliderFactory
{
	public const TASK = 'task';
	public const TEMPLATE = 'template';
	public const TAGS = 'tags';

	public const PERSONAL_CONTEXT = PathMaker::PERSONAL_CONTEXT;
	public const GROUP_CONTEXT = PathMaker::GROUP_CONTEXT;
	public const SPACE_CONTEXT = PathMaker::SPACE_CONTEXT;
	public const EDIT_ACTION = PathMaker::EDIT_ACTION;
	public const VIEW_ACTION = PathMaker::DEFAULT_ACTION;

	private string $action = PathMaker::DEFAULT_ACTION;

	private string $queryParams = '';
	private bool $skipEvents = false;

	public function setAction(string $action): self
	{
		$this->action = $action;
		return $this;
	}

	public function setQueryParams(string $getParams): self
	{
		$this->queryParams = $getParams;
		return $this;
	}

	public function getAction(): string
	{
		return $this->action;
	}

	public function getQueryParams(): string
	{
		return $this->queryParams;
	}

	public function skipEvents(): self
	{
		$this->skipEvents = true;
		return $this;
	}

	/**
	 * @throws UnknownEntityTypeException
	 * @throws UnknownEntityContextException
	 */
	public function createEntitySlider(
		int $entityId,
		string $entityType,
		int $ownerId,
		string $context
	): TasksSliderInterface
	{
		switch ($entityType)
		{
			case self::TASK:
				if (!in_array($context, [PathMaker::PERSONAL_CONTEXT, PathMaker::GROUP_CONTEXT, PathMaker::SPACE_CONTEXT], true))
				{
					throw new UnknownEntityContextException('Wrong entity context.');
				}

				$pathService = new TaskPathMaker($entityId, $this->action, $ownerId, $context);
				break;

			case self::TEMPLATE:
				$pathService = new TemplatePathMaker($entityId, $this->action, $ownerId, $context);
				break;

			default:
				throw new UnknownEntityTypeException('Wrong entity type.');
		}

		$pathService->setQueryParams($this->queryParams);

		$entityPath = $pathService->makeEntityPath();
		$entityListPath = $pathService->makeEntitiesListPath();

		return new TasksSlider($entityPath, $entityListPath, $this->skipEvents);
	}

	/**
	 * @throws UnknownEntityTypeException
	 */
	public function createEntityListSlider(string $entityType, int $ownerId, string $context): TasksSliderInterface
	{
		$width = null;
		switch ($entityType)
		{
			case self::TAGS:
				$width = 1000;
				$pathService = new TaskTagsPathMaker(0, '', $ownerId, $context);
				break;

			default:
				throw new UnknownEntityTypeException('Wrong entity type.');
		}

		$pathService->setQueryParams($this->queryParams);

		$entityPath = $pathService->makeEntityPath();
		$entityListPath = $pathService->makeEntitiesListPath();
		$slider = new TasksSlider($entityPath, $entityListPath);

		if (!is_null($width))
		{
			$slider->setWidth($width);
		}

		return $slider;
	}
}