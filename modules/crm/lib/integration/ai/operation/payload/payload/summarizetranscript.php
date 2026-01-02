<?php

namespace Bitrix\Crm\Integration\AI\Operation\Payload\Payload;

use Bitrix\Crm\Integration\AI\Operation\Payload\CalcMarkersInterface;
use Bitrix\Crm\Integration\AI\Operation\Payload\PayloadInterface;
use Bitrix\Crm\Requisite\EntityLink;

final class SummarizeTranscript extends AbstractPayload implements CalcMarkersInterface
{
	public function getPayloadCode(): string
	{
		return 'summarize_transcript';
	}
	
	public function setMarkers(array $markers): PayloadInterface
	{
		$this->markers = array_merge($markers, $this->calcMarkers());

		return $this;
	}

	public function calcMarkers(): array
	{
		$activity = $this->getActivity();
		
		return [
			'company_name' => $this->getCompanyName(EntityLink::getDefaultMyCompanyId()),
			'manager_name' => $this->getUserName((int)($activity['RESPONSIBLE_ID'] ?? 0)),
		];
	}
}
