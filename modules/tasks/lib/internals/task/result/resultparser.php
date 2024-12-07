<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Task\Result;

class ResultParser extends \CTextParser
{
	private $ufManager;
	private $result;

	private const DEFAULT_ALLOW = [
		"HTML" => "N",
		"ANCHOR" => "Y",
		"BIU" => "Y",
		"IMG" => "Y",
		"QUOTE" => "Y",
		"CODE" => "Y",
		"FONT" => "Y",
		"LIST" => "Y",
		"EMOJI" => "Y",
		"SMILES" => "Y",
		"CLEAR_SMILES" => "N",
		"NL2BR" => "N",
		"VIDEO" => "Y",
		"TABLE" => "Y",
		"CUT_ANCHOR" => "N",
		"SHORT_ANCHOR" => "N",
		"TEXT_ANCHOR" => "Y",
		"ALIGN" => "Y",
		"USER" => "Y",
		'PROJECT' => 'Y',
		'DEPARTMENT' => 'Y',
		"P" => "Y",
		"TAG" => "N",
		"SPOILER" => "Y",
	];

	public function __construct()
	{
		global $USER_FIELD_MANAGER;

		parent::__construct();

		$this->ufManager = $USER_FIELD_MANAGER;
	}

	/**
	 * @param Result $result
	 * @return $this
	 */
	public function setResult(Result $result): self
	{
		$this->result = $result;
		return $this;
	}

	public function convertText($text, $attributes = [])
	{
		$this->setAllow();
		return parent::convertText($text, $attributes);
	}

	/**
	 *
	 */
	private function setAllow()
	{
		$this->allow = static::DEFAULT_ALLOW;

		if (!$this->result)
		{
			return;
		}

		$files = $this->result->get(\Bitrix\Tasks\Internals\Task\Result\ResultTable::UF_FILE_NAME);
		if (empty($files))
		{
			return;
		}

		$uf = $this->ufManager->getUserFields(ResultTable::getUfId());
		$uf[ResultTable::UF_FILE_NAME]['VALUE'] = $files;

		$this->allow["USERFIELDS"] = [
			ResultTable::UF_FILE_NAME => $uf[ResultTable::UF_FILE_NAME],
		];
	}

	// /**
	//  * @param string $text
	//  * @return string
	//  */
	// public function parseInlineFiles(string $text)
	// {
	// 	if (!Loader::includeModule('disk'))
	// 	{
	// 		return $text;
	// 	}
	//
	// 	$objectIds = [];
	// 	$attachedIds = [];
	//
	// 	if (preg_match_all("#\\[disk file id=(n\\d+)\\]#isu", $text, $matches))
	// 	{
	// 		$objectIds = array_map(function($a) { return (int)mb_substr($a, 1); }, $matches[1]);
	// 	}
	//
	// 	if (preg_match_all("#\\[disk file id=(\\d+)\\]#isu", $text, $matches))
	// 	{
	// 		$attachedIds = array_map(function($a) { return (int)$a; }, $matches[1]);
	// 	}
	//
	// 	if (
	// 		empty($objectIds)
	// 		&& empty($attachedIds)
	// 	)
	// 	{
	// 		return $text;
	// 	}
	//
	// 	$imageList = $this->loadImages($objectIds, $attachedIds);
	//
	// 	if (empty($imageList))
	// 	{
	// 		return $text;
	// 	}
	//
	// 	return $text;
	// }
	//
	// private function loadImages(array $objectIds, array $attachIds): array
	// {
	// 	$filter = array(
	// 		'=OBJECT.TYPE_FILE' => \Bitrix\Disk\TypeFile::IMAGE
	// 	);
	//
	// 	$subFilter = [];
	// 	if (!empty($objectIds))
	// 	{
	// 		$subFilter['@OBJECT_ID'] = $objectIds;
	// 	}
	// 	elseif (!empty($attachIds))
	// 	{
	// 		$subFilter['@ID'] = $attachIds;
	// 	}
	//
	// 	if (count($subFilter) > 1)
	// 	{
	// 		$subFilter['LOGIC'] = 'OR';
	// 		$filter[] = $subFilter;
	// 	}
	// 	else
	// 	{
	// 		$filter = array_merge($filter, $subFilter);
	// 	}
	//
	// 	$res = AttachedObjectTable::getList([
	// 		'filter' => $filter,
	// 		'select' => ['ID', 'ENTITY_ID']
	// 	]);
	//
	// 	$imageList = [];
	// 	while ($row = $res->fetch())
	// 	{
	// 		/**
	// 		 * Add check in that $row['ENTITY_ID'] is equal to $resultId
	// 		 */
	// 		$imageList[] = (int)$row['ID'];
	// 	}
	//
	// 	return $imageList;
	// }
}