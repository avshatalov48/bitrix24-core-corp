<?php

namespace Bitrix\Disk\ZipNginx;


use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;

class Archive
{
	/** @var string */
	protected $name;
	/** @var ArchiveEntry[] */
	protected $entries = array();

	public function __construct($name, array $entries = array())
	{
		$this->name = $name;
		$this->entries = $entries;
	}

	/**
	 * Creates archive which will be copy of folder.
	 * @param Folder          $folder Target folder.
	 * @param SecurityContext $securityContext Security context to getting items.
	 * @return static
	 */
	public static function createFromFolder(Folder $folder, SecurityContext $securityContext)
	{
		$archive = new static($folder->getName() . '.zip');
		$archive->collectDescendants($folder, $securityContext);

		return $archive;
	}

	private function collectDescendants(Folder $folder, SecurityContext $securityContext, $currentPath = '')
	{
		foreach($folder->getChildren($securityContext) as $object)
		{
			if($object instanceof Folder)
			{
				$this->collectDescendants(
					$object,
					$securityContext,
					$currentPath . $object->getName() . '/'
				);

			}
			elseif($object instanceof File)
			{
				$this->addEntry(ArchiveEntry::createFromFile($object, $currentPath . $object->getName()));
			}
		}
	}

	/**
	 * Returns true if the archive does not have entries.
	 *
	 * @return bool
	 */
	public function isEmpty()
	{
		return empty($this->entries);
	}

	/**
	 * Returns name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Sets name of zip archive.
	 *
	 * @param string $name Name.
	 * @return $this
	 */
	public function setName($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * Adds entry to archive.
	 *
	 * @param ArchiveEntry $entry Entry.
	 * @return $this
	 */
	public function addEntry(ArchiveEntry $entry)
	{
		$this->entries[] = $entry;

		return $this;
	}

	private function addHeaders()
	{
		$httpResponse = Context::getCurrent()->getResponse();
		$httpResponse->addHeader('X-Archive-Files', 'zip');

		$utfName = \CHTTP::urnEncode($this->name, 'UTF-8');
		$translitName = \CUtil::translit($this->name, LANGUAGE_ID, array(
			'max_len' => 1024,
			'safe_chars' => '.',
			'replace_space' => '-',
		));
		$httpResponse->addHeader(
			'Content-Disposition',
			"attachment; filename=\"" . $translitName . "\"; filename*=utf-8''" . $utfName
		);
	}

	/**
	 * @return string
	 */
	private function getFileList()
	{
		$list = array();
		foreach($this->entries as $entry)
		{
			$list[] = (string)$entry;
		}
		unset($entry);

		return implode("\n", $list);
	}

	/**
	 * Sends content to output stream and sets necessary headers.
	 *
	 * @return void
	 */
	public function send()
	{
		if ($this->isEmpty())
		{
			(new EmptyArchive($this->getName()))->send();
		}
		else
		{
			$this->disableCompression();
			$this->addHeaders();

			Context::getCurrent()->getResponse()->flush($this->getFileList());
		}
	}

	protected function disableCompression()
	{
		if(Loader::includeModule('compression'))
		{
			\CCompress::disableCompression();
		}
	}
}
