<?php

/** @noinspection PhpUnusedParameterInspection */

namespace Bitrix\DocumentGenerator\Controller;

use Bitrix\DocumentGenerator\Body\Docx;
use Bitrix\DocumentGenerator\DataProvider\Rest;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Engine\CheckAccess;
use Bitrix\DocumentGenerator\Integration\Bitrix24Manager;
use Bitrix\DocumentGenerator\Model\DocumentTable;
use Bitrix\DocumentGenerator\Model\FileTable;
use Bitrix\DocumentGenerator\Model\TemplateTable;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\Binder;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Engine\Response\DataType\ContentUri;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Error;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Rest\APAuth\PasswordTable;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\OAuth\Auth;

class Document extends Base
{
	/**
	 * @return array
	 */
	public function getDefaultPreFilters()
	{
		$preFilters = parent::getDefaultPreFilters();
		$preFilters[] = new CheckAccess();

		return $preFilters;
	}

	protected function init()
	{
		parent::init();

		Binder::registerParameterDependsOnName(
			\Bitrix\DocumentGenerator\Template::class,
			function($className, $id)
			{
				/** @var \Bitrix\DocumentGenerator\Template $className */
				return $className::loadById($id);
			},
			function()
			{
				return 'templateId';
			}
		);
	}


	/**
	 * @return array
	 */
	public function configureActions()
	{
		$configureActions = parent::configureActions();
		$configureActions['getImage'] = [
			'-prefilters' => [
				Csrf::class
			]
		];
		$configureActions['getFile'] = [
			'-prefilters' => [
				Csrf::class
			]
		];
		$configureActions['getPdf'] = [
			'-prefilters' => [
				Csrf::class
			]
		];
		$configureActions['showPdf'] = [
			'-prefilters' => [
				Csrf::class
			]
		];

		return $configureActions;
	}

	/**
	 * @param \Bitrix\DocumentGenerator\Document $document
	 * @param \CRestServer|null $restServer
	 * @return null
	 */
	public function getImageAction(\Bitrix\DocumentGenerator\Document $document, \CRestServer $restServer = null)
	{
		if($document->IMAGE_ID > 0)
		{
			return FileTable::download($document->IMAGE_ID);
		}
		else
		{
			Loc::loadLanguageFile(__FILE__);
			$this->errorCollection[] = new Error(Loc::getMessage('DOCGEN_CONTROLLER_DOCUMENT_NO_IMAGE'));
		}

		return null;
	}

	/**
	 * @param \Bitrix\DocumentGenerator\Document $document
	 * @param string $fileName
	 * @param \CRestServer|null $restServer
	 * @return null
	 */
	public function getFileAction(\Bitrix\DocumentGenerator\Document $document, $fileName = '', \CRestServer $restServer = null)
	{
		if($fileName === '')
		{
			$fileName = $document->getFileName();
		}
		return FileTable::download($document->FILE_ID, $fileName);
	}

	/**
	 * @param \Bitrix\DocumentGenerator\Document $document
	 * @param string $fileName
	 * @param \CRestServer|null $restServer
	 * @return null
	 */
	public function getPdfAction(\Bitrix\DocumentGenerator\Document $document, $fileName = '', \CRestServer $restServer = null)
	{
		if($document->PDF_ID > 0)
		{
			if($fileName === '')
			{
				$fileName = $document->getFileName('pdf');
			}
			return FileTable::download($document->PDF_ID, $fileName);
		}
		else
		{
			Loc::loadLanguageFile(__FILE__);
			$this->errorCollection[] = new Error(Loc::getMessage('DOCGEN_CONTROLLER_DOCUMENT_NO_PDF'));
		}

		return null;
	}

