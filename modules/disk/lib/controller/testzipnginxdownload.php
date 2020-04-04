<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk\Internals\Engine\Controller;
use Bitrix\Disk\ZipNginx\Archive;
use Bitrix\Disk\ZipNginx\TestArchiveEntry;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;

class TestZipNginxDownload extends Controller
{
	public function configureActions()
	{
		return [
			'download' => [
				'prefilters' => [
					new CloseSession(),
					new HttpMethod([HttpMethod::METHOD_GET])
				],
			],
		];
	}

	/**
	 * Processes action to download test zip archive.
	 *
	 * @return Archive
	 */
	public function downloadAction()
	{
		$zipNginx = new Archive('ivandivan.zip');
		$zipNginx->addEntry(TestArchiveEntry::create());

		return $zipNginx;
	}
}