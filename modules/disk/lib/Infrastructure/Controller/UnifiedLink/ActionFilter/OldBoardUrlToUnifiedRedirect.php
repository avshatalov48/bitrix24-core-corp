<?php

declare(strict_types=1);

namespace Bitrix\Disk\Infrastructure\Controller\UnifiedLink\ActionFilter;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\FileLink;
use Bitrix\Disk\Internal\Service\UnifiedLink\Configuration;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\ObjectException;

class OldBoardUrlToUnifiedRedirect extends Base
{
	private bool $needRedirect = false;
	private string $unifiedLink = '';

	public function onBeforeAction(Event $event): ?EventResult
	{
		$action = $this->getAction();

		$file = $action->getArguments()['file'] ?? null;
		$attachedObject = $action->getArguments()['attachedObject'] ?? null;

		if ($attachedObject instanceof AttachedObject)
		{
			$file = $attachedObject->getFile();
		}

		if ($file instanceof FileLink)
		{
			try
			{
				$file = $file->getRealObject();
			}
			catch (ObjectException)
			{
				return null;
			}
		}

		if (!$file instanceof File)
		{
			return null;
		}

		if (!$file->supportsUnifiedLink())
		{
			return null;
		}

		$options = [
			'absolute' => true,
			'attachedId' => $attachedObject?->getId(),
		];

		$request = Context::getCurrent()?->getRequest();
		$versionId = $request?->getQuery('versionId');
		if ($versionId)
		{
			$options['versionId'] = (int)$versionId;
		}

		$options = array_filter($options);

		$this->needRedirect = true;
		$this->unifiedLink = Driver::getInstance()->getUrlManager()->getUnifiedLink($file, $options);

		return new EventResult(EventResult::ERROR);
	}

	public function onAfterAction(Event $event): void
	{
		if ($this->needRedirect)
		{
			$response = (new HttpResponse())
				->setStatus(301)
				->addHeader('Location', $this->unifiedLink)
			;

			$event->setParameter('result', $response);
		}
	}
}
