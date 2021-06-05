<?php

namespace Bitrix\Disk\Document\OnlyOffice;

use Bitrix\Disk\Document;

class BlankFileData extends Document\BlankFileData
{
	protected function getTypeList()
	{
		$list = parent::getTypeList();

		$list['docx']['src'] = static::getPathToBlankDocsFolder() . 'blank-onlyoffice.docx';
		$list['pptx']['src'] = static::getPathToBlankDocsFolder() . 'blank-onlyoffice.pptx';
		$list['xlsx']['src'] = static::getPathToBlankDocsFolder() . 'blank-onlyoffice.xlsx';

		return $list;
	}
}