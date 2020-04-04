<?php

namespace Bitrix\Crm\Controller\DocumentGenerator;

use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Main\Engine\Response\DataType\ContentUri;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Error;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Result;
use Bitrix\Main\UI\PageNavigation;

class Document extends Base
{
	/**
	 * @return \Bitrix\DocumentGenerator\Controller\Base
	 */
	protected function getDocumentGeneratorController()
	{
		return new \Bitrix\DocumentGenerator\Controller\Document();
	}

	protected function getDocumentFileLink($documentId, $action, $updateTime = null)
	{
		if(!$updateTime)
		{
			$updateTime = time();
		}
		$link = UrlManager::getInstance()->create(static::CONTROLLER_PATH.'.document.'.$action, ['id' => $documentId, 'ts' => $updateTime]);
		$link = new ContentUri(UrlManager::getInstance()->getHostUrl().$link->getLocator());

		return $link;
	}

	/**
	 * @see \Bitrix\DocumentGenerator\Controller\Document::getAction()
	 * @param \Bitrix\DocumentGenerator\Document $document
	 * @return mixed
	 */
	public function getAction(\Bitrix\DocumentGenerator\Document $document)
	{
		$result = $this->proxyAction('getAction', [$document]);

		$data = false;
		if($result instanceof Result)
		{
			$data = $result->getData();
		}
		elseif(is_array($result))
		{
			$data = $result;
		}
		if($data)
		{
			$data['document'] = $this->prepareDocumentData($data['document']);

			if($result instanceof Result)
			{
				$result->setData($data);
			}
			else
			{
				$result = $data;
			}
		}

		return $result;
	}

	/**
	 * @see \Bitrix\DocumentGenerator\Controller\Document::listAction()
	 * @param array $select
	 * @param array|null $order
	 * @param array|null $filter
	 * @param PageNavigation|null $pageNavigation
	 * @return Page
	 */
	public function listAction(array $select = ['*'], array $order = null, array $filter = null, PageNavigation $pageNavigation = null)
	{
		if(!is_array($filter))
		{
			$filter = [];
		}
		$filter['template.moduleId'] = static::MODULE_ID;

		if(is_array($select) && in_array('entityId', $select))
		{
			$select[] = 'value';
			unset($select[array_search('entityId', $select)]);
		}

		$providersMap = DocumentGeneratorManager::getInstance()->getCrmOwnerTypeProvidersMap();
		if(is_array($filter))
		{
			if(isset($filter['entityTypeId']))
			{
				$filterMap = array_map(function($item)
				{
					return str_replace('\\', '\\\\', strtolower($item));
				}, $providersMap);
				$filter['provider'] = str_ireplace(array_keys($providersMap), $filterMap, $filter['entityTypeId']);
				unset($filter['entityTypeId']);
			}
			if(isset($filter['entityId']))
			{
				$filter['value'] = $filter['entityId'];
				unset($filter['entityId']);
			}
		}
		/** @var Page $result */
		$result = $this->proxyAction('listAction', [$select, $order, $filter, $pageNavigation]);
		$documents = $result->getItems();
		foreach($documents as $key => &$document)
		{
			$document = $this->prepareDocumentData($document);
			$result->offsetSet($key, $document);
		}

		return $result;
	}

	/**
	 * @see \Bitrix\DocumentGenerator\Controller\Document::deleteAction()
	 * @param \Bitrix\DocumentGenerator\Document $document
	 * @return mixed
	 */
	public function deleteAction(\Bitrix\DocumentGenerator\Document $document)
	{
		return $this->proxyAction('deleteAction', [$document]);
	}

