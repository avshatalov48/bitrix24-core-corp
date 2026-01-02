<?php

namespace Bitrix\Crm\Integration\AI\Operation\Payload\Payload;

use Bitrix\Crm\Format\TextHelper;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Operation\Orchestrator;
use Bitrix\Crm\Integration\AI\Operation\Payload\CalcMarkersInterface;
use Bitrix\Crm\Integration\AI\Operation\Payload\PayloadInterface;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\RepeatSale\DataCollector\CopilotMarkerLimitManager;
use Bitrix\Crm\RepeatSale\DataCollector\DataCollectorManager;
use Bitrix\Crm\RepeatSale\Segment\Controller\RepeatSaleSegmentController;
use Bitrix\Main\Web\Json;
use CCrmOwnerType;

final class RepeatSalesPrompt extends AbstractPayload implements CalcMarkersInterface
{
	public function getPayloadCode(): string
	{
		return 'repeat_sales_prompt';
	}

	public function setMarkers(array $markers): PayloadInterface
	{
		$this->markers = array_merge($markers, $this->calcMarkers());
		
		return $this;
	}

	public function calcMarkers(): array
	{
		$activity = $this->getActivity();
		if (empty($activity))
		{
			return []; // no activity
		}

		$segmentData = $this->getSegmentData($activity);
		$crmData = $this->getCrmData($activity);
		if (empty($segmentData) || empty($crmData))
		{
			return []; // no data
		}

		return [
			'segment_data' => in_array('segment_data', $this->encodedMarkers, true)
				? Json::encode(['client_segment' => $segmentData])
				: ['client_segment' => $segmentData],
			'crm_data' => in_array('crm_data', $this->encodedMarkers, true)
				? Json::encode($crmData)
				: $crmData,
			'crm_data_sufficient' => $this->isCrmDataSufficient($crmData),
		];
	}

	private function getCrmData(array $activity): array
	{
		$clientEntityTypeId = (int)($activity['PROVIDER_PARAMS']['CLIENT_ENTITY_TYPE_ID'] ?? 0);
		$clientEntityId = (int)($activity['PROVIDER_PARAMS']['CLIENT_ENTITY_ID'] ?? 0);
		if ($clientEntityTypeId <= 0 || $clientEntityId <= 0)
		{
			return []; // no client
		}

		$entityIdentifier = (new Orchestrator())->findPossibleFillFieldsTarget($this->identifier->getEntityId());
		if (!$entityIdentifier)
		{
			return []; // no owner entity
		}

		if ($entityIdentifier->getEntityTypeId() !== CCrmOwnerType::Deal)
		{
			return []; // currently only deal supported
		}

		return (new DataCollectorManager(
			$entityIdentifier,
			new ItemIdentifier($clientEntityTypeId, $clientEntityId),
			AIManager::logger(),
			$this->userId
		))->collectCopilotData();
	}

	private function getSegmentData(array $activity): array
	{
		$segmentId = (int)($activity['PROVIDER_PARAMS']['SEGMENT_ID'] ?? 0);
		if ($segmentId <= 0)
		{
			return [];
		}

		return RepeatSaleSegmentController::getInstance()->collectCopilotData($segmentId);
	}

	private function isCrmDataSufficient(array $data): bool
	{
		$dealList = $data['deals_list'] ?? [];
		if (empty($dealList))
		{
			return false;
		}

		$limit = CopilotMarkerLimitManager::getInstance()->getCommunicationFieldsLimit();
		$filtered =  array_filter(
			$dealList,
			static function (array $item) use ($limit): bool
			{
				$communicationData = $item['communication_data'] ?? [];

				return TextHelper::countCharactersInArrayFlexible($communicationData) > $limit;
			},
		);

		return !empty($filtered);
	}
}
