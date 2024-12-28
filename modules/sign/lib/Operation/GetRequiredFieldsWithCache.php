<?php

namespace Bitrix\Sign\Operation;

use Bitrix\Main;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Repository\RequiredFieldRepository;
use Bitrix\Sign\Service\Api\B2e\ProviderFieldsService;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Result\Sign\Block\B2eRequiredFieldsResult;

class GetRequiredFieldsWithCache implements Operation
{
	private readonly RequiredFieldRepository $requiredFieldRepository;
	private readonly ProviderFieldsService $providerFieldsService;
	public function __construct(
		private readonly int $documentId,
		private readonly string $companyUid,
		?RequiredFieldRepository $requiredFieldRepository = null,
		?ProviderFieldsService $providerFieldsService = null,
	)
	{
		$container = Container::instance();
		$this->requiredFieldRepository = $requiredFieldRepository ?? $container->getRequiredFieldRepository();
		$this->providerFieldsService = $providerFieldsService ?? $container->getApiB2eProviderFieldsService();
	}

	public function launch(): B2eRequiredFieldsResult|Main\Result
	{
		$collection = $this->requiredFieldRepository
			->listByDocumentId($this->documentId)
			->convertToRequiredFieldCollection()
		;
		if (!$collection->isEmpty())
		{
			return new B2eRequiredFieldsResult($collection);
		}

		$apiResult = $this->providerFieldsService->loadRequiredFields($this->companyUid);

		if ($apiResult instanceof B2eRequiredFieldsResult)
		{
			$this->requiredFieldRepository
				->replaceRequiredItemsCollectionForDocumentId($apiResult->collection, $this->documentId);
		}

		return $apiResult;
	}
}