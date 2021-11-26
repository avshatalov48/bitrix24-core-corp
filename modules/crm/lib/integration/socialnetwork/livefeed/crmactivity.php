<?php

namespace Bitrix\Crm\Integration\Socialnetwork\Livefeed;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class CrmActivity extends CrmEntity
{
	public const PROVIDER_ID = 'CRM_ACTIVITY';
	public const CONTENT_TYPE_ID = 'CRM_ACTIVITY';

	public function getEventId(): array
	{
		return [
			\CCrmLiveFeedEvent::ActivityPrefix . \CCrmLiveFeedEvent::Add,
		];
	}

	public function initSourceFields()
	{
		$entityId = $this->getEntityId();

		$fields = array();

		if ($entityId > 0)
		{
			$fields = array(
				'ID' => $entityId
			);

			if ($currentEntity = \CCrmActivity::getById($entityId, false))
			{
				$fields['CURRENT_ENTITY'] = $currentEntity;

				switch($currentEntity['TYPE_ID'])
				{
					case \CCrmActivityType::Meeting:
						$title = Loc::getMessage('CRMINTEGRATION_SONETLF_ENTITY_TITLE_ACTIVITY_MEETING', array(
							'#SUBJECT#' => $currentEntity['SUBJECT'],
						));
						break;
					case \CCrmActivityType::Call:
						$title = Loc::getMessage('CRMINTEGRATION_SONETLF_ENTITY_TITLE_ACTIVITY_CALL', array(
							'#SUBJECT#' => $currentEntity['SUBJECT'],
						));
						break;
					case \CCrmActivityType::Email:
						$title = Loc::getMessage('CRMINTEGRATION_SONETLF_ENTITY_TITLE_ACTIVITY_EMAIL', array(
							'#SUBJECT#' => $currentEntity['SUBJECT'],
						));
						break;
					default:
						$title = Loc::getMessage('CRMINTEGRATION_SONETLF_ENTITY_TITLE_ACTIVITY_DEFAULT', array(
							'#SUBJECT#' => $currentEntity['SUBJECT'],
						));
				}

				$this->setSourceTitle($title);
			}
		}

		$this->setSourceFields($fields);
	}
}