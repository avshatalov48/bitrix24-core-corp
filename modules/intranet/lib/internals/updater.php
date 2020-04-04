<?php

namespace Bitrix\Intranet\Internals;

class Updater
{

	protected $updater, $params;
	protected $documentRoot, $updaterPath, $kernelPath;

	public static function run(\CUpdater &$updater, $params = array())
	{
		$instance = new static($updater, $params);
		$instance->exec();
	}

	public function __construct(\CUpdater &$updater, $params = array())
	{
		$this->updater = $updater;
		$this->params  = $params;

		$this->documentRoot = $_SERVER['DOCUMENT_ROOT'];

		$this->updaterPath = $this->documentRoot . $updater->curModulePath;
		$this->kernelPath  = $this->documentRoot . $updater->kernelPath;
	}

	public function exec()
	{
		if ($this->updater->canUpdateKernel())
		{
			$this->syncKernel('install/wizards/bitrix', 'wizards/bitrix');
			$this->syncKernel('install/templates', 'templates');
		}

		if ($this->updater->canUpdatePersonalFiles())
		{
			if (is_dir($this->updaterPath.'/install/public/bitrix24'))
			{
				if (is_dir($this->kernelPath.'/wizards/bitrix/bitrix24') || isModuleInstalled('bitrix24'))
				{
					if (!is_dir($this->kernelPath.'/wizards/bitrix/portal'))
					{
						\CUpdateSystem::copyDirFiles($this->updaterPath.'/install/public/bitrix24', $this->documentRoot, $error);

						if (defined('BX_COMP_MANAGED_CACHE'))
						{
							global $CACHE_MANAGER;
							$CACHE_MANAGER->clearByTag('bitrix24_left_menu');
						}
					}
				}
			}

			if (is_dir($this->updaterPath.'/install/public/pub'))
				\CUpdateSystem::copyDirFiles($this->updaterPath.'/install/public/pub', $this->documentRoot.'/pub', $error);
		}

		if ($this->updater->canUpdateKernel())
		{
			foreach (array('portal', 'portal_clear') as $item)
			{
				if (is_dir($this->updaterPath.'/install/wizards/bitrix/'.$item))
					\CUpdateSystem::deleteDirFilesEx($this->updaterPath.'/install/wizards/bitrix/'.$item);
				if (is_dir($this->kernelPath.'/modules/intranet/install/wizards/bitrix/'.$item))
					\CUpdateSystem::deleteDirFilesEx($this->kernelPath.'/modules/intranet/install/wizards/bitrix/'.$item);
			}
		}
	}

	protected function syncKernel($fromPath, $toPath)
	{
		foreach (static::getSubdirs($this->updaterPath.'/'.$fromPath) as $item)
		{
			$updaterItemPath = $fromPath . '/' . $item;
			$kernelItemPath  = $toPath . '/' . $item;

			if (!is_dir($this->updaterPath.'/'.$updaterItemPath))
				continue;

			if (!is_dir($this->kernelPath.'/'.$kernelItemPath))
			{
				if (empty($this->params[$updaterItemPath]['new']) || file_exists($this->kernelPath.'/'.$kernelItemPath))
					continue;
			}

			$this->updater->copyFiles($updaterItemPath, $kernelItemPath);
		}
	}

	protected static function getSubdirs($path)
	{
		$result = array();

		if (is_dir($path) && ($handle = @opendir($path)) !== false)
		{
			while (($item = readdir($handle)) !== false)
			{
				if ($item == '.' || $item == '..')
					continue;

				if (is_dir($path.'/'.$item))
					$result[] = $item;
			}
		}

		return $result;
	}

}