	/**
	 * @param \Bitrix\DocumentGenerator\Document $document
	 * @param string $print
	 * @param null $pdfUrl
	 * @param int $width
	 * @param int $height
	 * @param \CRestServer|null $restServer
	 * @return array|HttpResponse
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function showPdfAction(\Bitrix\DocumentGenerator\Document $document, $print = 'y', $pdfUrl = null, $width = 700, $height = 900, \CRestServer $restServer = null)
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
					'PATH' => $pdfUrl ? $pdfUrl : $document->getPdfUrl(),
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
			Loc::loadLanguageFile(__FILE__);
			$this->errorCollection[] = new Error(Loc::getMessage('DOCGEN_CONTROLLER_DOCUMENT_NO_PDF'));
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
	 * @param \Bitrix\DocumentGenerator\Document $document
	 * @param \CRestServer|null $restServer
	 * @return null|Result
	 */
	public function deleteAction(\Bitrix\DocumentGenerator\Document $document, \CRestServer $restServer = null)
	{
		$result = DocumentTable::delete($document->ID);
		if(!$result->isSuccess())
		{
			$this->errorCollection = $result->getErrorCollection();
		}
		return $result;
	}

	/**
	 * @param \Bitrix\DocumentGenerator\Template $template
	 * @param $providerClassName
	 * @param $value
	 * @param array $values
	 * @param int $stampsEnabled
	 * @param array $fields
	 * @param \CRestServer|null $restServer
	 * @return array|null
	 */
	public function addAction(\Bitrix\DocumentGenerator\Template $template, $providerClassName = null, $value = null, array $values = [], $stampsEnabled = 0, array $fields = [], \CRestServer $restServer = null)
	{
		if($restServer)
		{
			$providerClassName = Rest::class;
		}
		elseif(!$providerClassName)
		{
			$this->errorCollection[] = new Error('Empty required parameter "providerClassName"');
			return null;
		}
		if(!$value)
		{
			$this->errorCollection[] = new Error('Empty required parameter "value"');
			return null;
		}
		if($template->isDeleted())
		{
			$this->errorCollection[] = new Error('Cannot create document on deleted template');
			return null;
		}
		$template->setSourceType($providerClassName);
		$document = \Bitrix\DocumentGenerator\Document::createByTemplate($template, $value);
		if(!$document->hasAccess(Driver::getInstance()->getUserId()))
		{
			$this->errorCollection[] = new Error('Access denied', static::ERROR_ACCESS_DENIED);
			return null;
		}
		if(Bitrix24Manager::isEnabled() && Bitrix24Manager::isDocumentsLimitReached())
		{
			$this->errorCollection[] = new Error('Maximum count of documents has been reached', Bitrix24Manager::LIMIT_ERROR_CODE);
			return null;
		}
		$result = $document->enableStamps($stampsEnabled == 1)->setValues($values)->setFields($fields)->getFile(true, $this->getScope() === static::SCOPE_REST);
		if(!$result->isSuccess())
		{
			$this->errorCollection = $result->getErrorCollection();
			return null;
		}

		return ['document' => $result->getData()];
	}

	/**
	 * @param \Bitrix\DocumentGenerator\Document $document
	 * @param array $values
	 * @param int $stampsEnabled
	 * @param array $fields
	 * @param \CRestServer|null $restServer
	 * @return array|null
	 */
	public function updateAction(\Bitrix\DocumentGenerator\Document $document, array $values = [], $stampsEnabled = 1, array $fields = [], \CRestServer $restServer = null)
	{
		$result = $document->enableStamps($stampsEnabled == 1)->setFields($fields)->update($values, true, $this->getScope() === static::SCOPE_REST);
		if(!$result->isSuccess())
		{
			$this->errorCollection = $result->getErrorCollection();
			return null;
		}

		return ['document' => $result->getData()];
	}

	/**
	 * @param \Bitrix\DocumentGenerator\Document $document
	 * @param array $values
	 * @param \CRestServer|null $restServer
	 * @return array|null
	 */
	public function getFieldsAction(\Bitrix\DocumentGenerator\Document $document, array $values = [], \CRestServer $restServer = null)
	{
		$fields = $document->setValues($values)->setIsCheckAccess(true)->getFields([], true, true);
		foreach($fields as &$field)
		{
			$field = $this->convertKeysToCamelCase($field);
		}
		return ['documentFields' => $fields];
	}

	/**
	 * @param \Bitrix\DocumentGenerator\Document $document
	 * @param \CRestServer|null $restServer
	 * @return array|null
	 */
	public function getAction(\Bitrix\DocumentGenerator\Document $document, \CRestServer $restServer = null)
	{
		$result = $document->getFile();
		if($result->isSuccess())
		{
			return ['document' => $result->getData()];
		}
		else
		{
			$this->errorCollection = $result->getErrorCollection();
		}

		return null;
	}

