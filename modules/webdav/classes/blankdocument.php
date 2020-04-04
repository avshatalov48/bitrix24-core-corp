<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

IncludeModuleLangFile(__FILE__);

class CWebDavBlankDocument
{
	protected static $typeList = array();
	protected $type = null;

	public function __construct($type)
	{
		$type = strtolower($type);
		$type = trim($type, '.');

		if(!$this->issetType($type))
		{
			throw new Exception('Bad type');
		}

		$this->type = $this->getType($type);

		return;
	}

	protected static function getPathToBlankDocsFolder()
	{
		return realpath(__DIR__ . '/../tools/blankdocs/') . '/';
	}

	public function getExtension()
	{
		return $this->type['ext'];
	}

	public function getSrc()
	{
		return $this->type['src'];
	}

	public function getFileSize()
	{
		return file_exists($this->type['src'])? filesize($this->type['src']) : 0;
	}

	public function getMimeType()
	{
		return CWebDavBase::get_mime_type($this->type['ext']);
	}

	public function getNewFileName()
	{
		return $this->type['newFileName'];
	}

	public static function getTypeList()
	{
		if(!empty(static::$typeList))
		{
			return static::$typeList;
		}
		static::$typeList['docx'] = array(
			'newFileName' => GetMessage('WD_BLANK_DOC_TYPE_NEW_FILE_DOCX'),
			'name' => GetMessage('WD_BLANK_DOC_TYPE_DOCX'),
			'src' => static::getPathToBlankDocsFolder() . 'blank.docx',
			'ext' => '.docx',
		);
		static::$typeList['pptx'] = array(
			'newFileName' => GetMessage('WD_BLANK_DOC_TYPE_NEW_FILE_PPTX'),
			'name' => GetMessage('WD_BLANK_DOC_TYPE_PPTX'),
			'src' => static::getPathToBlankDocsFolder() . 'blank.pptx',
			'ext' => '.pptx',
		);
		static::$typeList['xlsx'] = array(
			'newFileName' => GetMessage('WD_BLANK_DOC_TYPE_NEW_FILE_XLSX'),
			'name' => GetMessage('WD_BLANK_DOC_TYPE_XLSX'),
			'src' => static::getPathToBlankDocsFolder() . 'blank.xlsx',
			'ext' => '.xlsx',
		);

		return static::$typeList;
	}

	public static function getType($type)
	{
		$typeList = static::getTypeList();

		return isset($typeList[$type])? $typeList[$type] : array();
	}

	public static function issetType($type)
	{
		$typeList = static::getTypeList();

		return isset($typeList[$type]);
	}
}