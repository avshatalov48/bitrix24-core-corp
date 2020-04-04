<?php

namespace Bitrix\Disk\Document\Upload;

use Bitrix\Disk\Document\FileData;
use Bitrix\Disk\Document\Office365Handler;

/**
 * @property Office365Handler $documentHandler
 */
class Office365ResumableUpload extends OneDriveResumableUpload
{
	const SUFFIX_TO_CREATE_UPLOAD_SESSION = Office365Handler::SUFFIX_TO_CREATE_UPLOAD_SESSION;

	protected function getPostFieldsForUpload(FileData $fileData)
	{
		return array(
			'item' => array('@microsoft.graph.conflictBehavior' => 'rename')
		);
	}
}