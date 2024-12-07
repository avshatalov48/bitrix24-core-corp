<?php
namespace Bitrix\Sign\Controller;

use Bitrix\Crm\Integration\Sign\Form;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Engine\Response\BFile;
use Bitrix\Main\Response;
use Bitrix\Main\Error;
use Bitrix\Sign\Access\Permission\PermissionDictionary;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Document as DocumentCore;
use Bitrix\Sign\Document\Status;
use Bitrix\Sign\File;
use Bitrix\Sign\Main\Application;
use Bitrix\Sign\Operation\CheckDocumentAccess;
use Bitrix\Sign\Proxy;

class Document extends Controller
{
	public function getDefaultPreFilters(): array
	{
		return [
			new \Bitrix\Main\Engine\ActionFilter\Authentication(),
			new \Bitrix\Main\Engine\ActionFilter\Csrf(),
			new \Bitrix\Sign\Controller\ActionFilter\Extranet(),
		];
	}

	public function configureActions(): array
	{
		$actionsConfiguration = parent::configureActions();
		$actionsConfiguration['downloadResultFile']['-prefilters'] = [
			\Bitrix\Main\Engine\ActionFilter\Csrf::class,
		];
		return $actionsConfiguration;
	}

	/**
	 * Gets document for Rendering
	 *
	 * @param string $documentHash Document hash.
	 * @param string|null $memberHash Member hash.
	 * @return Response
	 */
	public function getFileForSrcAction(string $documentHash, ?string $memberHash = null): Response
	{
		$response = \Bitrix\Main\Context::getCurrent()->getResponse();
		$document = DocumentCore::getByHash($documentHash);
		if ($document)
		{

			$data = [
				'documentHash' => $document->getHash(),
				'secCode' => $document->getSecCode(),
			];

			if ($memberHash)
			{
				$data['memberHash'] = $memberHash;
			}
			$fileToken = Proxy::sendCommand('document.file.getFileToken', $data);

			if ($fileToken)
			{
				$this->prepareFileResponse($document, $fileToken);
				return $response;
			}

			if (!$memberHash)
			{
				$file = $document->getResultFile();
				if ($file && $file->isExist())
				{
					\CFile::viewByUser($file->getId(), [
						'attachment_name' => $this->getDocumentFilenameForResponse($document),
					]);
				}

				return $response;
			}
		}

		return $response;
	}

	/**
	 * Download result file for document by its hash
	 *
	 * @param string $documentHash
	 * @return BFile|array
	 */
	public function downloadResultFileAction(string $documentHash): BFile | array
	{
		$document = DocumentCore::getByHash($documentHash);
		if (!$document)
		{
			$this->addError(new Error('Document not found'));
			return [];
		}

		$newDocumentItem = \Bitrix\Sign\Service\Container::instance()
			->getDocumentService()
			->getById($document->getId())
		;
		if ($newDocumentItem === null)
		{
			$this->addError(new Error('Document not found'));
			return [];
		}

		$result = $this->checkDownloadAccess($newDocumentItem);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return [];
		}

		if ($document->getProcessingStatus() !== Status::READY)
		{
			$this->addError(new Error('Wrong status'));
			return [];
		}

		$file = $document->getResultFile();
		if (!$file || !$file->isExist())
		{
			$this->addError(new Error('No result file'));
			return [];
		}

		return BFile::createByFileId(
			$file->getId(),
			$this->getDocumentFilenameForResponse($document)
		)->showInline(false);
	}

	/**
	 * @param DocumentCore $document
	 * @param $fileToken
	 * @throws ArgumentNullException
	 * @throws ArgumentTypeException
	 */
	private function prepareFileResponse(DocumentCore $document, $fileToken)
	{
		$url = Proxy::getCommandUrl('document.file.getFileContent', [
			'data' => [
				'documentHash' => $document->getHash(),
				'fileToken' => $fileToken,
			]
		]);

		$response = \Bitrix\Main\Application::getInstance()->getContext()->getResponse();
		$response->addHeader('Location', $url);
	}

	private function getDocumentFilenameForResponse(DocumentCore $document): string
	{
		return 'Smart_Document_' . $document->getEntityId() . '.pdf';
	}

	private function checkDownloadAccess(\Bitrix\Sign\Item\Document $document): \Bitrix\Main\Result
	{
		$result = (new CheckDocumentAccess($document, SignPermissionDictionary::SIGN_MY_SAFE_DOCUMENTS))->launch();
		if ($result->isSuccess())
		{
			return $result;
		}

		return (new CheckDocumentAccess($document, PermissionDictionary::SIGN_CRM_SMART_DOCUMENT_READ))->launch();
	}
}
