<?php

namespace Bitrix\Crm\RepeatSale\DataCollector\Activity;

use Bitrix\Crm\RepeatSale\DataCollector\Activity\Strategy\CallRecordingStrategy;
use Bitrix\Crm\RepeatSale\DataCollector\Activity\Strategy\CommentsStrategy;
use Bitrix\Crm\RepeatSale\DataCollector\Activity\Strategy\EmailsStrategy;
use Bitrix\Crm\RepeatSale\DataCollector\Activity\Strategy\OpenLinesStrategy;
use Bitrix\Crm\RepeatSale\DataCollector\Activity\Strategy\TodosStrategy;

class StrategyFactory
{
	private readonly TextNormalizer $textNormalizer;
	private readonly ActivityQueryBuilder $queryBuilder;

	public function __construct()
	{
		$this->textNormalizer = new TextNormalizer(new TextLengthConfig());
		$this->queryBuilder = new ActivityQueryBuilder();
	}

	public function createCallRecordingStrategy(int $entityTypeId): CallRecordingStrategy
	{
		return new CallRecordingStrategy($entityTypeId, $this->textNormalizer, $this->queryBuilder);
	}

	public function createCommentsStrategy(int $entityTypeId): CommentsStrategy
	{
		return new CommentsStrategy($entityTypeId, $this->textNormalizer, $this->queryBuilder);
	}

	public function createTodosStrategy(int $entityTypeId): TodosStrategy
	{
		return new TodosStrategy($entityTypeId, $this->textNormalizer, $this->queryBuilder);
	}

	public function createEmailsStrategy(int $entityTypeId): EmailsStrategy
	{
		return new EmailsStrategy($entityTypeId, $this->textNormalizer, $this->queryBuilder);
	}

	public function createOpenLinesStrategy(int $entityTypeId): OpenLinesStrategy
	{
		return new OpenLinesStrategy($entityTypeId, $this->textNormalizer, $this->queryBuilder);
	}
}
