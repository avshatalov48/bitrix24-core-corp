<?php

declare(strict_types=1);

namespace Bitrix\Disk\Infrastructure\Controller\UnifiedLink\ActionFilter;

use Bitrix\Disk\File;
use Bitrix\Disk\Infrastructure\Controller\UnifiedLink\Meta\ActionsMetadata;
use Bitrix\Disk\Internal\Access\UnifiedLink\UnifiedLinkAccessLevel;
use Bitrix\Disk\Internal\Service\UnifiedLink\Render\UnifiedLinkFileRenderer;
use Bitrix\Disk\Public\Service\UnifiedLink\UrlGenerator;
use Bitrix\Disk\TypeFile;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\Uri;

/**
 * Action filter to check access to files based on the action being executed.
 * Redirect to a specific URL if the access level of the file does not match the action's access level.
 */
class UnifiedLinkAccessLevelRouter extends Base
{
	private bool $accessDenied = false;
	private bool $needRedirect = false;
	private string $urlToRedirect = '';
	private readonly ActionsMetadata $actionsMetaData;

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

		$fileAccessLevel = $service->getAccessLevel();
		if ($fileAccessLevel === UnifiedLinkAccessLevel::Denied)
		{
			return $this->setAccessDeniedResult();
		}

		$methodAccessLevel = $this->actionsMetaData->getAccessLevel($action->getName());
		if ($methodAccessLevel !== null && $methodAccessLevel !== $fileAccessLevel)
		{
			$file = $service->resolveFile();
			$urlToRedirect = $this->actionsMetaData->getUrl($fileAccessLevel, $file);
			if (isset($urlToRedirect) && $this->shouldRedirect($fileAccessLevel, $file))
			{
				$currentUri = new Uri($this->controller->getRequest()->getRequestUri());
				$uriToRedirect = (string)$currentUri->withPath($urlToRedirect);

				return $this->setRedirectResult($uriToRedirect);
			}

			if ($methodAccessLevel->value > $fileAccessLevel->value)
			{
				return $this->setAccessDeniedResult();
			}
		}

		return null;
	}

	/**
	 * @param Event $event
	 * @return void
	 * @throws ArgumentNullException
	 * @throws ArgumentTypeException
	 */
	public function onAfterAction(Event $event): void
	{
		if ($this->accessDenied)
		{
			$response = $this->createResponse(403);
			$response->setContent(UnifiedLinkFileRenderer::renderAccessDeniedPage());

			$event->setParameter('result', $response);
		}

		if ($this->needRedirect && $this->urlToRedirect !== '')
		{
			$response = $this->createResponse(302);
			$response->addHeader('Location', $this->urlToRedirect);

			$event->setParameter('result', $response);
		}
	}

	private function createResponse(int $status): HttpResponse
	{
		return (new HttpResponse())
			->setStatus($status)
		;
	}

	private function shouldRedirect(UnifiedLinkAccessLevel $fileAccessLevel, File $file): bool
	{
		$request = $this->controller->getRequest();

		if ($request->get(UrlGenerator::QUERY_PARAM_NO_REDIRECT) !== null)
		{
			return false;
		}

		// disable redirects to document editing to avoid exceeding editing limits
		$isDocument = (int)$file->getTypeFile() === TypeFile::DOCUMENT;
		if ($isDocument && $fileAccessLevel === UnifiedLinkAccessLevel::Edit)
		{
			return false;
		}

		$actionName = $this->actionsMetaData->getActionName($fileAccessLevel);

		return $this->actionsMetaData->isFileTypeAllowed($actionName, $file);
	}

	private function setAccessDeniedResult(): EventResult
	{
		$this->accessDenied = true;

		return new EventResult(EventResult::ERROR);
	}

	private function setRedirectResult(string $urlToRedirect): EventResult
	{
		$this->needRedirect = true;
		$this->urlToRedirect = $urlToRedirect;

		return new EventResult(EventResult::ERROR);
	}
}
