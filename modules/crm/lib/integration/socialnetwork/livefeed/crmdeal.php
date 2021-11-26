<?php

namespace Bitrix\Crm\Integration\Socialnetwork\Livefeed;

use Bitrix\Crm\Integration\Socialnetwork;
use Bitrix\Socialnetwork\LogTable;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class CrmDeal extends CrmEntity
{
	public const PROVIDER_ID = 'CRM_LOG_DEAL';
	public const CONTENT_TYPE_ID = 'CRM_LOG_DEAL';

	private const EMPTY_TITLE = '__EMPTY__';

	public function getEventId(): array
	{
		return [
			\CCrmLiveFeedEvent::DealPrefix.\CCrmLiveFeedEvent::Add,
			\CCrmLiveFeedEvent::DealPrefix.\CCrmLiveFeedEvent::Client,
			\CCrmLiveFeedEvent::DealPrefix.\CCrmLiveFeedEvent::Progress,
			\CCrmLiveFeedEvent::DealPrefix.\CCrmLiveFeedEvent::Responsible,
			\CCrmLiveFeedEvent::DealPrefix.\CCrmLiveFeedEvent::Progress,
			\CCrmLiveFeedEvent::DealPrefix.\CCrmLiveFeedEvent::Message
		];
	}

	public function getMessageEventId(): array
	{
		return [
			\CCrmLiveFeedEvent::DealPrefix.\CCrmLiveFeedEvent::Message
		];
	}

	public function getCurrentEntityFields(): array
	{
		$result = [];

		$res = LogTable::getList([
			'filter' => [
				'=ID' => $this->getEntityId()
			],
			'select' => [ 'ENTITY_ID' ]
		]);
		if ($logEntryFields = $res->fetch())
		{
			$res = \CCrmDeal::getListEx(
				[],
				[
					'ID' => $logEntryFields['ENTITY_ID'],
					'CHECK_PERMISSIONS' => 'N',
				],
				false,
				[ 'nTopCount' => 1 ],
				[]
			);
			if ($currentEntity = $res->fetch())
			{
				$result = $currentEntity;
			}
		}

		return $result;
	}

	public function getLogEntityType(): string
	{
		return Socialnetwork::DATA_ENTITY_TYPE_CRM_DEAL;
	}

	public function getLogCommentEventId(): string
	{
		return 'crm_deal_message';
	}

	public function setCrmEntitySourceTitle(array $entityFields = []): void
	{
		$this->setSourceTitle($entityFields['TITLE']);
	}

	public function initSourceFields()
	{
		$entityId = $this->getEntityId();
		$logId = $this->getLogId();

		$fields = [];

		if ($entityId > 0)
		{
			$fields = [
				'ID' => $entityId,
				'CURRENT_ENTITY' => $this->getCurrentEntityFields()
			];
		}

		if ($logId > 0)
		{
			$res = LogTable::getList([
				'filter' => [
					'=ID' => $logId,
				],
			]);
			$logEntry = $res->fetch();

			if (
				!empty($logEntry['PARAMS'])
				&& !empty($fields['CURRENT_ENTITY'])
			) // not-message
			{
				$logEntry['PARAMS'] = unserialize($logEntry['PARAMS'], [ 'allowed_classes' => false ]);
				if (is_array($logEntry['PARAMS']))
				{
					$this->setCrmEntitySourceTitle($fields['CURRENT_ENTITY']);
					$fields = array_merge($fields, $logEntry['PARAMS']);

					$sourceDescription = Socialnetwork::buildAuxTaskDescription(
						$logEntry['PARAMS'],
						$this->getLogEntityType()
					);

					if (!empty($sourceDescription))
					{
						$title = $logEntry['TITLE'];
						if ($title === self::EMPTY_TITLE)
						{
							$title = self::getEventTitle([
								'EVENT_ID' => $logEntry['EVENT_ID'],
							]);
						}

						$this->setSourceDescription(Loc::getMessage('CRMINTEGRATION_SONETLF_ENTITY_DESCRIPTION', [
							'#LOGENTRY_TITLE#' => $title,
							'#ENTITY_TITLE#' => $sourceDescription,
						]));
					}
				}
			}
			elseif ($logEntry['EVENT_ID'] === $this->getLogCommentEventId())
			{
				$this->setSourceDescription($logEntry['MESSAGE']);
				$this->setSourceTitle(truncateText(($logEntry['TITLE'] !== self::EMPTY_TITLE ? $logEntry['TITLE'] : $logEntry['MESSAGE']), 100));
			}
		}

		$this->setSourceFields($fields);
	}

	private static function getEventTitle(array $params = []): string
	{
		$result = '';
		$eventId = ($params['EVENT_ID'] ?? null);
		if (!$eventId)
		{
			return $result;
		}

		switch ($eventId)
		{
			case 'crm_deal_progress':
				$result = Loc::getMessage('CRMINTEGRATION_SONETLF_DEAL_PROGRESS_TITLE');
				break;
		}

		return $result;
	}

	public function getSuffix(): string
	{
		$logEventId = $this->getLogEventId();
		if (
			!empty($logEventId)
			&& in_array($logEventId, $this->getMessageEventId(), true)
		)
		{
			return 'MESSAGE';
		}

		return '';
	}
}
