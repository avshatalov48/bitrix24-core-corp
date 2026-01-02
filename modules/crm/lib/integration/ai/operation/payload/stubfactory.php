<?php

namespace Bitrix\Crm\Integration\AI\Operation\Payload;

use Bitrix\Crm\Integration\AI\Operation\ExtractScoringCriteria;
use Bitrix\Crm\Integration\AI\Operation\FillItemFieldsFromCallTranscription;
use Bitrix\Crm\Integration\AI\Operation\FillRepeatSaleTips;
use Bitrix\Crm\Integration\AI\Operation\Payload\Stub\CallScoring;
use Bitrix\Crm\Integration\AI\Operation\Payload\Stub\CallTranscribe;
use Bitrix\Crm\Integration\AI\Operation\Payload\Stub\ExtractFormFields;
use Bitrix\Crm\Integration\AI\Operation\Payload\Stub\RepeatSalesPrompt;
use Bitrix\Crm\Integration\AI\Operation\Payload\Stub\ScoringCriteriaExtraction;
use Bitrix\Crm\Integration\AI\Operation\Payload\Stub\SummarizeTranscript;
use Bitrix\Crm\Integration\AI\Operation\ScoreCall;
use Bitrix\Crm\Integration\AI\Operation\SummarizeCallTranscription;
use Bitrix\Crm\Integration\AI\Operation\TranscribeCallRecording;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\ArgumentException;

final class StubFactory
{
	/**
	 * @throws ArgumentException
	 */
	public static function build(int $code, ItemIdentifier $identifier): StubInterface
	{
		return match ($code)
		{
			TranscribeCallRecording::TYPE_ID => new CallTranscribe(),
			SummarizeCallTranscription::TYPE_ID => new SummarizeTranscript(),
			FillItemFieldsFromCallTranscription::TYPE_ID => new ExtractFormFields($identifier),
			ScoreCall::TYPE_ID => new CallScoring(),
			ExtractScoringCriteria::TYPE_ID => new ScoringCriteriaExtraction(),
			FillRepeatSaleTips::TYPE_ID => new RepeatSalesPrompt(),
			default => throw new ArgumentException('Unsupported operation code'),
		};
	}
}
