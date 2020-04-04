<?php
namespace Bitrix\Landing\Zip;

use \Bitrix\Landing\Site as SiteCore;
use \Bitrix\Landing\File;
use \Bitrix\Main\Engine\Response\Zip\Archive;
use \Bitrix\Main\Engine\Response\Zip\ArchiveEntry;

class Site
{
	/**
	 * Export site to zip.
	 * @param int $id Site id.
	 * @return Archive
	 */
	public static function export($id)
	{
		$id = intval($id);
		if (Config::serviceEnabled() && SiteCore::ping($id))
		{
			// export in tmp file
			$tmpDir = \CTempFile::getDirectoryName(
				1, 'landing_site_' . $id
			);
			$jsonFile = $tmpDir . 'site_' . $id . '.json';
			$export = \Bitrix\Landing\Site::fullExport(
				$id,
				['edit_mode' => 'Y']
			);

			// gets file ids from export
			$files = File::getFilesFromSite($id);
			foreach ($export['items'] as $landing)
			{
				$files = array_merge($files, File::getFilesFromLanding(
					$landing['old_id']
				));
				if (!isset($landing['items']))
				{
					continue;
				}
				foreach ($landing['items'] as $block)
				{
					$files = array_merge($files, File::getFilesFromBlock(
						$block['old_id']
					));
				}
				unset($block);
			}
			$files = array_unique($files);

			// flush zip to client
			$zip = new Archive('site_' . $id . '.zip');
			$zip->addEntry(
				ArchiveEntry::createFromFilePath($jsonFile, "/upload/0/site_{$id}.json")
			);
			$export['files'] = [];
			if ($files)
			{
				foreach ($files as $fid)
				{
					$entry = ArchiveEntry::createFromFileId($fid, 'landing');
					if ($entry)
					{
						$entry->setName("/upload/{$fid}/{$entry->getName()}");
						$export['files'][$fid] = $entry->getName();

						$zip->addEntry($entry);
					}
				}
			}

			// main manifest file
			\Bitrix\Main\IO\File::putFileContents(
				$jsonFile,
				\CUtil::phpToJSObject($export)
			);

			return $zip;
		}
	}
}