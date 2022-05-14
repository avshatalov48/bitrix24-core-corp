<?php

namespace Bitrix\Crm\Timeline\HistoryDataModel\Presenter;

use Bitrix\Crm\Entity\PaymentDocumentsRepository;
use Bitrix\Crm\Timeline\HistoryDataModel\Presenter;
use Bitrix\Main\DI\ServiceLocator;

class FinalSummary extends Presenter
{
	protected function prepareDataBySettingsForSpecificEvent(array $data, array $settings): array
	{
		$data['RESULT'] = [];
		if (!isset($settings['ORDER_IDS']) || !is_array($settings['ORDER_IDS']))
		{
			return $data;
		}

		$associatedEntityTypeID = (int)$data['ASSOCIATED_ENTITY_TYPE_ID'];
		$entityId = (int)$data['ASSOCIATED_ENTITY_ID'];

		/** @var PaymentDocumentsRepository */
		$repository = ServiceLocator::getInstance()->get('crm.entity.paymentDocumentsRepository');
		$result = $repository->getDocumentsForEntity($associatedEntityTypeID, $entityId);
		if ($result->isSuccess())
		{
			$data['RESULT']['TIMELINE_SUMMARY_OPTIONS'] = $result->getData();
		}

		return $data;
	}
}
