<?php

namespace Bitrix\Crm\Integration\Socialnetwork\Livefeed;

use Bitrix\Socialnetwork\LogTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Integration;

final class CrmCompany extends CrmEntity
{
	public const PROVIDER_ID = 'CRM_LOG_COMPANY';
	public const CONTENT_TYPE_ID = 'CRM_LOG_COMPANY';

	public function getEventId(): array
	{
		return [
			\CCrmLiveFeedEvent::CompanyPrefix . \CCrmLiveFeedEvent::Add,
			\CCrmLiveFeedEvent::CompanyPrefix . \CCrmLiveFeedEvent::Denomination,
			\CCrmLiveFeedEvent::CompanyPrefix . \CCrmLiveFeedEvent::Responsible,
			\CCrmLiveFeedEvent::CompanyPrefix . \CCrmLiveFeedEvent::Message,
		];
	}

	public function getMessageEventId(): array
	{
		return [
			\CCrmLiveFeedEvent::CompanyPrefix . \CCrmLiveFeedEvent::Message,
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
			$res = \CCrmCompany::getListEx(
				[],
				[
					'ID' => $logEntryFields['ENTITY_ID'],
					'CHECK_PERMISSIONS' => 'N'
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
		return Integration\Socialnetwork::DATA_ENTITY_TYPE_CRM_COMPANY;
	}

	public function getLogCommentEventId(): string
	{
		return 'crm_company_message';
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
			$fields = array(
				'ID' => $entityId,
				'CURRENT_ENTITY' => $this->getCurrentEntityFields()
			);
		}

		if ($logId > 0)
		{
			$res = LogTable::getList(array(
				'filter' => array(
					'=ID' => $logId
				)
			));
			$logEntry = $res->fetch();
			if (
				!empty($logEntry['PARAMS'])
				&& !empty($fields['CURRENT_ENTITY'])
			) // not-message
			{
				$logEntry['PARAMS'] = unserialize($logEntry['PARAMS'], ['allowed_classes' => false]);
				if (is_array($logEntry['PARAMS']))
				{
					$this->setCrmEntitySourceTitle($fields['CURRENT_ENTITY']);
					$fields = array_merge($fields, $logEntry['PARAMS']);

					$sourceDescription = Integration\Socialnetwork::buildAuxTaskDescription(
						$logEntry['PARAMS'],
						$this->getLogEntityType()
					);

					if (!empty($sourceDescription))
					{
						$this->setSourceDescription(Loc::getMessage('CRMINTEGRATION_SONETLF_ENTITY_DESCRIPTION', array(
							'#LOGENTRY_TITLE#' => $logEntry['TITLE'],
							'#ENTITY_TITLE#' => $sourceDescription
						)));
					}
				}
			}
			elseif ($logEntry['EVENT_ID'] === $this->getLogCommentEventId())
			{
				$this->setSourceDescription($logEntry['MESSAGE']);
				$this->setSourceTitle(truncateText(($logEntry['TITLE'] !== '__EMPTY__' ? $logEntry['TITLE'] : $logEntry['MESSAGE']), 100));
			}
		}

		$this->setSourceFields($fields);
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