<?php

namespace Bitrix\Crm\RepeatSale\DataCollector\Activity\Strategy;

use Bitrix\Crm\Activity\Provider\OpenLine;
use Bitrix\Crm\Integration\OpenLineManager;
use Bitrix\Crm\RepeatSale\DataCollector\Activity\ActivityType;
use Bitrix\Crm\RepeatSale\DataCollector\Activity\StrategyBase;

class OpenLinesStrategy extends StrategyBase
{
	public function getType(): ActivityType
	{
		return ActivityType::OPEN_LINES;
	}

	public function collect(int $entityId, int $limit): array
	{
		$query = $this
			->queryBuilder
			->buildActivityQuery($this->entityTypeId, $entityId, OpenLine::getId(), $limit)
		;

		$providerParams = $query
			->setSelect(['PROVIDER_PARAMS'])
			->whereNotNull('PROVIDER_PARAMS')
			->fetchAll()
		;
		if (empty($providerParams))
		{
			return [];
		}

		$openLines = [];
		foreach ($providerParams as $row)
		{
			$userCode = (string)($row['PROVIDER_PARAMS']['USER_CODE'] ?? '');
			if (empty($userCode))
			{
				continue;
			}

			$data = OpenLineManager::getMessageData($userCode);
			$openLinesText = $this->formatOpenLinesText($data);
			$normalizedText = $this
				->textNormalizer
				->normalize($openLinesText, $this->getType())
			;
			if ($normalizedText !== null)
			{
				$openLines[] = $normalizedText;
			}
		}

		return $this->filterValidData($openLines);
	}

	private function formatOpenLinesText(array $data): string
	{
		$messages = array_filter(
			$data['messages'],
			static fn(array $item) => $item['author_id'] > 0
		);
		if (empty($messages))
		{
			return '';
		}

		$userMap = array_column($data['users'] ?? [], 'name', 'id');

		$result = [];
		foreach ($messages as $message)
		{
			$result[] = sprintf(
				"%s%s\n%s",
				$userMap[$message['author_id']] ?? '',
				isset($message['date']) ? ' [' .  $message['date'] . ']:' : '',
				trim($message['text'])
			);
		}

		return implode(' ', $result);
	}
}
