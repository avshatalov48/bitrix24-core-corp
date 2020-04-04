<?php

namespace Bitrix\Disk\Document;

use Bitrix\Disk\TypeFile;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\IO;

Loc::loadMessages(__FILE__);

class BlankFileData extends FileData
{
	public function __construct($type)
	{
		parent::__construct();

		$type = trim(strtolower($type), '.');
		if (!$this->issetType($type))
		{
			throw new SystemException("Could not find type '{$type}' in BlankFile");
		}

		$typeData = $this->getType($type);

		$this->name = $typeData['newFileName'] . $typeData['ext'];
		$this->mimeType = TypeFile::getMimeTypeByFilename($this->name);
		$this->src = $typeData['src'];
		$this->size = IO\File::isFileExists($typeData['src']) ? filesize($typeData['src']) : 0;
	}

	protected function issetType($type)
	{
		$typeList = $this->getTypeList();

		return isset($typeList[$type]);
	}

	protected function getTypeList()
	{
		return array(
			'docx' => array(
				'newFileName' => Loc::getMessage('DISK_BLANK_FILE_DATA_NEW_FILE_DOCX'),
				'name' => Loc::getMessage('DISK_BLANK_FILE_DATA_TYPE_DOCX'),
				'src' => static::getPathToBlankDocsFolder() . 'blank.docx',
				'ext' => '.docx',
			),
			'pptx' => array(
				'newFileName' => Loc::getMessage('DISK_BLANK_FILE_DATA_NEW_FILE_PPTX'),
				'name' => Loc::getMessage('DISK_BLANK_FILE_DATA_TYPE_PPTX'),
				'src' => static::getPathToBlankDocsFolder() . 'blank.pptx',
				'ext' => '.pptx',
			),
			'xlsx' => array(
				'newFileName' => Loc::getMessage('DISK_BLANK_FILE_DATA_NEW_FILE_XLSX'),
				'name' => Loc::getMessage('DISK_BLANK_FILE_DATA_TYPE_XLSX'),
				'src' => static::getPathToBlankDocsFolder() . 'blank.xlsx',
				'ext' => '.xlsx',
			)
		);
	}

	protected static function getPathToBlankDocsFolder()
	{
		return realpath(__DIR__ . '/blankdocs/') . '/';
	}

	protected function getType($type)
	{
		$typeList = $this->getTypeList();

		return isset($typeList[$type]) ? $typeList[$type] : array();
	}

} 