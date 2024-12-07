<?php

namespace Bitrix\BIConnector\Superset\Dashboard;

use Bitrix\BIConnector\Integration\Superset\Model\Dashboard;
use Bitrix\Main\Error;
use Bitrix\Main\IO;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class ScreenshotExporter
{
	private const FILE_FORMAT_PDF = 'pdf';
	private const FILE_FORMAT_JPEG = 'jpeg';

	private static function saveBase64File(string $content, string $fileName, string $fileFormat): Result
	{
		$result = new Result();
		$content = base64_decode($content);

		$filePath = \CTempFile::getFileName(md5(uniqid('bic', true)));
		$file = new IO\File($filePath);
		$contentSize = $file->putContents($content);

		$file = \CFile::makeFileArray($filePath);
		$file['MODULE_ID'] = 'biconnector';
		$file['name'] = "{$fileName}.{$fileFormat}";

		if (\CFile::checkFile($file, strExt: $fileFormat))
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_PDF_EXPORT_ERROR_WRONG_FILE_TYPE')));

			return $result;
		}

		$fileId = \CFile::saveFile($file, 'biconnector/dashboard_screenshot');
		if ((int)$fileId <= 0)
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_PDF_EXPORT_ERROR_FILE_SAVE')));

			return $result;
		}
		$newFile = \CFile::getByID($fileId)->fetch();
		$responseData = [
			'filePath' => $newFile['SRC'],
			'contentSize' => $contentSize,
		];
		$result->setData($responseData);

		return $result;
	}

	public static function saveDashboardScreenshot(Dashboard $dashboard, string $content, string $fileType): Result
	{
		$result = new Result();
		if ($fileType !== self::FILE_FORMAT_PDF && $fileType !== self::FILE_FORMAT_JPEG)
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_PDF_EXPORT_ERROR_WRONG_FILE_TYPE')));

			return $result;
		}
		$fileName = $dashboard->getTitle();

		return self::saveBase64File($content, $fileName, $fileType);
	}
}
