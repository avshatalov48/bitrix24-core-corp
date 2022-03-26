<?php

namespace Bitrix\Disk\Document;

use Bitrix\Disk\Document\Upload\Office365ResumableUpload;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\ShowSession;
use Bitrix\Disk\TypeFile;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;

Loc::loadMessages(__FILE__);

class Office365Handler extends OneDriveHandler implements IViewer
{
	const SUFFIX_TO_CREATE_LINK           = 'createLink';
	const SUFFIX_TO_CREATE_UPLOAD_SESSION = 'createUploadSession';

	/**
	 * Internal code. Identificate document handler
	 *
	 * Max length is 10 chars
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getCode()
	{
		return 'office365';
	}

	/**
	 * Public name document handler. May show in user interface.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getName()
	{
		return Loc::getMessage('DISK_OFFICE365_HANDLER_NAME');
	}

	/**
	 * Public name storage of documents. May show in user interface.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getStorageName()
	{
		return Loc::getMessage('DISK_OFFICE365_HANDLER_NAME_STORAGE');
	}

	/**
	 * Returns OAuth service for working with Office365.
	 *
	 * @return \CSocServOffice365OAuth|\CSocServAuth
	 */
	protected function getOAuthService()
	{
		$oauth = new \CSocServOffice365OAuth($this->userId);
		foreach ($this->getScopes() as $scope)
		{
			$oauth->getEntityOAuth()->addScope($scope);
		}

		return $oauth;
	}

	protected function instantiateResumableUpload(FileData $fileData)
	{
		return new Office365ResumableUpload($this, $fileData);
	}

	/**
	 * Returns url root for API.
	 *
	 * @return string
	 */
	protected function getApiUrlRoot()
	{
		return $this
			->getOAuthService()
			->getEntityOAuth()
				->getResource() . \COffice365OAuthInterface::VERSION . "/me"
		;
	}
	/**
	 * Returns scopes.
	 *
	 * @return array
	 */
	protected function getScopes()
	{
		return array(
			'offline_access',
			'Files.ReadWrite.All',
			'User.Read',
		);
	}

	/**
	 * Gets data for showing preview file.
	 * Array must be contain keys: id, viewUrl, neededDelete, neededCheckView.
	 *
	 * @param FileData $fileData
	 * @return array|null
	 */
	public function getDataForViewFile(FileData $fileData)
	{
		$fileData = $this->createFileInternal($fileData);
		if($fileData === null)
		{
			return null;
		}

		$http = new HttpClient([
			'redirect' => false,
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		]);

		$http->setHeader('Content-Type', 'application/json; charset=UTF-8');
		$http->setHeader('Authorization', "bearer {$this->getAccessToken()}");

		if ($http->post($this->getApiUrlRoot() . "/drive/items/{$fileData->getId()}/preview") === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection[] = new Error($errorString, self::ERROR_SHARED_EMBED_LINK);

			return null;
		}

		if (!$this->checkHttpResponse($http))
		{
			return null;
		}

		$responseData = Json::decode($http->getResult());
		if ($responseData === null)
		{
			$this->errorCollection[] = new Error('Could not decode response as json', self::ERROR_BAD_JSON);

			return null;
		}

		if (empty($responseData['getUrl']))
		{
			$this->errorCollection[] = new Error('Could not find getUrl in response', self::ERROR_SHARED_EMBED_LINK);

			return null;
		}

		$fileData->setLinkInService($responseData['getUrl']);
		ShowSession::register($this, $fileData, $this->errorCollection);

		return [
			'id' => $fileData->getId(),
			'viewUrl' => $responseData['getUrl'],
			'neededDelete' => true,
			'neededCheckView' => false,
		];
	}

	/**
	 * Returns shared link on the document, which possible to use in iframe to view document.
	 *
	 * @param FileData $fileData File data.
	 * @return null|string
	 */
	protected function getSharedEmbedLink(FileData $fileData)
	{
		/** @see \Bitrix\Disk\Document\OneDriveHandler::getSharedLink() */
		$link = $this->retryMethod('getSharedLink', array($fileData, self::SHARED_LINK_TYPE_VIEW));
		if($link === null)
		{
			return null;
		}

		return (new Uri($link))->addParams([
			'action' => 'embedview',
			'wdStartOn' => 1,
		])->getLocator();
	}

	/**
	 * Shares file to edit for anyone by id.
	 *
	 * @param FileData $fileData
	 * @internal
	 * @return bool
	 */
	public function shareFileToEdit(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id',
		)))
		{
			return false;
		}

		$linkInService = $this->getSharedLink($fileData, static::SHARED_LINK_TYPE_EDIT);
		if(!$linkInService)
		{
			return false;
		}
		
		$fileData->setLinkInService($linkInService);

		return true;
	}

	/**
	 * Gets a file's metadata by ID.
	 *
	 * @param FileData $fileData
	 * @return array|null Describes file (id, title, size)
	 */
	public function getFileMetadata(FileData $fileData)
	{
		$fileMetadata = parent::getFileMetadata($fileData);
		if(empty($fileMetadata['mimeType']))
		{
			$fileMetadata['mimeType'] = TypeFile::getMimeTypeByFilename($fileMetadata['name']);
		}

		return $fileMetadata;
	}

	protected function getUploadPath(FileData $fileData)
	{
		$fileName = $fileData->getName();
		$fileName = $this->convertToUtf8($fileName);
		$fileName = rawurlencode($fileName);

		return $this->getApiUrlRoot() . "/drive/root:/{$fileName}:/";
	}

	public function checkHttpResponse(HttpClient $http)
	{
		$status = (int)$http->getStatus();
		if($status === 404)
		{
			$result = Json::decode($http->getResult());
			if (!empty($result['error']['code']) && $result['error']['code'] === 'itemNotFound')
			{
				//it's hack. Because this type of error we will get when user has lack of scopes (old version scopes).
				$this->errorCollection[] = new Error(
					'Insufficient scope (403)',
					self::ERROR_CODE_INSUFFICIENT_SCOPE
				);

				return false;
			}
		}

		return parent::checkHttpResponse($http);
	}
}
