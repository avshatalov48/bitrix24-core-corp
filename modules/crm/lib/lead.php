<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Crm\History\Entity\LeadStatusHistoryTable;
use Bitrix\Crm\History\Entity\LeadStatusHistoryWithSupposedTable;
use Bitrix\Crm\Observer\Entity\ObserverTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\Relations\CascadePolicy;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;

Loc::loadMessages(__FILE__);

/**
 * Class LeadTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Lead_Query query()
 * @method static EO_Lead_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Lead_Result getById($id)
 * @method static EO_Lead_Result getList(array $parameters = array())
 * @method static EO_Lead_Entity getEntity()
 * @method static \Bitrix\Crm\EO_Lead createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_Lead_Collection createCollection()
 * @method static \Bitrix\Crm\EO_Lead wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_Lead_Collection wakeUpCollection($rows)
 */
class LeadTable extends Main\ORM\Data\DataManager
{
	private static $STATUS_INIT = false;
	private static $WORK_STATUSES = array();
	private static $REJECT_STATUSES = array();
	protected static $isCheckUserFields = true;

	public static function getTableName()
	{
		return 'b_crm_lead';
	}

	public static function getUFId()
	{
		return 'CRM_LEAD';
	}

	public static function getMap()
	{
		Container::getInstance()->getLocalization()->loadMessages();

		global $DB;

		$map = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'TITLE' => array(
				'data_type' => 'string'
			),
			'STATUS_ID' => array(
				'data_type' => 'string'
			),
			'STATUS_BY' => array(
				'data_type' => 'Status',
				'reference' => array(
					'=this.STATUS_ID' => 'ref.STATUS_ID',
					'=ref.ENTITY_ID' => array('?', 'STATUS')
				)
			),
			'STATUS_DESCRIPTION' => array(
				'data_type' => 'string'
			),
			'IS_CONVERT' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s = \'CONVERTED\' THEN 1 ELSE 0 END',
					'STATUS_ID'
				),
				'values' => array(0, 1)
			),
			'PRODUCT_ID' => array(
				'data_type' => 'string'
			),
			'OPPORTUNITY' => array(
				'data_type' => 'float'
			),
			'IS_MANUAL_OPPORTUNITY' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y')
			),
			'CURRENCY_ID' => array(
				'data_type' => 'string'
			),
			'OPPORTUNITY_ACCOUNT' => array(
				'data_type' => 'float'
			),
			'ACCOUNT_CURRENCY_ID' => array(
				'data_type' => 'string'
			),
			'COMMENTS' => array(
				'data_type' => 'string'
			),
			'NAME' => array(
				'data_type' => 'string'
			),
			'LAST_NAME' => array(
				'data_type' => 'string'
			),
			'SECOND_NAME' => array(
				'data_type' => 'string'
			),
			'SHORT_NAME' => array(
				'data_type' => 'string',
				'expression' => array(
					$DB->concat("%s","' '", "UPPER(".$DB->substr("%s", 1, 1).")", "'.'"),
					'LAST_NAME', 'NAME'
				)
			),
			'LOGIN' => array(
				'data_type' => 'string',
				'expression' => array('NULL')
			),
			'COMPANY_TITLE' => array(
				'data_type' => 'string'
			),
			'POST' => array(
				'data_type' => 'string'
			),
			'ADDRESS' => array(
				'data_type' => 'string'
			),
			'SOURCE_ID' => array(
				'data_type' => 'string'
			),
			'SOURCE_BY' => array(
				'data_type' => 'Status',
				'reference' => array(
					'=this.SOURCE_ID' => 'ref.STATUS_ID',
					'=ref.ENTITY_ID' => array('?', 'SOURCE')
				)
			),
			'SOURCE_DESCRIPTION' => array(
				'data_type' => 'string'
			),
			'COMPANY_ID' => array(
				'data_type' => 'integer'
			),

			'CONTACT_ID' => array(
				'data_type' => 'integer'
			),
			'OPENED' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y')
			),
			'IS_RETURN_CUSTOMER' => array(
				'data_type' => 'string'
			),

			'DATE_CREATE' => array(
				'data_type' => 'datetime'
			),
			'DATE_CREATE_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'DATE_CREATE'
				)
			),
			'DATE_MODIFY' => array(
				'data_type' => 'datetime'
			),
			'DATE_MODIFY_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'DATE_MODIFY'
				)
			),
			'DATE_CLOSED' => array(
				'data_type' => 'datetime'
			),
			'ASSIGNED_BY_ID' => array(
				'data_type' => 'integer'
			),
			'ASSIGNED_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.ASSIGNED_BY_ID' => 'ref.ID')
			),
			'CREATED_BY_ID' => array(
				'data_type' => 'integer'
			),
			'CREATED_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.CREATED_BY_ID' => 'ref.ID')
			),
			'MODIFY_BY_ID' => array(
				'data_type' => 'integer'
			),
			'MODIFY_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.MODIFY_BY_ID' => 'ref.ID')
			),
			'BIRTHDATE' => array(
				'data_type' => 'date'
			),
			'HONORIFIC' => array(
				'data_type' => 'string'
			),
			'EVENT_RELATION' => array(
				'data_type' => 'EventRelations',
				'reference' => array('=this.ID' => 'ref.ENTITY_ID')
			),
			'STATUS_SEMANTIC_ID' => array(
				'data_type' => 'string'
			),
			'ORIGIN_ID' => array(
				'data_type' => 'string'
			),
			'ORIGINATOR_ID' => array(
				'data_type' => 'string'
			),
			'HAS_EMAIL' => array(
				'data_type' => 'boolean',
				'default_value' => 'N',
				'values' => array('N', 'Y')
			),
			'HAS_PHONE' => array(
				'data_type' => 'boolean',
				'default_value' => 'N',
				'values' => array('N', 'Y')
			),
			'HAS_IMOL' => array(
				'data_type' => 'boolean',
				'default_value' => 'N',
				'values' => array('N', 'Y')
			),
			'PHONE_MOBILE' => array(
				'data_type' => 'string',
				'expression' => array(
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'LEAD\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'PHONE\' '.
						'AND FM.VALUE_TYPE = \'MOBILE\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'PHONE_WORK' => array(
				'data_type' => 'string',
				'expression' => array(
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'LEAD\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'PHONE\' '.
						'AND FM.VALUE_TYPE = \'WORK\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'PHONE_MAILING' => array(
				'data_type' => 'string',
				'expression' => array(
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'LEAD\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'PHONE\' '.
						'AND FM.VALUE_TYPE = \'MAILING\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'EMAIL_HOME' => array(
				'data_type' => 'string',
				'expression' => array(
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'LEAD\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'EMAIL\' '.
						'AND FM.VALUE_TYPE = \'HOME\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'EMAIL_WORK' => array(
				'data_type' => 'string',
				'expression' => array(
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'LEAD\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'EMAIL\' '.
						'AND FM.VALUE_TYPE = \'WORK\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'EMAIL_MAILING' => array(
				'data_type' => 'string',
				'expression' => array(
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'LEAD\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'EMAIL\' '.
						'AND FM.VALUE_TYPE = \'MAILING\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'SKYPE' => array(
				'data_type' => 'string',
				'expression' => array(
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'LEAD\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'IM\' '.
						'AND FM.VALUE_TYPE = \'SKYPE\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'ICQ' => array(
				'data_type' => 'string',
				'expression' => array(
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'LEAD\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'IM\' '.
						'AND FM.VALUE_TYPE = \'ICQ\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'IMOL' => array(
				'data_type' => 'string',
				'expression' => array(
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'LEAD\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'IM\' '.
						'AND FM.VALUE LIKE \'imol|%%\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'EMAIL' => array(
				'data_type' => 'string',
				'expression' => array(
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'LEAD\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'EMAIL\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'PHONE' => array(
				'data_type' => 'string',
				'expression' => array(
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'LEAD\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'PHONE\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'SEARCH_CONTENT' => array(
				'data_type' => 'string'
			),
			new Main\Entity\IntegerField('FACE_ID'),

			(new IntegerField('MOVED_BY_ID'))
				->configureDefaultValue(static function () {
					return Container::getInstance()->getContext()->getUserId();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_MOVED_BY'))
			,

			(new DatetimeField('MOVED_TIME'))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_MOVED_TIME'))
			,

			new Main\Entity\ReferenceField('ADDRESS_ENTITY', AddressTable::getEntity(), array(
				'=this.ID' => 'ref.ENTITY_ID',
				'=ref.TYPE_ID' => new Main\DB\SqlExpression('?', EntityAddressType::Primary),
				'=ref.ENTITY_TYPE_ID' => new Main\DB\SqlExpression('?', \CCrmOwnerType::Lead)
			)),
			new Main\Entity\ReferenceField('PRODUCT_ROW', ProductRowTable::getEntity(), array(
				'=this.ID' => 'ref.OWNER_ID',
				'=ref.OWNER_TYPE' => new Main\DB\SqlExpression('?', \CCrmOwnerTypeAbbr::Lead),
			)),
			new Main\Entity\ReferenceField(
				'HISTORY',
				LeadStatusHistoryTable::class,
				Main\ORM\Query\Join::on('this.ID', 'ref.OWNER_ID')
			),
			new ReferenceField(
				'FULL_HISTORY',
				LeadStatusHistoryWithSupposedTable::class,
				Main\ORM\Query\Join::on('this.ID', 'ref.OWNER_ID'),
				array('join_type' => 'INNER')
			),
			new ReferenceField(
				'BINDING_CONTACT',
				Binding\LeadContactTable::class,
				Main\ORM\Query\Join::on('this.ID', 'ref.LEAD_ID')
			),
			new Main\Entity\IntegerField('WEBFORM_ID'),
			(new OneToMany('CONTACT_BINDINGS', Binding\LeadContactTable::class, 'LEAD'))
				->configureCascadeDeletePolicy(CascadePolicy::FOLLOW),
			(new OneToMany('PRODUCT_ROWS', ProductRowTable::class, 'LEAD_OWNER'))
				// products will be deleted in onAfterDelete, if it's needed
				->configureCascadeDeletePolicy(CascadePolicy::NO_ACTION),
			(new OneToMany('OBSERVER_IDS', ObserverTable::class, 'LEAD'))
				->configureCascadeDeletePolicy(CascadePolicy::FOLLOW),
		);

		$codeList = UtmTable::getCodeList();

		foreach ($codeList as $fieldName)
		{
			$map[] = new Main\Entity\ReferenceField($fieldName, UtmTable::getEntity(), array(
				'=ref.ENTITY_TYPE_ID' => new Main\DB\SqlExpression('?', \CCrmOwnerType::Lead),
				'=this.ID' => 'ref.ENTITY_ID',
				'=ref.CODE' => new Main\DB\SqlExpression('?', $fieldName)
			));
		}

		return $map;
	}

	private static function ensureStatusesLoaded()
	{
		if(self::$STATUS_INIT)
		{
			return;
		}

		global $DB;

		$convertStatus = null;
		$arStatuses = array();
		$rsStatuses = $DB->Query('SELECT STATUS_ID, SORT FROM b_crm_status WHERE ENTITY_ID = \'STATUS\'');
		while($arStatus = $rsStatuses->Fetch())
		{
			if(!$convertStatus && strval($arStatus['STATUS_ID']) === 'CONVERTED')
			{
				$convertStatus = $arStatus;
				continue;
			}

			$arStatuses[$arStatus['STATUS_ID']] = $arStatus;
		}

		self::$WORK_STATUSES = array();
		self::$REJECT_STATUSES = array();

		if($convertStatus)
		{
			$convertStatusSort = intval($convertStatus['SORT']);
			foreach($arStatuses as $statusID => $arStatus)
			{
				$sort = intval($arStatus['SORT']);
				if($sort < $convertStatusSort)
				{
					self::$WORK_STATUSES[] = '\''.$DB->ForSql($statusID).'\'';
				}
				elseif($sort > $convertStatusSort)
				{
					self::$REJECT_STATUSES[] = '\''.$DB->ForSql($statusID).'\'';
				}
			}
		}

		self::$STATUS_INIT = true;
	}

	public static function processQueryOptions(&$options)
	{
		$stub = '_BX_STATUS_STUB_';
		self::ensureStatusesLoaded();
		$options['WORK_STATUS_IDS'] = '('.(!empty(self::$WORK_STATUSES) ? implode(',', self::$WORK_STATUSES) : "'$stub'").')';
		$options['REJECT_STATUS_IDS'] = '('.(!empty(self::$REJECT_STATUSES) ? implode(',', self::$REJECT_STATUSES) : "'$stub'").')';
	}

	public static function disableUserFieldsCheck(): void
	{
		static::$isCheckUserFields = false;
	}

	protected static function checkUfFields($object, $ufdata, $result)
	{
		if (!static::$isCheckUserFields)
		{
			static::$isCheckUserFields = true;
			return;
		}

		parent::checkUfFields($object, $ufdata, $result);
	}

	public static function onAfterUpdate(Event $event): EventResult
	{
		$item = $event->getParameter('object');
		if (!$item)
		{
			return new EventResult();
		}

		$result = new EventResult();
		ProductRowTable::handleOwnerUpdate($item, $result);

		return $result;
	}
}
