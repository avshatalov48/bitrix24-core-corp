<?php


namespace Bitrix\Disk\Document\CloudImport;


use Bitrix\Disk\Bitrix24Disk;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Main\IO;

final class TmpFile extends Bitrix24Disk\TmpFile
{
	protected static function prepareDataToInsertFromFileArray(array $fileData, array $data, ErrorCollection $errorCollection)
	{
		list($relativePath, $absolutePath) = self::generatePath();

		$file = new IO\File($fileData['tmp_name']);
		if(!$file->isExists())
		{
			$errorCollection->addOne(new Error('Could not find file', self::ERROR_EXISTS_FILE));
			return null;
		}
		if(!$file->rename($absolutePath))
		{
			$errorCollection->addOne(new Error('Could not move file', self::ERROR_MOVE_FILE));
			return null;
		}

		//now you can set CREATED_BY
		$data = array_intersect_key($data, array('CREATED_BY' => true, 'SIZE' => true,));

		return array_merge(array(
			'TOKEN' => static::generateTokenByPath($relativePath),
			'FILENAME' => $fileData['name'],
			'CONTENT_TYPE' => empty($fileData['type'])? \CFile::getContentType($absolutePath) : $fileData['type'],
			'PATH' => $relativePath,
			'BUCKET_ID' => '',
			'SIZE' => empty($fileData['size'])? '' : $fileData['size'],
			'RECEIVED_SIZE' => empty($fileData['size'])? '' : $fileData['size'],
			'WIDTH' => empty($fileData['width'])? '' : $fileData['width'],
			'HEIGHT' => empty($fileData['height'])? '' : $fileData['height'],
		), $data);
	}
}