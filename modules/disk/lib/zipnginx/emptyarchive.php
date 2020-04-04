<?php

namespace Bitrix\Disk\ZipNginx;

final class EmptyArchive extends Archive
{
	const EMPTY_ARCHIVE_PATH = '/bitrix/images/disk/empty.zip';

	/** @var string */
	protected $name;

	public function __construct($name)
	{
		parent::__construct($name);
	}

	public function send()
	{
		$this->disableCompression();

		$fileArray = \CFile::MakeFileArray(self::EMPTY_ARCHIVE_PATH);
		\CFile::ViewByUser($fileArray, ['force_download' => true, 'attachment_name' => $this->getName()]);
	}
}
