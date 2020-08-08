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
			if (is_dir($this->updaterPath . '/install/public/bitrix24'))
			{
				if (is_dir($this->kernelPath . '/wizards/bitrix/bitrix24') || isModuleInstalled('bitrix24'))
				{
					if (!is_dir($this->kernelPath . '/wizards/bitrix/portal'))
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

			if (is_dir($this->updaterPath . '/install/public/pub'))
			{
				\CUpdateSystem::copyDirFiles($this->updaterPath.'/install/public/pub', $this->documentRoot.'/pub', $error);
			}

			if (!empty($this->params['personal']) && is_array($this->params['personal']))
			{
				if (is_dir($this->kernelPath . '/wizards/bitrix/portal'))
				{
					$this->syncPersonal('portal', $this->params['personal']);
				}
			}
		}

		if ($this->updater->canUpdateKernel())
		{
			foreach (array('portal', 'portal_clear') as $item)
			{
				if (is_dir($this->updaterPath . '/install/wizards/bitrix/'.$item))
				{
					\CUpdateSystem::deleteDirFilesEx($this->updaterPath.'/install/wizards/bitrix/'.$item);
				}

				if (is_dir($this->kernelPath . '/modules/intranet/install/wizards/bitrix/'.$item))
				{
					\CUpdateSystem::deleteDirFilesEx($this->kernelPath.'/modules/intranet/install/wizards/bitrix/'.$item);
				}
			}
		}
	}

	protected function syncPersonal($wizard, array $params)
	{
		foreach (\CUtil::getSitesByWizard($wizard) as $site)
		{
			$siteDir = ($site['DIR'] <> '' ? $site['DIR'] : '/');
			$documentRoot = ($site['DOC_ROOT'] <> '' ? $site['DOC_ROOT'] : $this->documentRoot);

			foreach ($params as $from => $to)
			{
				$fromPath = preg_replace('#^install/+#', '', trim($from, '/'));

				if (!preg_match(sprintf('#^wizards/bitrix/%s#', preg_quote($wizard, '#')), $fromPath))
				{
					continue;
				}

				$toPath = is_callable($to) ? $to($site, $from) : $to;

				if (false === $toPath || is_null($toPath))
				{
					continue;
				}

				$fromPath = $this->kernelPath . '/' . trim($fromPath, '/');
				$toPath = $documentRoot . $siteDir . trim($toPath, '/');

				if (file_exists($fromPath))
				{
					\CUpdateSystem::copyDirFiles($fromPath, $toPath, $error);

					require_once $this->kernelPath . '/modules/main/classes/general/wizard_util.php';

					if (is_dir($toPath))
					{
						\CWizardUtil::replaceMacrosRecursive($toPath, array('SITE_DIR' => $siteDir));
					}
					else
					{
						\CWizardUtil::replaceMacros($toPath, array('SITE_DIR' => $siteDir));
					}
				}
			}
		}
	}

	protected function syncKernel($fromPath, $toPath)
	{
		foreach (static::getSubdirs($this->updaterPath . '/' . $fromPath) as $item)
		{
			$fromItemPath = $fromPath . '/' . $item;
			$toItemPath  = $toPath . '/' . $item;

			if (!is_dir($this->updaterPath . '/' . $fromItemPath))
			{
				continue;
			}

			if (!is_dir($this->kernelPath . '/' . $toItemPath))
			{
				if (empty($this->params[$fromItemPath]['new']) && empty($this->params['kernel'][$fromItemPath]['new']))
				{
					continue;
				}

				if (file_exists($this->kernelPath . '/' . $toItemPath))
				{
					continue;
				}
			}

			$this->updater->copyFiles($fromItemPath, $toItemPath);
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
				{
					continue;
				}

				if (is_dir($path . '/' . $item))
				{
					$result[] = $item;
				}
			}
		}

		return $result;
	}

}
