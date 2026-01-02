<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Service\UnifiedLink\Render;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\File;
use Bitrix\Disk\Internal\Access\UnifiedLink\UnifiedLinkAccessLevel;
use Bitrix\Disk\Internal\Service\UnifiedLink\FileHandler\FileHandlerOperationResult;
use Bitrix\Disk\Internal\Service\UnifiedLink\FileHandler\HtmlRenderableFileHandler;
use Bitrix\Disk\Internal\Service\UnifiedLink\FileHandler\HtmlRenderableFileHandlerFactory;
use Bitrix\Disk\Internal\Service\UnifiedLink\FileResolver;
use Bitrix\Disk\Internal\Service\UnifiedLink\UnifiedLinkAccessService;
use Bitrix\Disk\Version;
use Bitrix\Main\DI\ServiceLocator;
use LogicException;

class UnifiedLinkFileRenderer
{
	private UnifiedLinkAccessService $unifiedLinkAccessService;
	private HtmlRenderableFileHandler $fileHandler;
	private ?UnifiedLinkAccessLevel $accessLevel = null;

	public function __construct(
		private readonly File $file,
		private readonly ?AttachedObject $attachedObject = null,
		private readonly ?Version $version = null,
	) {
		$serviceLocator = ServiceLocator::getInstance();
		$this->unifiedLinkAccessService = $serviceLocator->get(UnifiedLinkAccessService::class);
		$this->fileHandler = $serviceLocator->get(HtmlRenderableFileHandlerFactory::class)->createHandler(
			$this->file,
			$this->attachedObject,
			$this->version,
		);
	}

	public function getAccessLevel(): UnifiedLinkAccessLevel
	{
		if ($this->accessLevel === null)
		{
			$file = $this->resolveFile();
			$this->accessLevel = $this->unifiedLinkAccessService->check($file, $this->attachedObject);
		}

		return $this->accessLevel;
	}

	public function resolveFile(): File
	{
		return FileResolver::resolve($this->file, $this->version);
	}

	public function render(?UnifiedLinkAccessLevel $accessLevel = null): RenderResult
	{
		$accessLevel ??= $this->getAccessLevel();
		if ($accessLevel === UnifiedLinkAccessLevel::Denied)
		{
			return new RenderResult(self::renderAccessDeniedPage(), 403);
		}

		$result = match ($accessLevel)
		{
			UnifiedLinkAccessLevel::Edit => $this->fileHandler->edit(),
			default => $this->fileHandler->view(),
		};

		if (!$result->isSuccess())
		{
			$content = $this->renderServerErrorPage($result);

			return new RenderResult($content, 500);
		}

		return new RenderResult($result->getValue(), 200);
	}

	public static function renderAccessDeniedPage(): string
	{
		return $GLOBALS['APPLICATION']->includeComponent(
			'bitrix:ui.sidepanel.wrapper',
			'',
			[
				'RETURN_CONTENT' => true,
				'POPUP_COMPONENT_NAME' => 'bitrix:disk.error.page',
				'POPUP_COMPONENT_PARAMS' => [
				],
				'PLAIN_VIEW' => false,
				'IFRAME_MODE' => true,
				'PREVENT_LOADING_WITHOUT_IFRAME' => false,
				'USE_PADDING' => true,
			],
		);
	}

	private function renderServerErrorPage(FileHandlerOperationResult $result): string
	{
		if ($result->isSuccess())
		{
			throw new LogicException('Cannot get server error response from success result');
		}

		return 'Server error occurred. Please try again later.';
	}
}
