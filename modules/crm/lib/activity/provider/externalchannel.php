<?php
namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Activity\CommunicationStatistics;
use Bitrix\Crm\ExternalChannelConnectorTable;
use Bitrix\Crm\Rest\CCrmExternalChannelActivityType;
use Bitrix\Crm\Rest\CCrmExternalChannelConnector;
use Bitrix\Crm\Rest\CCrmExternalChannelImport;
use Bitrix\Crm\Rest\CCrmExternalChannelType;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class ExternalChannel extends Base
{
	const PROVIDER_ID = 'CRM_EXTERNAL_CHANNEL';

	protected static function checkRequiredModules()
	{
		return \Bitrix\Main\Loader::includeModule("rest");
	}

	public static function getId()
	{
		return static::PROVIDER_ID;
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL');
	}

	/**
	 * Checks provider status.
	 * @return bool
	 */
	public static function isActive()
	{
		return (ExternalChannelConnectorTable::getCount() > 0);
	}

	/**
	 * Checks provider type status.
	 * @return bool
	 */
	public static function isInUse($typeId)
	{
		return (CCrmExternalChannelType::isDefined(CCrmExternalChannelType::resolveID($typeId))
			&& ExternalChannelConnectorTable::getCount(array('TYPE_ID'=>$typeId)) > 0
		);
	}

	/**
	 * @param string $typeId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
     */
	public static function getListActiveConnector($typeId)
	{
		$result = array();

		$r = ExternalChannelConnectorTable::getList(array('filter'=>array('TYPE_ID'=>$typeId)));

		while($list = $r->Fetch())
		{
			$result[$list['ORIGINATOR_ID']] = $list;
		}

		return $result;
	}

	/**
	 * @return array
     */
	public static function getStatusAnchor()
	{
		$result = array();
		$result['TEXT'] =  (static::isActive() ? Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_PORTRAIT_ACTIVE') : Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_PORTRAIT_INACTIVE'));

		$url = static::getRenderUrl();

		if(!empty($url))
			$result['URL'] = $url;

		return $result;
	}

	public static function getRenderUrl($typeId = CCrmExternalChannelType::OneCName)
	{
		$result = '';

		switch($typeId)
		{
			case CCrmExternalChannelType::CustomName:
			case CCrmExternalChannelType::BitrixName:
			case CCrmExternalChannelType::WordpressName:
			case CCrmExternalChannelType::JoomlaName:
			case CCrmExternalChannelType::DrupalName:
			case CCrmExternalChannelType::MagentoName:
				$result = Option::get('crm', 'path_to_external_channel_list', '/crm/external_channel/');
				break;
			case CCrmExternalChannelType::OneCName:
				if(self::checkRequiredModules())
				{
					$res = \Bitrix\Rest\AppTable::getList(array(
							'filter' => array("CLIENT_ID"=>'app.552d288cc83c88.78059741', 'ACTIVE'=>'Y')
					));
					if (
							($app = $res->fetch())
							&& (intval($app["ID"]) > 0)
					)
					{
						$result = '/marketplace/app/'.$app["ID"].'/';
					}
				}
			break;
		}


		return $result;

	}
	
	/**
	 * @param null|string $providerId Provider id.
	 * @param int $direction Activity direction.
	 * @return bool
	 */
	public static function isTypeEditable($providerId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		return false;
	}

	public static function getTypes()
	{
		return array(
				array(
					'NAME' => Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_ACTIVITY'),
					'PROVIDER_ID' => self::PROVIDER_ID,
					'PROVIDER_TYPE_ID' => CCrmExternalChannelActivityType::ActivityName,
					'DIRECTIONS' => array(
						\CCrmActivityDirection::Incoming => Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_ACTIVITY_IN')
					)
				),
				array(
					'NAME' => Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_IMPORT_AGENT'),
					'PROVIDER_ID' => self::PROVIDER_ID,
					'PROVIDER_TYPE_ID' => CCrmExternalChannelActivityType::ImportAgentName,
					'DIRECTIONS' => array(
						\CCrmActivityDirection::Incoming => Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_IMPORT_AGENT_IN')
					)
				)
		);
	}

	public static function getTypesFilterPresets()
	{
		return array(
			array(
				'NAME' => Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_ACTIVITY'),
				'PROVIDER_TYPE_ID' => CCrmExternalChannelActivityType::ActivityName
			),
			array(
				'NAME' => Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_IMPORT_AGENT'),
				'PROVIDER_TYPE_ID' => CCrmExternalChannelActivityType::ImportAgentName
			)
		);
	}

	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @param int $direction Activity direction.
	 * @return bool
	 */
	public static function getTypeName($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		if ($providerTypeId === CCrmExternalChannelActivityType::ActivityName)
		{
			return Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_ACTIVITY');
		}
		elseif ($providerTypeId === CCrmExternalChannelActivityType::ImportAgentName)
		{
			return Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_IMPORT_AGENT');
		}

		return '';
	}

	/**
	 * @param array $activity Activity data.
	 * @return string Rendered html view for specified mode.
	 */
	public static function renderView(array $activity)
	{
		$external = array();

		$manager = '';
		$title = '';
		$description = '';
		$externalHost = '';
		$urlShot = '';

		$document_type = '';
		$number = '';

		switch($activity['PROVIDER_TYPE_ID'])
		{
			case  CCrmExternalChannelActivityType::ImportAgentName:

				if(is_set($activity['PROVIDER_PARAMS'], CCrmExternalChannelImport::AGENT) &&
						is_set($activity['PROVIDER_PARAMS'][CCrmExternalChannelImport::AGENT], CCrmExternalChannelImport::EXTERNAL_FIELDS)
				)
				{
					$external = $activity['PROVIDER_PARAMS'][CCrmExternalChannelImport::AGENT][CCrmExternalChannelImport::EXTERNAL_FIELDS];
				}
				break;
			case  CCrmExternalChannelActivityType::ActivityName:
			case  CCrmExternalChannelActivityType::ActivityFaceCardName:

				if(is_set($activity['PROVIDER_PARAMS'], CCrmExternalChannelImport::ACTIVITY) &&
						is_set($activity['PROVIDER_PARAMS'][CCrmExternalChannelImport::ACTIVITY], CCrmExternalChannelImport::EXTERNAL_FIELDS)

				)
				{
					$external = $activity['PROVIDER_PARAMS'][CCrmExternalChannelImport::ACTIVITY][CCrmExternalChannelImport::EXTERNAL_FIELDS];
				}

				break;
		}

		if(count($external)>0)
		{
			$document_type = is_set($external, 'EXTERNAL_TYPE_ID') ? $external['EXTERNAL_TYPE_ID']: '';
			$number = is_set($external, 'NUMBER') ? $external['NUMBER']: '';
			$manager = is_set($external, 'MANAGER') ? $external['MANAGER']: '';

			if(is_set($external, 'EXTERNAL_URL') && $external['EXTERNAL_URL']!=='')
			{
				if(isset($activity['ORIGINATOR_ID']))
				{
					/** @var CCrmExternalChannelConnector $connector*/
					$connector = new CCrmExternalChannelConnector();

					$r = $connector->getList(array('filter'=>array(
							'ORIGINATOR_ID'=>$activity['ORIGINATOR_ID'],
							'TYPE_ID'=>$activity['PROVIDER_GROUP_ID']))
					);

					if($result = $r->fetch())
					{
						if(count($result)>0 && is_set($result['EXTERNAL_SERVER_HOST']))
						{
							$externalHost = $result['EXTERNAL_SERVER_HOST'];
						}
					}
				}
				$urlShot = $external['EXTERNAL_URL'];
			}
		}

		$urlDocument = ($urlShot !== '' ? $externalHost.$urlShot: '');

		switch($activity['PROVIDER_TYPE_ID'])
		{
			case  CCrmExternalChannelActivityType::ImportAgentName:
				if($activity['OWNER_TYPE_ID'] == \CCrmOwnerType::Company)
				{
					$title = Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_'.$activity['PROVIDER_TYPE_ID'].'_LABEL_COMPANY_TITLE');
					$showPath = \CComponentEngine::MakePathFromTemplate(
							\COption::GetOptionString('crm', 'path_to_company_show'),
							array('company_id' => $activity['OWNER_ID'])
					);
					$description = '<a href="'.$showPath.'">'.\CCrmOwnerType::GetCaption(\CCrmOwnerType::Company, $activity['OWNER_ID']).'</a>';
				}
				else
				{
					$title = Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_'.$activity['PROVIDER_TYPE_ID'].'_LABEL_CONTACT_TITLE');
					$showPath = \CComponentEngine::MakePathFromTemplate(
							\COption::GetOptionString('crm', 'path_to_contact_show'),
							array('contact_id' => $activity['OWNER_ID'])
					);
					$description = '<a href="'.$showPath.'">'.\CCrmOwnerType::GetCaption(\CCrmOwnerType::Contact, $activity['OWNER_ID']).'</a>';
				}
				break;
			case  CCrmExternalChannelActivityType::ActivityName:
				$title = Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_'.$activity['PROVIDER_TYPE_ID'].'_LABEL_TITLE', array(
								'#SUBJECT#'=> $document_type,
								'#NUMBER#'=> $number)
				);
				$description = Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_'.$activity['PROVIDER_TYPE_ID'].'_LABEL_TEXT', array(
								'#START_TIME#' => $activity['START_TIME'],
								'#RESULT_SUM_CURRENCY#' => \CCrmCurrency::MoneyToString(round($activity['RESULT_SUM'], 2), $activity['RESULT_CURRENCY_ID']))
				);
				break;
			case  CCrmExternalChannelActivityType::ActivityFaceCardName:
				$descriptionType = CCrmExternalChannelActivityType::getAllDescriptions();
				$title = $descriptionType[CCrmExternalChannelActivityType::ActivityFaceCard];

				$description .= ''.Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_ACTIVITY_FACE_CARD_EVENT').$activity['START_TIME'];
				$description .= isset($external['RESULT_PERCENT']) ? '<br><br>'.Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_ACTIVITY_FACE_CARD_RESULT_PERCENT').round($external['RESULT_PERCENT'], 2).'%':'';
				$description .= '<br><br>'.Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_ACTIVITY_FACE_CARD_RESULT_SUM').\CCrmCurrency::MoneyToString(round($activity['RESULT_SUM'], 2), $activity['RESULT_CURRENCY_ID']);
				$description .= $manager<>'' ? '<br><br>'.Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_ACTIVITY_FACE_CARD_MANGER').$manager:'';

				break;
		}

		return '<div class="crm-task-list-1c">
				<div class="crm-task-list-1c-info-container">
					<div class="crm-task-list-1c-info-title">'.Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_ACTIVITY_LABEL_IN').'</div>
					<div class="crm-task-list-1c-info-item">
						<span class="crm-task-list-1c-info-order">'.$title.'</span>
						<span class="crm-task-list-1c-info-order-price">'.$description.'</span>
					</div>
					<div class="crm-task-list-1c-order-link">
						'.($urlDocument ? '<a href="'.$urlDocument.'" class="crm-task-list-1c-order-link-item" target="_blank">'.Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_'.$activity['PROVIDER_TYPE_ID'].'_LABEL_URL').'</a>': '').'
					</div>
				</div>
			</div><!--crm-task-list-1c-->';
	}

	public static function getSupportedCommunicationStatistics()
	{
		return array(
			CommunicationStatistics::STATISTICS_QUANTITY,
			CommunicationStatistics::STATISTICS_MONEY
		);
	}
}