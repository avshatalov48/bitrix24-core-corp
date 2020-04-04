<?
namespace Bitrix\Crm\Integration\Socialnetwork\Livefeed;

use \Bitrix\Socialnetwork\Livefeed\Provider;
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class CrmActivity extends Provider
{
	const PROVIDER_ID = 'CRM_ACTIVITY';
	const CONTENT_TYPE_ID = 'CRM_ACTIVITY';

	public static function getId()
	{
		return static::PROVIDER_ID;
	}

	public function getEventId()
	{
		return array(
			\CCrmLiveFeedEvent::ActivityPrefix.\CCrmLiveFeedEvent::Add
		);
	}

	public function getType()
	{
		return Provider::TYPE_POST;
	}

	public function getCommentProvider()
	{
		$provider = new \Bitrix\Crm\Integration\Socialnetwork\Livefeed\CrmEntityComment();
		return $provider;
	}

	public function initSourceFields()
	{
		$entityId = $this->getEntityId();
		$logId = $this->getLogId();

		$fields = $entity = $logEntry = array();

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
//				$this->setSourceDescription($logEntry['MESSAGE']);
			}
		}

		$this->setSourceFields($fields);
	}

	public function getLiveFeedUrl()
	{
		$result = '';
		$logId = $this->getLogId();

		if ($logId > 0)
		{
			$result = "/crm/stream/?log_id=".$logId;
		}

		return $result;
	}
}