<?php
namespace Bitrix\Main\IO;

abstract class DirectoryEntry
	extends FileSystemEntry
{
	public function __construct($path, $siteId = null)
	{
		parent::__construct($path, $siteId);
	}

	public function create()
	{
		if ($this->isExists())
			return;

		$arMissingDirs = array($this->getName());
		$dir = $this->getDirectory();
		while (!$dir->isExists())
		{
			$arMissingDirs[] = $dir->getName();
			$dir = $dir->getDirectory();
		}

		$arMissingDirs = array_reverse($arMissingDirs);
		foreach ($arMissingDirs as $dirName)
			$dir = $dir->createSubdirectory($dirName);
	}

	/**
	 * @return FileSystemEntry[]
	 */
	abstract public function getChildren();

	/**
	 * @param string $path
	 * @return DirectoryEntry
	 */
	abstract public function createSubdirectory($name);

	public function isDirectory()
	{
		return true;
	}

	public function isFile()
	{
		return false;
	}

	public function isLink()
	{
		return false;
	}
}
