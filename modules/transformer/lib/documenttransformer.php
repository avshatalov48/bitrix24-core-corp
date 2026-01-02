<?php

namespace Bitrix\Transformer;

/**
 * Class Document
 * High-level logic to work with commands for documents.
 *
 * Make transformation of a document (.doc, .xls, .pdf and others formats) supported by Libre Office
 * Correct transformation:
 * .doc|.docx|.odt => pdf|jpg|txt|text
 * .xls|.xlsx|.ods => pdf|jpg
 * .pdf => jpg.
 *
 * @package Bitrix\Transformer
 */
class DocumentTransformer extends FileTransformer
{
	const PDF = 'pdf';
	const TXT = 'txt';
	const TEXT = 'text';
	const CSV = 'csv';

	protected function getCommandName()
	{
		return WellKnownControllerCommand::Document->value;
	}

	protected function getFileTypeName()
	{
		return 'Document';
	}
}
