<?php

declare(strict_types=1);

namespace Bitrix\Disk\Infrastructure\Controller\UnifiedLink\ActionFilter;

use Bitrix\Disk\Infrastructure\Controller\UnifiedLink\Meta\ActionsMetadata;
use Bitrix\Disk\Internal\Service\UnifiedLink\Render\UnifiedLinkFileRenderer;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\SystemException;

class FileTypeControl extends Base
{
	private ActionsMetadata $actionsMetaData;
	private bool $fileTypeNotAllowed = false;

	public function __construct(
		private readonly Controller $controller,
	) {
		parent::__construct();

		$this->actionsMetaData = new ActionsMetadata($this->controller);
	}

	/**
	 * @param Event $event
	 * @return EventResult|null
	 * @throws SystemException
	 */
	public function onBeforeAction(Event $event): ?EventResult
	{
		$action = $this->getAction();
		/** @var UnifiedLinkFileRenderer $service */
		$service = $action->getArguments()['service'] ?? null;

		if ($service === null)
		{
			return null;
		}

		$file = $service->resolveFile();

		if (!$this->actionsMetaData->isFileTypeAllowed($action->getName(), $file))
		{
			$this->fileTypeNotAllowed = true;

			return new EventResult(EventResult::ERROR);
		}

		return null;
	}

	public function onAfterAction(Event $event): void
	{
		if ($this->fileTypeNotAllowed)
		{
			$response = (new HttpResponse())
				->setStatus(400)
				->setContent('Operation not supported for this file type')
			;

			$event->setParameter('result', $response);
		}
	}
}
