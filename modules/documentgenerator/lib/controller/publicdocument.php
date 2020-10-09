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

	public function showPdfAction(
		\Bitrix\DocumentGenerator\Document $document,
		$print = 'y',
		$pdfUrl = null,
		$width = 700,
		$height = 900,
		\CRestServer $restServer = null,
		$hash = ''
	)
	{
		return parent::showPdfAction(
			$document,
			$print,
			$this->getActionUri('getPdf', [
				'id' => $document->ID,
				'hash' => $hash,
			]
		));
	}

	public function getAction(\Bitrix\DocumentGenerator\Document $document, \CRestServer $restServer = null, $hash = '')
	{
		$allowedPublicData = [
			'publicUrl' => true,
			'pdfUrl' => true,
			'printUrl' => true,
			'imageUrl' => true,
			'pullTag' => true,
		];
		$result = parent::getAction($document, $restServer);
		if($result)
		{
			$result['document'] = array_intersect_key($result['document'], $allowedPublicData);
		}

		return $result;
	}
}