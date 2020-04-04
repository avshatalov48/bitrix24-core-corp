<?php

/** @noinspection PhpUnusedParameterInspection */

namespace Bitrix\DocumentGenerator\Controller;

use Bitrix\DocumentGenerator\Engine\CheckHash;
use Bitrix\Rest\RestException;

class PublicDocument extends Document
{
	protected function init()
	{
		if($this->getScope() === static::SCOPE_REST)
		{
			throw new RestException('Wrong scope for current action');
		}

		parent::init();
	}

	/**
	 * @return array
	 */
	public function getDefaultPreFilters()
	{
		return [new CheckHash()];
	}

	public function getImageAction(\Bitrix\DocumentGenerator\Document $document, \CRestServer $restServer = null, $hash = '')
	{
		return parent::getImageAction($document);
	}

	public function getFileAction(\Bitrix\DocumentGenerator\Document $document, $fileName = '', \CRestServer $restServer = null, $hash = '')
	{
		return parent::getFileAction($document, $fileName);
	}

	public function getPdfAction(\Bitrix\DocumentGenerator\Document $document, $fileName = '', \CRestServer $restServer = null, $hash = '')
	{
		return parent::getPdfAction($document, $fileName);
	}

	public function showPdfAction(\Bitrix\DocumentGenerator\Document $document, $print = 'y', \CRestServer $restServer = null, $hash = '')
	{
		return parent::showPdfAction($document, $print, $this->getActionUri('getPdf', ['id' => $document->ID, 'hash' => $hash]));
	}
}