	/**
	 * @see \Bitrix\DocumentGenerator\Controller\Document::addAction()
	 * @param \Bitrix\DocumentGenerator\Template $template
	 * @param $entityTypeId
	 * @param $entityId
	 * @param array $values
	 * @param int $stampsEnabled
	 * @param array $fields
	 * @return bool|mixed
	 */
	public function addAction(\Bitrix\DocumentGenerator\Template $template, $entityTypeId, $entityId, array $values = [], $stampsEnabled = 0, array $fields = [])
	{
		$providersMap = DocumentGeneratorManager::getInstance()->getCrmOwnerTypeProvidersMap();
		if(!isset($providersMap[$entityTypeId]))
		{
			$this->errorCollection[] = new Error('No provider for entityTypeId');
			return null;
		}

		$result = $this->proxyAction('addAction', [$template, $providersMap[$entityTypeId], $entityId, $values, $stampsEnabled, $fields]);
		if(is_array($result))
		{
			$result['document'] = $this->prepareDocumentData($result['document']);
		}

		return $result;
	}

	/**
	 * @see \Bitrix\DocumentGenerator\Controller\Document::updateAction()
	 * @param \Bitrix\DocumentGenerator\Document $document
	 * @param array $values
	 * @param int $stampsEnabled
	 * @return array
	 */
	public function updateAction(\Bitrix\DocumentGenerator\Document $document, array $values = [], $stampsEnabled = 1)
	{
		$result = $this->proxyAction('updateAction', [$document, $values, $stampsEnabled]);

		if(is_array($result))
		{
			$result['document'] = $this->prepareDocumentData($result['document']);
		}

		return $result;
	}

	/**
	 * @see \Bitrix\DocumentGenerator\Controller\Document::getFieldsAction()
	 * @param \Bitrix\DocumentGenerator\Document $document
	 * @param array $values
	 * @return array|false
	 */
	public function getFieldsAction(\Bitrix\DocumentGenerator\Document $document, array $values = [])
	{
		return $this->proxyAction('getFieldsAction', [$document, $values]);
	}

	/**
	 * @see \Bitrix\DocumentGenerator\Controller\Document::enablePublicUrlAction()
	 * @param \Bitrix\DocumentGenerator\Document $document
	 * @param int $status
	 * @return array
	 */
	public function enablePublicUrlAction(\Bitrix\DocumentGenerator\Document $document, $status = 1)
	{
		return $this->proxyAction('enablePublicUrlAction', [$document, $status]);
	}

	/**
	 * @see \Bitrix\DocumentGenerator\Controller\Document::getImageAction()
	 * @param \Bitrix\DocumentGenerator\Document $document
	 * @return array
	 */
	public function getImageAction(\Bitrix\DocumentGenerator\Document $document)
	{
		return $this->proxyAction('getImageAction', [$document]);
	}

	/**
	 * @see \Bitrix\DocumentGenerator\Controller\Document::getPdfAction()
	 * @param \Bitrix\DocumentGenerator\Document $document
	 * @return array
	 */
	public function getPdfAction(\Bitrix\DocumentGenerator\Document $document)
	{
		return $this->proxyAction('getPdfAction', [$document]);
	}

	/**
	 * @see \Bitrix\DocumentGenerator\Controller\Document::getFileAction()
	 * @param \Bitrix\DocumentGenerator\Document $document
	 * @return array
	 */
	public function downloadAction(\Bitrix\DocumentGenerator\Document $document)
	{
		return $this->proxyAction('getFileAction', [$document]);
	}

