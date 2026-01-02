<?php

declare(strict_types=1);

namespace Bitrix\Disk\Controller;

use Bitrix\Disk\Controller\ActionFilter\RequiredParameter;
use Bitrix\Disk\File;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\Internal\Service\UnifiedLink\Render\UnifiedLinkFileRenderer;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Disk\Infrastructure\Controller\UnifiedLink\ActionFilter\
{FileTypeControl};
use Bitrix\Disk\Infrastructure\Controller\UnifiedLink\ActionFilter\UnifiedLinkAccessLevelRouter;
use Bitrix\Disk\Infrastructure\Controller\UnifiedLink\Attributes\
{UrlGenerator};
use Bitrix\Disk\Infrastructure\Controller\UnifiedLink\Attributes\FileTypes;
use Bitrix\Disk\Infrastructure\Controller\UnifiedLink\Attributes\LevelAccess;
use Bitrix\Disk\Internal\Access\UnifiedLink\UnifiedLinkAccessLevel;
use Bitrix\Disk\UrlManager;
use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Version;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\AutoWire\BinderArgumentException;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Request;

class UnifiedLinkController extends Controller
{
	private RequiredParameter $requiredParameterFilter;
	private UnifiedLinkAccessLevelRouter $accessControlFilter;
	private FileTypeControl $fileTypeFilter;

	public function __construct(?Request $request = null)
	{
		$this->requiredParameterFilter = new RequiredParameter('service');
		$this->fileTypeFilter = new FileTypeControl($this);
		$this->accessControlFilter = new UnifiedLinkAccessLevelRouter($this);

		parent::__construct($request);
	}

	protected function getDefaultPreFilters(): array
	{
		return [
			new Authentication(true), // while external links are not implemented
			$this->requiredParameterFilter,
			new HttpMethod([HttpMethod::METHOD_GET]),
			$this->fileTypeFilter,
			$this->accessControlFilter,
		];
	}

	protected function getDefaultPostFilters(): array
	{
		return [
			$this->requiredParameterFilter,
			$this->fileTypeFilter,
			$this->accessControlFilter,
		];
	}

	/**
	 * @return ExactParameter[]
	 * @throws BinderArgumentException
	 */
	public function getAutoWiredParameters(): array
	{
		return [
			new ExactParameter(
				UnifiedLinkFileRenderer::class,
				'service',
				function ($className,  string $uniqueCode): ?UnifiedLinkFileRenderer {
					$file = File::loadByUniqueCode($uniqueCode);
					if (!$file)
					{
						return null;
					}
					$attachedObject = AttachedObject::loadById((int)$this->request->get('attachedId'));
					$version = Version::loadById((int)$this->request->get('versionId'));

					return new UnifiedLinkFileRenderer($file, $attachedObject, $version);
				},
			),
		];
	}

	#[LevelAccess(UnifiedLinkAccessLevel::Read)]
	#[UrlGenerator([new UrlManager(), 'getUnifiedLink'])]
	#[FileTypes(TypeFile::FLIPCHART, TypeFile::DOCUMENT, TypeFile::PDF)]
	public function viewAction(?UnifiedLinkFileRenderer $service): HttpResponse
	{
		return $this->createResponse($service, UnifiedLinkAccessLevel::Read);
	}

	#[LevelAccess(UnifiedLinkAccessLevel::Edit)]
	#[UrlGenerator([new UrlManager(), 'getUnifiedEditLink'])]
	#[FileTypes(TypeFile::FLIPCHART, TypeFile::DOCUMENT)]
	public function editAction(?UnifiedLinkFileRenderer $service): HttpResponse
	{
		return $this->createResponse($service, UnifiedLinkAccessLevel::Edit);
	}

	/**
	 * @param UnifiedLinkFileRenderer|null $service
	 * @return HttpResponse
	 * @throws ArgumentTypeException
	 */
	private function createResponse(?UnifiedLinkFileRenderer $service, UnifiedLinkAccessLevel $accessLevel): HttpResponse
	{
		if ($service === null)
		{
			return (new HttpResponse())
				->setStatus(404)
				->setContent(UnifiedLinkFileRenderer::renderAccessDeniedPage())
			;
		}

		$result = $service->render($accessLevel);

		return (new HttpResponse())
			->setStatus($result->getStatus())
			->setContent($result->getContent())
		;
	}
}
