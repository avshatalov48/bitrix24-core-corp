<?php

namespace Bitrix\Sign\Config\Ui;

use Bitrix\Main\Context;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Main\Application;

final class BlankSelector
{
	public function create(
		string $scenario,
		?string $regionCode = null,
		array $regionDocumentTypes = [],
		?bool $isEdoRegion = null
	): array
	{
		$storage = Storage::instance();
		$regionCode ??= \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();

		return [
			'uploaderOptions' => [
				'maxFileSize' => $storage->getUploadDocumentMaxSize(),
				'imageMaxFileSize' => $storage->getUploadImagesMaxSize(),
				'maxTotalFileSize' => $storage->getUploadTotalMaxSize(),
				'maxFileCount' => $storage->getImagesCountLimitForBlankUpload(),
			],
			'portalConfig' => [
				'isDomainChanged' => $this->isDomainChanged($storage->getSavedDomain()),
				'isUnsecuredScheme' => $this->isUnsecuredScheme(),
				'isEdoRegion' => $isEdoRegion ?? $storage->isEdoRegion(),
			],
			'type' => $scenario,
			'region' => $regionCode,
			'regionDocumentTypes' => $regionDocumentTypes,
		];
	}


	private function isUnsecuredScheme(): bool
	{
		return !Context::getCurrent()?->getRequest()?->isHttps();
	}

	private function isDomainChanged($currentDomain): bool
	{
		return $currentDomain !== Application::getServer()->getHttpHost();
	}
}