	/**
	 * @see \Bitrix\DocumentGenerator\Controller\Document::uploadAction()
	 * @param array $fields
	 * @param \CRestServer $restServer
	 * @return array|null
	 * @throws \Exception
	 */
	public function uploadAction(array $fields, \CRestServer $restServer)
	{
		$emptyFields = $this->checkArrayRequiredParams($fields, ['entityTypeId', 'fileContent', 'region', 'entityId', 'title', 'number']);
		if(!empty($emptyFields))
		{
			$this->errorCollection[] = new Error('Empty required fields: '.implode(', ', $emptyFields));
			return null;
		}

		$providersMap = DocumentGeneratorManager::getInstance()->getCrmOwnerTypeProvidersMap();
		if(!isset($providersMap[$fields['entityTypeId']]))
		{
			$this->errorCollection[] = new Error('No provider for entityTypeId');
			return null;
		}
		$fields['providerClassName'] = $providersMap[$fields['entityTypeId']];
		unset($fields['entityTypeId']);

		$fields['fileId'] = $this->uploadFile($fields['fileContent']);
		if(!$fields['fileId'])
		{
			return null;
		}
		unset($fields['fileContent']);

		$fields['pdfId'] = $this->uploadFile($fields['pdfContent'], [
			'fileParamName' => 'pdf',
			'required' => false,
			'fileName' => $fields['title'].'.pdf',
		]);
		unset($fields['pdfContent']);
		$fields['imageId'] = $this->uploadFile($fields['imageContent'], [
			'fileParamName' => 'image',
			'required' => false,
			'fileName' => $fields['title'].'.jpg',
		]);
		unset($fields['imageContent']);
		$fields['moduleId'] = static::MODULE_ID;
		$fields['value'] = $fields['entityId'];
		unset($fields['entityId']);

		$result = $this->proxyAction('uploadAction', [$fields, $restServer]);

		if(is_array($result))
		{
			$result['document'] = $this->prepareDocumentData($result['document']);
		}

		return $result;
	}

	/**
	 * @see \Bitrix\DocumentGenerator\Controller\Document::showPdfAction()
	 *
	 * @param \Bitrix\DocumentGenerator\Document $document
	 * @param string $print
	 * @param int $width
	 * @param int $height
	 * @return HttpResponse|array
	 */
	public function showPdfAction(\Bitrix\DocumentGenerator\Document $document, $print = 'n', $width = 700, $height = 900)
	{
		$response = new HttpResponse();
		if($document->PDF_ID > 0)
		{
			global $APPLICATION;
			ob_start();
			$APPLICATION->IncludeComponent(
				'bitrix:pdf.viewer',
				'',
				[
					'PATH' => $this->getDocumentFileLink($document->ID, 'getPdf', $document->getUpdateTime()->getTimestamp()),
					'IFRAME' => ($print === 'y' ? 'Y' : 'N'),
					'PRINT' => ($print === 'y' ? 'Y' : 'N'),
					'TITLE' => $document->getTitle(),
					'WIDTH' => $width,
					'HEIGHT' => $height,
				]
			);
			$response->setContent(ob_get_contents());
			ob_end_clean();
		}
		else
		{
			$this->errorCollection[] = new Error('No pdf for this document');
		}
		if($print === 'y')
		{
			return $response;
		}
		else
		{
			return [
				'html' => $response->getContent(),
			];
		}
	}

	/**
	 * @param array $data
	 * @return array
	 */
	protected function prepareDocumentData(array $data)
	{
		if(isset($data['imageUrl']) && !empty($data['imageUrl']))
		{
			$data['imageUrl'] = $this->getDocumentFileLink($data['id'], 'getImage', $data['updateTime']);
		}
		if(isset($data['pdfUrl']) && !empty($data['pdfUrl']))
		{
			$data['pdfUrl'] = $this->getDocumentFileLink($data['id'], 'getPdf', $data['updateTime']);
		}
		$data['downloadUrl'] = $this->getDocumentFileLink($data['id'], 'download', $data['updateTime']);
		if(isset($data['value']))
		{
			$data['entityId'] = $data['value'];
			unset($data['value']);
		}
		if(isset($data['provider']))
		{
			$providersMap = DocumentGeneratorManager::getInstance()->getCrmOwnerTypeProvidersMap();
			$data['entityTypeId'] = str_ireplace(array_values($providersMap), array_keys($providersMap), $data['provider']);
			unset($data['provider']);
		}
		if(isset($data['printUrl']))
		{
			unset($data['printUrl']);
		}

		return $data;
	}
}
