<?php

namespace Bitrix\Crm\Controller\Site;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\UI\FileUploader\Chunk;
use Bitrix\UI\FileUploader\UploaderController;

class FileUploader extends \Bitrix\UI\Controller\FileUploader
{
	public function configureActions()
	{
		$configuration = parent::configureActions();

		$configuration['upload']['-prefilters'][] = ActionFilter\Csrf::class;

		$corsFilter = (new ActionFilter\Cors(null, true))
			->setAllowedMethods([
				'POST'
			])
			->setAllowedHeaders([
				'X-Upload-Content-Name',
				'Content-Type',
				'Crm-Webform-Cors',
				'Content-Range'
			]);

		$configuration['upload']['prefilters'][] = $corsFilter;

		return $configuration;
	}

	public function uploadAction(UploaderController $controller, Chunk $chunk, string $token = null): array
	{
		if ($this->getRequest()->getRequestMethod() === 'OPTIONS')
		{
			return [];
		}

		return parent::uploadAction($controller, $chunk, $token);
	}

	protected function getAvailableControllers(): ?array
	{
		return [
			'crm.fileUploader.siteFormFileUploaderController'
		];
	}
}