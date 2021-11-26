<?php

namespace Bitrix\Crm\Integration\Socialnetwork\Livefeed;

use Bitrix\Socialnetwork\Livefeed\Provider;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\LogTable;

Loc::loadMessages(__FILE__);

class CrmEntity extends Provider
{
	public const PROVIDER_ID = 'CRM_ENTITY';
	public const CONTENT_TYPE_ID = 'CRM_ENTITY';

	public static function getId(): string
	{
		return static::PROVIDER_ID;
	}

	public function getEventId(): array
	{
		return [];
	}

	public function getType(): string
	{
		return Provider::TYPE_POST;
	}

	public function getCommentProvider(): Provider
	{
		return new CrmEntityComment();
	}

	public function getCurrentEntityFields(): array
	{
		return [];
	}

	public function getLogEntityType(): string
	{
		return '';
	}

	public function getLogCommentEventId(): string
	{
		return '';
	}

	public function setCrmEntitySourceTitle(array $entityFields = []): void
	{

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
				'CURRENT_ENTITY' => $this->getCurrentEntityFields(),
			];
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

					$sourceDescription = \Bitrix\Crm\Integration\Socialnetwork::buildAuxTaskDescription(
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

	public function getLiveFeedUrl(): string
	{
		$result = '';
		$logId = $this->getLogId();

		if ($logId > 0)
		{
			$result = '/crm/stream/?log_id=' . $logId;
		}

		return $result;
	}
}
