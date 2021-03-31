<?php

namespace Bitrix\Rest\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Response\Zip\Archive;
use Bitrix\Main\Engine\Response\Zip\ArchiveEntry;
use Bitrix\Main\Localization\Loc;
use Bitrix\Rest\Configuration\Helper;
use Bitrix\Rest\Configuration\Setting;
use Bitrix\Rest\Configuration\Structure;
use Bitrix\Rest\Configuration\Manifest;

Loc::loadLanguageFile(__FILE__);

class Configuration extends Controller
{
	/**
	 * Download zip export.
	 * @return Archive
	 */
	public function downloadAction()
	{
		if (Helper::getInstance()->enabledZipMod())
		{
			$postfix = $this->getRequest()->getQuery('postfix');
			if (!empty($postfix))
			{
				$context = Helper::getInstance()->getContextUser($postfix);
				$setting = new Setting($context);
				$access = Manifest::checkAccess(Manifest::ACCESS_TYPE_EXPORT, $setting->get(Setting::MANIFEST_CODE));
				if ($access['result'] === true)
				{
					$structure = new Structure($context);

					$name = $structure->getArchiveName();
					if(empty($name))
					{
						$name = Helper::DEFAULT_ARCHIVE_NAME;
					}
					$name .= '.' . Helper::DEFAULT_ARCHIVE_FILE_EXTENSIONS;

					$archive = new Archive($name);

					$files = [];
					$fileList = $structure->getFileList();
					if (is_array($fileList))
					{
						$folderName = Helper::STRUCTURE_FILES_NAME;
						foreach ($fileList as $file)
						{
							$id = (int)$file['ID'];
							$entry = ArchiveEntry::createFromFileId($id);
							if ($entry)
							{
								$files[$id] = array_merge(
									[
										'NAME' => $entry->getName(),
									],
									$file
								);
								$entry->setName($folderName . '/' . $id);
								$archive->addEntry($entry);
							}
						}
					}

					if ($files)
					{
						$structure->saveContent(false, Helper::STRUCTURE_FILES_NAME, $files);
					}

					$folderFiles = $structure->getConfigurationFileList();
					foreach ($folderFiles as $file)
					{
						$entry = ArchiveEntry::createFromFileId((int)$file['ID']);
						if ($entry)
						{
							$entry->setName($file['NAME']);
							$archive->addEntry($entry);
						}
					}

					return $archive;
				}
			}
		}

		return null;
	}

	public function getDefaultPreFilters()
	{
		return [
			new ActionFilter\Authentication(),
			new ActionFilter\Scope(ActionFilter\Scope::NOT_REST),
		];
	}
}