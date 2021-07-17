<?php

namespace Bitrix\Disk\Document\OnlyOffice;

use Bitrix\Disk\Document;
use Bitrix\Main\IO\File;

class BlankFileData extends Document\BlankFileData
{
	private $langCode;

	public function __construct($type, string $langCode = null)
	{
		if ($langCode)
		{
			$this->langCode = Document\Language::getIso639Code($langCode);
		}

		parent::__construct($type);
	}

	protected function getTypeList()
	{
		$list = parent::getTypeList();

		$pathToBlankDocsFolder = static::getPathToBlankDocsFolder();
		$docxPath = $pathToBlankDocsFolder . 'blank-onlyoffice.docx';
		if ($this->langCode && File::isFileExists($pathToBlankDocsFolder . "blank-onlyoffice-{$this->langCode}.docx"))
		{
			$docxPath = $pathToBlankDocsFolder . "blank-onlyoffice-{$this->langCode}.docx";
		}

		$list['docx']['src'] = $docxPath;
		$list['pptx']['src'] = $pathToBlankDocsFolder . 'blank-onlyoffice.pptx';
		$list['xlsx']['src'] = $pathToBlankDocsFolder . 'blank-onlyoffice.xlsx';

		return $list;
	}
}