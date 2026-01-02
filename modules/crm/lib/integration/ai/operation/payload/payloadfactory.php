<?php

namespace Bitrix\Crm\Integration\AI\Operation\Payload;

use Bitrix\Crm\Integration\AI\Operation\ExtractScoringCriteria;
use Bitrix\Crm\Integration\AI\Operation\FillItemFieldsFromCallTranscription;
use Bitrix\Crm\Integration\AI\Operation\FillRepeatSaleTips;
use Bitrix\Crm\Integration\AI\Operation\Payload\Payload\CallScoring;
use Bitrix\Crm\Integration\AI\Operation\Payload\Payload\ExtractFormFields;
use Bitrix\Crm\Integration\AI\Operation\Payload\Payload\RepeatSalesPrompt;
use Bitrix\Crm\Integration\AI\Operation\Payload\Payload\ScoringCriteriaExtraction;
use Bitrix\Crm\Integration\AI\Operation\Payload\Payload\SummarizeTranscript;
use Bitrix\Crm\Integration\AI\Operation\ScoreCall;
use Bitrix\Crm\Integration\AI\Operation\SummarizeCallTranscription;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\ArgumentException;

final class PayloadFactory
{
	/**
	 * @throws ArgumentException
	 */
	public static function build(int $code, ?int $userId, ItemIdentifier $identifier): PayloadInterface
	{
		return match ($code)
		{
			SummarizeCallTranscription::TYPE_ID => new SummarizeTranscript($userId, $identifier),
			FillItemFieldsFromCallTranscription::TYPE_ID => new ExtractFormFields($userId, $identifier),
			ScoreCall::TYPE_ID => new CallScoring($userId, $identifier),
			ExtractScoringCriteria::TYPE_ID => new ScoringCriteriaExtraction($userId, $identifier),
			FillRepeatSaleTips::TYPE_ID => new RepeatSalesPrompt($userId, $identifier),
			default => throw new ArgumentException('Unsupported operation code'),
		};
	}
}
