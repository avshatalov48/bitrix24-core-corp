<?php

namespace Bitrix\DocumentGenerator\Rest;

use Bitrix\DocumentGenerator\Controller\File;
use Bitrix\DocumentGenerator\Document;
use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Model\FileTable;
use Bitrix\DocumentGenerator\Template;
use Bitrix\Main\Application;
use Bitrix\Rest\RestException;

class RestManager
{
	const DOCUMENT_FILE_TYPE_FILE = 'file';
	const DOCUMENT_FILE_TYPE_IMAGE = 'image';
	const DOCUMENT_FILE_TYPE_PDF = 'pdf';

	/**
	 * @return array
	 */
	public static function onRestGetModule()
	{
		return ['MODULE_ID' => Driver::MODULE_ID];
	}

	/**
	 * @return array
	 */
	public static function onRestServiceBuildDescription()
	{
		return [
			Driver::MODULE_ID => [
				\CRestUtil::METHOD_UPLOAD => [static::class, 'upload'],
				\CRestUtil::METHOD_DOWNLOAD => [static::class, 'download'],
			],
		];
	}

	/**
	 * @param array $query
	 * @param $scope
	 * @param \CRestServer $server
	 * @return array
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Exception
	 */
	public static function upload(array $query, $scope, \CRestServer $restServer)
	{
		$file = Application::getInstance()->getContext()->getRequest()->getFile(File::FILE_PARAM_NAME);
		if(!is_array($file))
		{
			throw new RestException('missing file content', RestException::ERROR_ARGUMENT);
		}

		$result = FileTable::saveFile($file);
		if($result->isSuccess())
		{
			return ['id' => $result->getId()];
		}
		else
		{
			throw new RestException(implode(', ', $result->getErrorMessages()));
		}
	}

	/**
	 * @param array $query
	 * @param $scope
	 * @param \CRestServer $restServer
	 * @throws RestException
	 */
	public static function download(array $query, $scope, \CRestServer $restServer)
	{
		if(isset($query['templateId']))
		{
			$template = Template::loadById($query['templateId']);
			if($template)
			{
				static::showFileContent($template->FILE_ID);
			}
			else
			{
				throw new RestException('Template not found', RestException::ERROR_NOT_FOUND);
			}
		}
		elseif(isset($query['documentId']) && isset($query['type']))
		{
			$document = Document::loadById($query['documentId']);
			if($document)
			{
				if($query['type'] === static::DOCUMENT_FILE_TYPE_FILE)
				{
					static::showFileContent($document->FILE_ID);
				}
				elseif($query['type'] === static::DOCUMENT_FILE_TYPE_PDF)
				{
					if($document->PDF_ID > 0)
					{
						static::showFileContent($document->PDF_ID);
					}
					else
					{
						throw new RestException('No pdf for document');
					}
				}
				elseif($query['type'] === static::DOCUMENT_FILE_TYPE_IMAGE)
				{
					if($document->IMAGE_ID > 0)
					{
						static::showFileContent($document->IMAGE_ID);
					}
					else
					{
						throw new RestException('No image for document');
					}
				}
			}
			else
			{
				throw new RestException('Document not found', RestException::ERROR_NOT_FOUND);
			}
		}

		throw new RestException('Wrong arguments', RestException::ERROR_ARGUMENT);
	}

	/**
	 * @param int $fileId
	 * @throws RestException
	 */
	protected static function showFileContent($fileId)
	{
		$content = FileTable::getContent($fileId);
		if($content)
		{
			header("Content-Type: text/plain");
			echo $content;

			Application::getInstance()->terminate();
		}

		throw new RestException('File not found', RestException::ERROR_NOT_FOUND);
	}
}