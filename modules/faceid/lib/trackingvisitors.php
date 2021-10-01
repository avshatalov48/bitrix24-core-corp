<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage faceid
 * @copyright  2001-2016 Bitrix
 */

namespace Bitrix\Faceid;

use Bitrix\Crm;
use Bitrix\Crm\LeadTable;
use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

\Bitrix\Main\Loader::includeModule('crm');

/**
 * Class TrackingVisitorsTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> FACE_ID int mandatory
 * <li> CRM_ID int mandatory
 * <li> VK_ID string(50) mandatory
 * <li> FIRST_VISIT datetime mandatory
 * <li> PRELAST_VISIT datetime mandatory
 * <li> LAST_VISIT datetime mandatory
 * <li> LAST_VISIT_ID int mandatory
 * <li> VISITS_COUNT int mandatory
 * </ul>
 *
 * @package Bitrix\Faceid
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TrackingVisitors_Query query()
 * @method static EO_TrackingVisitors_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_TrackingVisitors_Result getById($id)
 * @method static EO_TrackingVisitors_Result getList(array $parameters = array())
 * @method static EO_TrackingVisitors_Entity getEntity()
 * @method static \Bitrix\Faceid\EO_TrackingVisitors createObject($setDefaultValues = true)
 * @method static \Bitrix\Faceid\EO_TrackingVisitors_Collection createCollection()
 * @method static \Bitrix\Faceid\EO_TrackingVisitors wakeUpObject($row)
 * @method static \Bitrix\Faceid\EO_TrackingVisitors_Collection wakeUpCollection($rows)
 */

class TrackingVisitorsTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_faceid_tracking_visitors';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
			)),
			new Main\Entity\IntegerField('FILE_ID'),
			new Main\Entity\IntegerField('FACE_ID'),
			new Main\Entity\IntegerField('CRM_ID'),
			new Main\Entity\StringField('VK_ID'),
			new Main\Entity\DatetimeField('FIRST_VISIT'),
			new Main\Entity\DatetimeField('PRELAST_VISIT'),
			new Main\Entity\DatetimeField('LAST_VISIT'),
			new Main\Entity\IntegerField('LAST_VISIT_ID'),
			new Main\Entity\IntegerField('VISITS_COUNT'),
		);
	}
	/**
	 * Returns validators for VK_ID field.
	 *
	 * @return array
	 */
	public static function validateVkId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}

	/**
	 * @param int|int[] $faceId
	 *
	 * @return array|mixed
	 */
	public static function getCrmInfoByFace($faceId)
	{
		/** @var int[] $faceIds faceId => faceId */
		$faceIds = is_array($faceId) ? array_unique($faceId) : array($faceId);
		$faceIds = array_combine($faceIds, $faceIds);

		$crmRecords = array();

		if (!empty($faceIds))
		{
			// try contacts
			$result = \Bitrix\Crm\ContactTable::getList(array(
				'select' => array('ID', 'FACE_ID', 'FULL_NAME'),
				'filter' => array(
					'@FACE_ID' => $faceIds
				),
				'order' => array('ID' => 'DESC')
			));

			foreach ($result as $row)
			{
				$crmRecords[$row['FACE_ID']] = array(
					'TYPE' => \CCrmOwnerType::Contact,
					'ID' => $row['ID'],
					'TITLE' => $row['FULL_NAME'],
					'URL' => '/crm/contact/show/'.$row['ID'].'/'
				);
				unset($faceIds[$row['FACE_ID']]);
			}
		}

		if (!empty($faceIds))
		{
			// try leads
			$result = \Bitrix\Crm\LeadTable::getList(array(
				'select' => array('ID', 'FACE_ID', 'TITLE'),
				'filter' => array(
					'@FACE_ID' => $faceIds
				),
				'order' => array('ID' => 'DESC')
			));

			foreach ($result as $row)
			{
				$crmRecords[$row['FACE_ID']] = array(
					'TYPE' => \CCrmOwnerType::Lead,
					'ID' => $row['ID'],
					'TITLE' => $row['TITLE'],
					'URL' => '/crm/lead/show/'.$row['ID'].'/'
				);
				unset($faceIds[$row['FACE_ID']]);
			}
		}

		return is_array($faceId) ? $crmRecords : $crmRecords[$faceId];
	}

	/**
	 * Creates lead based on visitor
	 *
	 * @param array|int $visitor
	 * @param string $leadTitle
	 *
	 * @return array|bool
	 */
	public static function createCrmLead($visitor, $leadTitle)
	{
		if (!is_array($visitor))
		{
			$visitor = \Bitrix\Faceid\TrackingVisitorsTable::getRowById($visitor);
		}

		if (empty($visitor))
		{
			return false;
		}

		$lead = array(
			'FACE_ID' => $visitor['FACE_ID'],
			'TITLE' => $leadTitle
		);

		// get lead source
		$leadSource = \Bitrix\Main\Config\Option::get('faceid', 'ftracker_lead_source');
		if($leadSource <> '')
		{
			// check if it still exists
			$sources = \CCrmStatus::GetStatusList('SOURCE');
			if(isset($sources[$leadSource]))
			{
				$lead['SOURCE_ID'] = $leadSource;
			}
		}

		// create lead
		$entity = new \CCrmLead(false);
		$entity->Add($lead, true, array('DISABLE_USER_FIELD_CHECK' => true));

		if ($lead['ID'])
		{
			$arErrors = array();
			\CCrmBizProcHelper::AutoStartWorkflows(
				\CCrmOwnerType::Lead,
				$lead['ID'],
				\CCrmBizProcEventType::Create,
				$arErrors
			);
			if (class_exists('\Bitrix\Crm\Automation\Starter'))
			{
				$starter = new Crm\Automation\Starter(\CCrmOwnerType::Lead, $lead['ID']);
				$starter->setContextModuleId('faceid')->setUserIdFromCurrent()->runOnAdd();
			}
		}

		return $lead;
	}

	public static function registerCrmActivity($visitor, $photoContent, $crmEntityType = null, $crmEntityId = null)
	{
		global $USER;

		// visitor
		if (!is_array($visitor))
		{
			$visitor = \Bitrix\Faceid\TrackingVisitorsTable::getRowById($visitor);
			if (empty($visitor))
			{
				return false;
			}
		}

		// find crm associations
		if (empty($crmEntityType))
		{
			$crmRecord = static::getCrmInfoByFace($visitor['FACE_ID']);

			if ($crmRecord)
			{
				$crmEntityType = $crmRecord['TYPE'];
				$crmEntityId = $crmRecord['ID'];
			}
			else
			{
				return false;
			}
		}

		// copy file
		$fileClone = array(
			'name' => 'face_'.$visitor['FACE_ID'].'.jpg',
			'type' => 'image/jpeg',
			'content' => $photoContent,
			'MODULE_ID' => 'crm',
		);

		$fileId = \CFile::SaveFile($fileClone, 'crm');

		// register activity
		$selector = new \Bitrix\Crm\Integrity\ActualEntitySelector();
		$selector->setEntity($crmEntityType, $crmEntityId)->search();
		$bindingSelector = new \Bitrix\Crm\Activity\BindingSelector($selector);
		$bindings  = $bindingSelector ->getBindings();

		// save activity
		$activityFields = array(
			'PROVIDER_ID' => \Bitrix\Crm\Activity\Provider\Visit::PROVIDER_ID,
			'PROVIDER_TYPE_ID' => \Bitrix\Crm\Activity\Provider\Visit::TYPE_VISIT,
			'START_TIME' => new \Bitrix\Main\Type\DateTime,
			'COMPLETED' => 'Y',
			'PRIORITY' => \CCrmActivityPriority::Medium,
			'SUBJECT' => Loc::getMessage("FACEID_ACTIVITY_VISIT"),
			'DESCRIPTION' => "",
			'DESCRIPTION_TYPE' => \CCrmContentType::PlainText,
			'LOCATION' => '',
			'NOTIFY_TYPE' => \CCrmActivityNotifyType::None,
			'BINDINGS' => $bindings,
			'SETTINGS' => array(),
			'PROVIDER_PARAMS' => array('tracker_photo' => $fileId),
			'AUTHOR_ID' => $USER->GetID(),
			'RESPONSIBLE_ID' => $USER->GetID(),
			'STORAGE_TYPE_ID' => \Bitrix\Crm\Integration\StorageType::File,
			'STORAGE_ELEMENT_IDS' => array($fileId) // может быть удален?
		);

		$activityId = \CCrmActivity::Add($activityFields, true, true, array('REGISTER_SONET_EVENT' => true));

		if ($activityId > 0)
		{
			$communications = array(
				array(
					'ID' => 0,
					'ENTITY_ID' => $crmEntityId,
					'ENTITY_TYPE_ID' => $crmEntityType
				)
			);

			\CCrmActivity::SaveCommunications($activityId, $communications, $activityFields, true, false);
		}

		if ($activityId > 0)
		{
			//Execute automation trigger
			\Bitrix\Crm\Automation\Trigger\VisitTrigger::execute($bindings, array('ACTIVITY_ID' => $activityId));
		}
	}

	public static function toJson($visitor, $confidence = 0, $returnAsArray = false)
	{
		$visitInfo = FormatDate('j F, H:i', $visitor['LAST_VISIT']->getTimestamp()).' | ';

		if ($visitor['VISITS_COUNT'] == 1)
		{
			$visitInfo .= Loc::getMessage('FACEID_VISITORS_NEW');
		}
		else
		{
			$visitInfo .= sprintf(
				Loc::getMessage('FACEID_VISITOR_VISITS'),
				$visitor['VISITS_COUNT'], FormatDate('j F, H:i', $visitor['PRELAST_VISIT']->getTimestamp())
			);
		}

		// crm info
		if (!isset($visitor['CRM']))
		{
			$visitor['CRM'] = \Bitrix\Faceid\TrackingVisitorsTable::getCrmInfoByFace($visitor['FACE_ID']);
		}

		$jsonResult = array(
			'visitor_id' => $visitor['ID'],
			'visit_info' => $visitInfo,
			'last_visit' => (string) $visitor['LAST_VISIT'],
			'last_visit_ts' => $visitor['LAST_VISIT']->getTimestamp(),
			'prelast_visit' => (string) $visitor['PRELAST_VISIT'],
			'visits_count' => $visitor['VISITS_COUNT'],
			'name' => $visitor['CRM'] ? $visitor['CRM']['TITLE'] : Loc::getMessage('FACEID_VISITOR')." ".$visitor['ID'],
			'crm_url' => $visitor['CRM'] ? $visitor['CRM']['URL'] : '',
			'vk_id' => $visitor['VK_ID'],
			'shot_src' => \CFile::GetPath($visitor['FILE_ID']),
			'confidence' => $confidence
		);

		return $returnAsArray ? $jsonResult : \Bitrix\Main\Web\Json::encode($jsonResult);
	}
}