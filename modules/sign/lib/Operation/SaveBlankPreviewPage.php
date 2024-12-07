<?php

namespace Bitrix\Sign\Operation;

use Bitrix\Main;

use Bitrix\Main\Web\MimeType;
use Bitrix\Sign\Item;
use Bitrix\Sign\Item\Fs\FileContent;
use Bitrix\Sign\Repository\Blank\ResourceRepository;
use Bitrix\Sign\Repository\FileRepository;
use Bitrix\Sign\Service;
use Bitrix\Sign\Contract;

final class SaveBlankPreviewPage implements Contract\Operation
{
	private Main\Web\HttpClient $httpClient;

	public function __construct(
		private readonly Item\Blank $blank,
		private readonly string $url,
		private readonly string $baseUrl,
		private ?ResourceRepository $resourceRepository = null,
		private ?FileRepository $fileRepository = null
	)
	{
		$this->resourceRepository ??= Service\Container::instance()->getBlankResourceRepository();
		$this->fileRepository ??= Service\Container::instance()->getFileRepository();
		$this->httpClient = new Main\Web\HttpClient();
	}

	public function launch(): Main\Result
	{
		$result = new Main\Result();
		$baseUrl = (str_ends_with($this->baseUrl, '/')) ? $this->baseUrl : $this->baseUrl . '/';
		if(!preg_match('/^(http|https):\/\/[a-z0-9_\-\/.]+\/$/', $baseUrl))
		{
			return $result->addError(new Main\Error('Invalid base url'));
		}
		if (!str_starts_with($this->url, $baseUrl))
		{
			return $result->addError(new Main\Error('Invalid file url'));
		}
		if ($this->blank->id === null)
		{
			return $result->addError(new Main\Error('Blank item field `id` is empty'));
		}
		$resource = $this->resourceRepository->getFirstByBlankId($this->blank->id);
		if ($resource)
		{
			return $result->addError(new Main\Error('Preview image already created'));
		}
		$getFileResult = $this->httpClient->get($this->url);
		if ($getFileResult === false)
		{
			return $result->addError(new Main\Error('Can\'t download file'));
		}
		if (!MimeType::isImage($this->httpClient->getHeaders()->getContentType()))
		{
			return $result->addError(new Main\Error('File type is not allowed'));
		}
		$content = new FileContent($getFileResult);
		if (empty($content->data))
		{
			return $result->addError(new Main\Error('File can\'t be empty'));
		}
		$fsFile = new Item\Fs\File(
			name: uniqid(more_entropy: true),
			content: $content
		);
		$filePutResult = $this->fileRepository->put($fsFile);
		if (!$filePutResult->isSuccess())
		{
			return $result->addErrors($filePutResult->getErrors());
		}
		if ($fsFile->id === null)
		{
			return $result->addError(new Main\Error('File item field `id` is empty'));
		}
		$resource = new Item\Blank\Resource(
			null,
			$this->blank->id,
			$fsFile->id
		);
		$resourceAddResult = $this->resourceRepository->add($resource);
		if (!$resourceAddResult->isSuccess())
		{
			return $result->addErrors($resourceAddResult->getErrors());
		}

		return $result;
	}
}
