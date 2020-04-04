<?php

namespace Bitrix\Disk\ZipNginx;

use Bitrix\Disk\Internals\Controller;

class TestDownloadController extends Controller
{
	/**
	 * Lists all actions by controller.
	 *
	 * @return array
	 */
	protected function listActions()
	{
		return array(
			'downloadTestZipArchive' => array(
				'method' => array('GET'),
				'redirect_on_auth' => false,
				'close_session' => true,
			),
		);
	}

	/**
	 * Runs processing if user is not authorized.
	 * @return void
	 */
	protected function runProcessingIfUserNotAuthorized()
	{
		if(strtolower($this->getAction()) === strtolower('downloadTestZipArchive'))
		{
			//test ZipNginx without user.
			return;
		}
		else
		{
			parent::runProcessingIfUserNotAuthorized();
		}
	}

	/**
	 * Processes action to download test zip archive.
	 *
	 * @return void
	 */
	protected function processActionDownloadTestZipArchive()
	{
		$zipNginx = new Archive(
			'ivandivan.zip',
			array(TestArchiveEntry::create())
		);
		$zipNginx->send();

		$this->end();
	}
}