	/**
	 * @param \Bitrix\DocumentGenerator\Document $document
	 * @param int $status
	 * @param \CRestServer|null $restServer
	 * @return array|null
	 */
	public function enablePublicUrlAction(\Bitrix\DocumentGenerator\Document $document, $status = 1, \CRestServer $restServer = null)
	{
		$result = $document->enablePublicUrl($status == 1);
		if($result->isSuccess())
		{
			return [
				'publicUrl' => $document->getPublicUrl(),
			];
		}
		else
		{
			$this->errorCollection = $result->getErrorCollection();
			return null;
		}
	}

	/**
	 * @param array $select
	 * @param array|null $order
	 * @param array|null $filter
	 * @param PageNavigation|null $pageNavigation
	 * @param \CRestServer|null $restServer
	 * @return Page
	 */
	public function listAction(array $select = ['*'], array $order = null, array $filter = null, PageNavigation $pageNavigation = null, \CRestServer $restServer = null)
	{
		$converter = new Converter(0);
		if($restServer)
		{
			if(!is_array($filter))
			{
				$filter = [];
			}
			$filter['template.moduleId'] = Driver::REST_MODULE_ID;
		}
		if(is_array($filter))
		{
			$filter = $converter->setFormat(Converter::TO_UPPER | Converter::KEYS | Converter::TO_SNAKE)->process($filter);
		}
		if(is_array($order))
		{
			$order = $converter->setFormat(Converter::TO_UPPER | Converter::KEYS | Converter::TO_SNAKE)->process($order);
		}
		if(is_array($select))
		{
			$select = $converter->setFormat(Converter::TO_UPPER | Converter::VALUES | Converter::TO_SNAKE)->process($select);
		}

		$documents = [];
		$documentList = DocumentTable::getList([
			'select' => $select,
			'filter' => $filter,
			'order' => $order,
			'offset' => $pageNavigation->getOffset(),
			'limit' => $pageNavigation->getLimit(),
		]);
		while($document = $documentList->fetch())
		{
			$document['DOWNLOAD_URL'] = $this->getDocumentFileLink($document['ID'], 'getfile', $document['updateTime']);
			$document['PDF_URL'] = $this->getDocumentFileLink($document['ID'], 'getpdf', $document['updateTime']);
			$document['IMAGE_URL'] = $this->getDocumentFileLink($document['ID'], 'getimage', $document['updateTime']);
			$values = $document['VALUES'];
			$document = $this->convertKeysToCamelCase($document);
			$document['values'] = $values;
			if(isset($values['stampsEnabled']) && $values['stampsEnabled'])
			{
				$document['stampsEnabled'] = true;
			}
			else
			{
				$document['stampsEnabled'] = false;
			}
			$documents[] = $document;
		}

		return new Page('documents', $documents, function() use ($filter)
		{
			return DocumentTable::getCount($filter);
		});
	}

	/**
	 * @param array $fields
	 * @param \CRestServer $restServer
	 * @return array|bool
	 */
	public function uploadAction(array $fields, \CRestServer $restServer)
	{
		$emptyFields = $this->checkArrayRequiredParams($fields, ['moduleId', 'providerClassName', 'fileId', 'region', 'value', 'title', 'number']);
		if(!empty($emptyFields))
		{
			$this->errorCollection[] = new Error('Empty required fields: '.implode(', ', $emptyFields));
			return null;
		}

		if(!Loader::includeModule($fields['moduleId']))
		{
			$this->errorCollection[] = new Error('Module '.$fields['moduleId'].' is not installed');
			return null;
		}

		if(!DataProviderManager::checkProviderName($fields['providerClassName']))
		{
			$this->errorCollection[] = new Error('Wrong provider '.$fields['providerClassName']);
			return null;
		}

		$restTemplate = $this->getRestTemplate($restServer, $fields['moduleId'], $fields['region']);
		if(!$restTemplate)
		{
			$this->errorCollection[] = new Error('Error getting template');
			return null;
		}
		$restTemplate->setSourceType($fields['providerClassName']);

		$result = \Bitrix\DocumentGenerator\Document::upload($restTemplate, $fields['value'], $fields['title'], $fields['number'], $fields['fileId'], $fields['pdfId'], $fields['imageId']);
		if($result->isSuccess())
		{
			return ['document' => $result->getData()];
		}
		else
		{
			$this->errorCollection->add($result->getErrors());
			return null;
		}
	}

