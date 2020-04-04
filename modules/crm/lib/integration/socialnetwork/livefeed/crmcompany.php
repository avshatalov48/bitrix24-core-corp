<?
namespace Bitrix\Crm\Integration\Socialnetwork\Livefeed;

use Bitrix\Socialnetwork\LogTable;

final class CrmCompany extends CrmEntity
{
	const PROVIDER_ID = 'CRM_LOG_COMPANY';
	const CONTENT_TYPE_ID = 'CRM_LOG_COMPANY';

	public function getEventId()
	{
		return array(
			\CCrmLiveFeedEvent::CompanyPrefix.\CCrmLiveFeedEvent::Add,
			\CCrmLiveFeedEvent::CompanyPrefix.\CCrmLiveFeedEvent::Denomination,
			\CCrmLiveFeedEvent::CompanyPrefix.\CCrmLiveFeedEvent::Responsible,
			\CCrmLiveFeedEvent::CompanyPrefix.\CCrmLiveFeedEvent::Message
		);
	}

	public function getCurrentEntityFields()
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

	public function getLogEntityType()
	{
		return \Bitrix\Crm\Integration\Socialnetwork::DATA_ENTITY_TYPE_CRM_COMPANY;
	}

	public function getLogCommentEventId()
	{
		return 'crm_company_message';
	}

	public function setCrmEntitySourceTitle(array $entityFields = [])
	{
		$this->setSourceTitle($entityFields['TITLE']);
	}

	// $arResult["canGetPostContent"] = ($reflectionClass->getMethod('initSourceFields')->class == $postProviderClassName);
	public function initSourceFields()
	{
		$entityId = $this->getEntityId();
		$logId = $this->getLogId();

		$fields = $entity = $logEntry = array();

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
				$logEntry['PARAMS'] = unserialize($logEntry['PARAMS']);
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
			elseif ($logEntry['EVENT_ID'] == $this->getLogCommentEventId())
			{
				$this->setSourceDescription($logEntry['MESSAGE']);
				$this->setSourceTitle(truncateText(($logEntry['TITLE'] != '__EMPTY__' ? $logEntry['TITLE'] : $logEntry['MESSAGE']), 100));
			}
		}

		$this->setSourceFields($fields);
	}
}