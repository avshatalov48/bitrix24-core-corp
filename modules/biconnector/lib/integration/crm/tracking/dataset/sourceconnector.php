<?php

namespace Bitrix\BIConnector\Integration\Crm\Tracking\Dataset;

use Bitrix\BIConnector\DataSourceConnector\Connector\Base;
use Bitrix\BIConnector\Integration\Crm\Tracking\ExpensesProvider\ProviderFactory;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Json;

class SourceConnector extends Base
{
	public function query(
		array $parameters,
		int $limit,
		array $dateFormats = []
	): \Generator
	{
		$result = new Result();

		$dataResult = $this->getData($parameters, $dateFormats);
		if (!$dataResult->isSuccess())
		{
			foreach ($dataResult->getErrorMessages() as $errorMessage)
			{
				$result->addError(new Error('QUERY_ERROR', 0, ['description' => $errorMessage]));
			}

			return $result;
		}

		$dto = $dataResult->getConnectorData();
		if (empty($dto?->getColumns()))
		{
			$result->addError(new Error('QUERY_ERROR', 0, ['description' => 'No column selected']));

			return $result;
		}

		$providers = ProviderFactory::getAvailableProviders();
		foreach ($providers as $provider)
		{
			$item = [];
			$providerData = [
				'ID' => $provider->getId(),
				'NAME' => $provider->getName(),
				'UTM_SOURCE_LIST' => Json::encode($provider->getUtmSources()),
			];

			foreach ($dto->getColumns() as $code)
			{
				if (isset($providerData[$code]))
				{
					$item[$code] = $providerData[$code];
				}
				else
				{
					$item[$code] = '';
				}
			}

			yield array_values($item);
		}

		return $result;
	}
}

