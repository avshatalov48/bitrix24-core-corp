<?php

namespace Bitrix\Crm\Timeline\HistoryDataModel\Presenter;

use Bitrix\Crm\Entity\PaymentDocumentsRepository;
use Bitrix\Crm\Order\Manager;
use Bitrix\Crm\Timeline\HistoryDataModel\Presenter;
use Bitrix\Main\DI\ServiceLocator;

class FinalSummaryDocuments extends Presenter
{
	protected function prepareDataBySettingsForSpecificEvent(array $data, array $settings): array
	{
		$data['RESULT'] = [];
		$associatedEntityTypeID = $data['ASSOCIATED_ENTITY_TYPE_ID'] ?? null;
		if (!\CCrmOwnerType::isUseFactoryBasedApproach($associatedEntityTypeID))
		{
			return $data;
		}

		$entityId = (int)($data['ASSOCIATED_ENTITY_ID'] ?? 0);
		/** @var PaymentDocumentsRepository */
		$repository = ServiceLocator::getInstance()->get('crm.entity.paymentDocumentsRepository');
		$result = $repository->getDocumentsForEntity($associatedEntityTypeID, $entityId);
		if ($result->isSuccess())
		{
			$data['RESULT']['TIMELINE_SUMMARY_OPTIONS'] = $result->getData();
		}

		$data['RESULT']['CHECKS'] = [];
		foreach ($settings['ORDER_IDS'] as $orderId)
		{
			$data['RESULT']['CHECKS'][] = Manager::getCheckData($orderId);
		}

		$data['RESULT']['CHECKS'] = array_merge(...$data['RESULT']['CHECKS']);

		return $data;
	}
}