	/**
	 * @param \CRestServer $restServer
	 * @param string $moduleId
	 * @param string $region
	 * @return \Bitrix\DocumentGenerator\Template|false
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Exception
	 */
	protected function getRestTemplate(\CRestServer $restServer, $moduleId, $region)
	{
		$appInfo = $this->getRestAppInfo($restServer);
		if(!$appInfo)
		{
			$this->errorCollection[] = new Error('Application not found');
			return false;
		}

		$templateId = 0;
		$template = TemplateTable::getList(['select' => ['ID'], 'order' => ['ID' => 'desc',],'filter' => ['MODULE_ID' => $moduleId, 'CODE' => $appInfo['CODE'], 'REGION' => $region]])->fetch();
		if(!$template)
		{
			$fileResult = FileTable::saveFile($this->generateStubFile());
			if(!$fileResult->isSuccess())
			{
				$this->errorCollection[] = new Error('Error generating file for template');
				return false;
			}
			$data = [
				'NAME' => $appInfo['TITLE'],
				'CODE' => $appInfo['CODE'],
				'REGION' => $region,
				'CREATED_BY' => CurrentUser::get()->getId(),
				'UPDATED_BY' => CurrentUser::get()->getId(),
				'MODULE_ID' => $moduleId,
				'FILE_ID' => $fileResult->getId(),
				'BODY_TYPE' => Docx::class,
				'IS_DELETED' => 'Y',
			];
			$addResult = TemplateTable::add($data);
			if($addResult->isSuccess())
			{
				$templateId = $addResult->getId();
			}
			else
			{
				$this->errorCollection->add($addResult->getErrors());
				return false;
			}
		}
		else
		{
			$templateId = $template['ID'];
		}

		return \Bitrix\DocumentGenerator\Template::loadById($templateId);
	}

	/**
	 * @param \CRestServer $server
	 * @return array|false
	 */
	protected function getRestAppInfo(\CRestServer $server)
	{
		if($server->getAuthType() === Auth::AUTH_TYPE)
		{
			$app = AppTable::getByClientId($server->getClientId());
			if($app)
			{
				return [
					'TITLE' => $app['APP_NAME'] ? $app['APP_NAME'] : $app['CODE'],
					'CODE' => 'rest_'.Auth::AUTH_TYPE.'_'.$app['ID'],
				];
			}
		}
		elseif($server->getAuthType() === \Bitrix\Rest\APAuth\Auth::AUTH_TYPE)
		{
			$hook = PasswordTable::getById($server->getPasswordId())->fetch();
			if($hook)
			{
				return [
					'TITLE' => $hook['TITLE'],
					'CODE' => 'rest_'.\Bitrix\Rest\APAuth\Auth::AUTH_TYPE.'_'.$hook['ID'],
				];
			}
		}

		return false;
	}

	/**
	 * @return array|false
	 */
	protected function generateStubFile()
	{
		$fileName = md5(mt_rand());
		$fileName = \CTempFile::GetFileName($fileName);

		if(CheckDirPath($fileName))
		{
			if(\Bitrix\Main\IO\File::putFileContents($fileName, ' ') !== false)
			{
				return \CFile::MakeFileArray($fileName);
			}
		}

		return false;
	}

	/**
	 * @param $documentId
	 * @param $action
	 * @param null $updateTime
	 * @return ContentUri|\Bitrix\Main\Web\Uri
	 */
	protected function getDocumentFileLink($documentId, $action, $updateTime = null)
	{
		if(!$updateTime)
		{
			$updateTime = time();
		}
		return new ContentUri(UrlManager::getInstance()->create('documentgenerator.api.document.'.$action, ['id' => $documentId, 'ts' => $updateTime], true)->getUri());
	}
}

