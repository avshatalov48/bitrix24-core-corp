<?php

declare(strict_types=1);

namespace Bitrix\Disk\Public\Service\UnifiedLink\Render;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\File;
use Bitrix\Disk\Internal\Service\UnifiedLink\Render\UnifiedLinkFileRenderer;
use Bitrix\Disk\Version;
use Bitrix\Main\NotImplementedException;

class MobileUnifiedLinkRenderer
{
	/**
	 * @param File $file
	 * @param AttachedObject|null $attachedObject
	 * @param Version|null $version
	 * @return string
	 */
	public static function render(File $file, ?AttachedObject $attachedObject, ?Version $version): string
	{
		$renderer = new UnifiedLinkFileRenderer($file, $attachedObject, $version);

		return $renderer->render()->getContent();
	}

	/**
	 * @param string $uniqueCode
	 * @param int $attachedObjectId
	 * @param int $versionId
	 * @return string
	 * @throws NotImplementedException
	 */
	public static function renderByUniqueCode(string $uniqueCode, int $attachedObjectId, int $versionId): string
	{
		$file = File::loadByUniqueCode($uniqueCode);
		if (!$file)
		{
			return UnifiedLinkFileRenderer::renderAccessDeniedPage();
		}

		$attachedObject = AttachedObject::loadById($attachedObjectId);
		$version = Version::loadById($versionId);

		return self::render($file, $attachedObject, $version);
	}

	/**
	 * @param int $fileId
	 * @param int $attachedObjectId
	 * @param int $versionId
	 * @return string
	 * @throws NotImplementedException
	 */
	public static function renderByFileId(int $fileId, int $attachedObjectId, int $versionId): string
	{
		$file = File::loadById($fileId)?->getRealObject();
		if (!$file)
		{
			return UnifiedLinkFileRenderer::renderAccessDeniedPage();
		}

		$attachedObject = AttachedObject::loadById($attachedObjectId);
		$version = Version::loadById($versionId);

		return self::render($file, $attachedObject, $version);
	}

	/**
	 * @param int $attachedObjectId
	 * @param int $versionId
	 * @return string
	 * @throws NotImplementedException
	 */
	public static function renderByAttachedObject(int $attachedObjectId, int $versionId): string
	{
		$attachedObject = AttachedObject::loadById($attachedObjectId);
		$file = $attachedObject?->getFile()?->getRealObject();
		if (!$file)
		{
			return UnifiedLinkFileRenderer::renderAccessDeniedPage();
		}

		$version = Version::loadById($versionId);

		return self::render($file, $attachedObject, $version);
	}
}
