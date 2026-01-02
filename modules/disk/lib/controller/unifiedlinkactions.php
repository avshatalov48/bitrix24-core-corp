<?php

declare(strict_types=1);

namespace Bitrix\Disk\Controller;

use Bitrix\Disk;
use Bitrix\Disk\Controller\ActionFilter\RequiredParameter;
use Bitrix\Disk\File;
use Bitrix\Disk\Internals\Engine\ActionFilter\CheckReadPermission;
use Bitrix\Disk\Infrastructure\Controller\UnifiedLink\ActionFilter\UnifiedLinkAccessChecker;
use Bitrix\Disk\Internal\Access\UnifiedLink\UnifiedLinkAccessLevel;
use Bitrix\Disk\Internal\Service\UnifiedLink\FileResolver;
use Bitrix\Main\Engine\AutoWire\BinderArgumentException;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Request;

class UnifiedLinkActions extends BaseObject
{
	private RequiredParameter $requiredParameterFilter;

	public function __construct(?Request $request = null)
	{
		parent::__construct($request);

		$this->requiredParameterFilter = new RequiredParameter('file', false);
	}

	protected function getDefaultPreFilters(): array
	{
		$defaultPreFilters = parent::getDefaultPreFilters();

		$notCheckReadPermission = static fn($preFilter) => !$preFilter instanceof CheckReadPermission;

		$defaultPreFilters = array_filter($defaultPreFilters, $notCheckReadPermission);

		$defaultPreFilters[] = $this->requiredParameterFilter;

		return $defaultPreFilters;
	}

	protected function getDefaultPostFilters(): array
	{
		$defaultPostFilters = parent::getDefaultPostFilters();

		$defaultPostFilters[] = $this->requiredParameterFilter;

		return $defaultPostFilters;
	}

	public function configureActions()
	{
		$configureActions = parent::configureActions();

		$readAccessChecker = new UnifiedLinkAccessChecker(UnifiedLinkAccessLevel::Read);
		$editAccessChecker = new UnifiedLinkAccessChecker(UnifiedLinkAccessLevel::Edit);

		$configureActions['getExternalLink'] = [
			'+prefilters' => [
				$editAccessChecker,
			],
		];
		$configureActions['generateExternalLink'] = [
			'+prefilters' => [
				$editAccessChecker,
			],
		];
		$configureActions['disableExternalLink'] = [
			'+prefilters' => [
				$editAccessChecker,
			],
		];
		$configureActions['get'] = [
			'+prefilters' => [
				$readAccessChecker,
			],
		];

		return $configureActions;
	}

	/**
	 * @throws BinderArgumentException
	 */
	public function getAutoWiredParameters(): array
	{
		return [
			new ExactParameter(
				File::class,
				'file',
				function ($className, string $uniqueCode): ?File {
					return File::loadByUniqueCode($uniqueCode);
				},
			),
		];
	}

	/**
	 * @param File|null $file uniqueCode is passed as string and converted to File object via autowiring
	 * @return array|null
	 */
	public function getExternalLinkAction(?Disk\File $file): ?array
	{
		/** @noinspection NullPointerExceptionInspection */
		return $this->getExternalLink($file);
	}

	/**
	 * @param File|null $file uniqueCode is passed as string and converted to File object via autowiring
	 * @return array|null
	 */
	public function generateExternalLinkAction(?Disk\File $file): ?array
	{
		/** @noinspection NullPointerExceptionInspection */
		return $this->generateExternalLink($file);
	}

	/**
	 * @param File|null $file uniqueCode is passed as string and converted to File object via autowiring
	 * @return bool
	 */
	public function disableExternalLinkAction(?Disk\File $file): bool
	{
		/** @noinspection NullPointerExceptionInspection */
		return $this->disableExternalLink($file);
	}

	/**
	 * @param File $file uniqueCode is passed as string and converted to File object via autowiring
	 * @param Disk\Version|null $version versionId is passed as int and converted to Version object via autowiring
	 * @param Disk\AttachedObject|null $attachedObject attachedObjectId is passed as int and converted to AttachedObject
	 * object via autowiring, needed to check access to the file, don't remove it
	 * @return array
	 */
	public function getAction(Disk\File $file, ?Disk\Version $version = null, ?Disk\AttachedObject $attachedObject = null): array
	{
		$resolvedFile = FileResolver::resolve($file, $version);

		return $this->get($resolvedFile);
	}
}
