<?php

declare(strict_types=1);

namespace Bitrix\Disk\Infrastructure\Controller\UnifiedLink\Meta;

use Bitrix\Disk\File;
use Bitrix\Main\Application;
use Bitrix\Main\Diag\ExceptionHandler;
use Bitrix\Disk\Infrastructure\Controller\UnifiedLink\Attributes\
{UrlGenerator};
use Bitrix\Disk\Infrastructure\Controller\UnifiedLink\Attributes\FileTypes;
use Bitrix\Disk\Infrastructure\Controller\UnifiedLink\Attributes\LevelAccess;
use Bitrix\Disk\Internal\Access\UnifiedLink\UnifiedLinkAccessLevel;
use Bitrix\Main\Engine\Controller;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use SplObjectStorage;
use Throwable;

class ActionsMetadata
{
	/** @var array<string, ?UnifiedLinkAccessLevel> $accessLevelsByActions */
	private ?array $accessLevelsByActions = null;

	/** @var SplObjectStorage<UnifiedLinkAccessLevel, string>|null $actionsByAccessLevels */
	private ?SplObjectStorage $actionsByAccessLevels = null;

	/** @var SplObjectStorage<UnifiedLinkAccessLevel, callable(File): string>|null $urlGeneratorByAccessLevels */
	private ?SplObjectStorage $urlGeneratorByAccessLevels = null;

	/** @var array<string, int[]>|null $allowedFileTypesByActions */
	private ?array $allowedFileTypesByActions = null;

	/** @var ReflectionAttribute[] $attributesByActions */
	private array $attributesByActions;
	private ExceptionHandler $exceptionHandler;

	public function __construct(
		private readonly Controller $controller,
	) {
		$this->attributesByActions = $this->getActionAttributes();
		$this->exceptionHandler = Application::getInstance()->getExceptionHandler();
	}

	public function isFileTypeAllowed(string $actionName, File $file): bool
	{
		if ($this->allowedFileTypesByActions === null)
		{
			$this->collectFileTypeByActions();
		}

		$allowedFileTypesForAction = $this->allowedFileTypesByActions[$actionName] ?? [];

		if (empty($allowedFileTypesForAction))
		{
			return true; // If no specific file types are defined for the action, allow all types.
		}

		$typeFile = (int)$file->getTypeFile();

		return in_array($typeFile, $allowedFileTypesForAction, true);
	}

	private function collectFileTypeByActions(): void
	{
		$this->allowedFileTypesByActions = [];

		foreach ($this->attributesByActions as $actionName => $actionAttributes)
		{
			$this->allowedFileTypesByActions[$actionName] = $actionAttributes[FileTypes::class]?->getArguments() ?? [];
		}
	}

	public function getAccessLevel(string $actionName): ?UnifiedLinkAccessLevel
	{
		if ($this->accessLevelsByActions === null)
		{
			$this->mapActionsToAccessLevelsAndAttributes();
		}

		return $this->accessLevelsByActions[$actionName] ?? null;
	}

	public function getActionName(UnifiedLinkAccessLevel $accessLevel): ?string
	{
		if ($this->accessLevelsByActions === null)
		{
			$this->mapActionsToAccessLevelsAndAttributes();
		}

		return $this->actionsByAccessLevels[$accessLevel] ?? null;
	}

	private function mapActionsToAccessLevelsAndAttributes(): void
	{
		$this->accessLevelsByActions = [];
		$this->actionsByAccessLevels = new SplObjectStorage();

		foreach ($this->attributesByActions as $actionName => $actionAttributes)
		{
			$accessLevel = $actionAttributes[LevelAccess::class]?->getArguments()[0] ?? null;

			if ($accessLevel === null)
			{
				continue;
			}

			$this->accessLevelsByActions[$actionName] = $accessLevel;
			$this->actionsByAccessLevels[$accessLevel] = $actionName;
		}
	}

	public function getUrl(UnifiedLinkAccessLevel $accessLevel, File $file): ?string
	{
		if ($this->urlGeneratorByAccessLevels === null)
		{
			$this->collectUrlByAccessLevels();
		}

		$urlGenerator = $this->urlGeneratorByAccessLevels[$accessLevel] ?? null;

		if ($urlGenerator !== null)
		{
			try
			{
				return $urlGenerator($file);
			}
			catch (Throwable $e)
			{
				$this->exceptionHandler->writeToLog($e);
			}
		}

		return null;
	}

	private function collectUrlByAccessLevels(): void
	{
		if ($this->accessLevelsByActions === null)
		{
			$this->mapActionsToAccessLevelsAndAttributes();
		}

		$this->urlGeneratorByAccessLevels = new SplObjectStorage();

		foreach ($this->attributesByActions as $actionName => $actionAttributes)
		{
			// It is assumed that there are no two methods with the same access level
			$accessLevel = $this->accessLevelsByActions[$actionName] ?? null;
			if ($accessLevel !== null)
			{
				$this->urlGeneratorByAccessLevels[$accessLevel] = $actionAttributes[UrlGenerator::class]?->getArguments()[0] ?? null;
			}
		}
	}

	private function getActionAttributes(): array
	{
		$actionAttributes = [];
		try
		{
			$reflectionClass = new ReflectionClass($this->controller);

			foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod)
			{
				$methodName = $reflectionMethod->getName();
				if (!str_ends_with($methodName, 'Action'))
				{
					continue;
				}

				$actionName = substr($methodName, 0, -6);

				foreach ($reflectionMethod->getAttributes() as $actionAttribute)
				{
					$actionAttributes[$actionName][$actionAttribute->getName()] = $actionAttribute;
				}
			}
		}
		catch (Throwable $e)
		{
			$this->exceptionHandler->writeToLog($e);
		}

		return $actionAttributes;
	}
}
