<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Crm\Integration\Channel\IChannelInfo;
use Bitrix\Crm\Integration\Channel\IChannelGroupInfo;
use Bitrix\Crm\Integration\Channel\ChannelTrackerManager;
use Bitrix\Crm\Integration\Channel\ChannelType;
use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\Widget\FilterPeriodType;
use Bitrix\Crm\Widget\Data\Activity\ChannelStatistics as ActivityChannelStatistics;
use Bitrix\Crm\Widget\Data\Company\DealSumStatistics;
use Bitrix\Main\Entity;

Loc::loadMessages(__FILE__);

class CCrmStartPageComponent extends CBitrixComponent
{
	/** @var string */
	protected $guid = '';
	/** @var string */
	protected $widgetGuid = '';
	/** @var array|null */
	private $errors = array();
	/** @var Bitrix\Crm\Widget\Filter|null */
	private $commonFilter = null;

	public function executeComponent()
	{
		if (!Bitrix\Main\Loader::includeModule('crm'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED');
			return;
		}
		if(!CCrmPerms::IsAccessEnabled())
		{
			ShowError(GetMessage('CRM_PERMISSION_DENIED'));
			return;
		}

		if (isset($this->arParams['GUID']))
		{
			$this->guid = $this->arParams['GUID'];
		}
		if ($this->guid === '')
		{
			$this->guid = 'start';
		}

		if (isset($this->arParams['WIDGET_GUID']))
		{
			$this->widgetGuid = $this->arParams['WIDGET_GUID'];
		}
		if ($this->widgetGuid === '')
		{
			$this->widgetGuid = 'start_widget';
		}

		//region Filter
		$filterOptions = new Main\UI\Filter\Options($this->widgetGuid);
		$filterFields = $filterOptions->getFilter(array(
			array('id' => 'RESPONSIBLE_ID'),
			array('id' => 'PERIOD')
		));

		Filter::convertPeriodFromDateType($filterFields, 'PERIOD');
		$filterFields = Filter::internalizeParams($filterFields);
		Filter::sanitizeParams($filterFields);

		$this->commonFilter = new Filter($filterFields);
		//endregion

		ChannelTrackerManager::initializeUserContext();

		$this->arResult['GUID'] = $this->guid;
		$this->arResult['WIDGET_GUID'] = $this->widgetGuid;
		$this->arResult["PERIOD"] = array("START" => null, "END" => null);
		$this->arResult['ITEMS'] = array();
		$this->arResult['GROUP_ITEMS'] = array();

		if (!$this->commonFilter->isEmpty())
		{
			$period = $this->commonFilter->getPeriod();
			/* @var \Bitrix\Main\Type\Date $period["START"] */
			if ($period["START"] instanceof \Bitrix\Main\Type\Date)
				$this->arResult["PERIOD"]["START"] = $period["START"]->format("Y-m-d");
			/* @var \Bitrix\Main\Type\Date $period["END"] */
			$this->arResult["PERIOD"]["END"] = $period["END"]->format("Y-m-d");
		}

		$this->arResult['CURRENCY_ID'] = CCrmCurrency::GetAccountCurrencyID();

		$this->includeComponentTemplate();
	}
}

class CCrmStartPageComponentCRMCounters {
	/** @var CCrmStartPageComponentCRMCounters */
	static $instance = null;
	/** @var Filter */
	protected $filter;
	private static $personalCounterData = array(
		'pointer' => array(),
		'dbRes' => null
	);
	private static $dbResPointer = array();

	public function __construct()
	{
		$this->filter = new Filter(array('periodType' => FilterPeriodType::CURRENT_DAY));
	}

	/**
	 * @param Filter $filter
	 * @return void
	 */
	public function setFilter(Filter $filter)
	{
		if ($filter && !$filter->isEmpty())
		{
			Bitrix\Crm\Widget\Filter::merge($filter, $this->filter, array('overridePeriod' => true));
		}
	}

	/**
	 * @return Filter
	 */
	public function getFilter()
	{
		return $this->filter;
	}
	/**
	 * @return array
	 * @throws Main\ObjectNotFoundException
	 */
	public function getSaleCounters()
	{
		$generalCntr = array();
		$personalCntr = array();

		//region Sale statistic
		$source = new DealSumStatistics(array());

		$results = $source->getList(
			array(
				'filter' => $this->getFilter(),
				'select' => array(array('name' => 'SUCCESS_SUM', 'aggregate' => 'SUM')),
				'group' => array(
					DealSumStatistics::GROUP_BY_DATE,
					DealSumStatistics::GROUP_BY_USER
				)
			)
		);
		foreach($results as $deal)
		{
			if (!isset($generalCntr[$deal['DATE']]))
				$generalCntr[$deal['DATE']] = array(
					"VALUE" => 0
				);
			$generalCntr[$deal['DATE']]['VALUE'] += $deal['SUCCESS_SUM'];
			if (!isset($personalCntr[$deal['USER_ID']]))
				$personalCntr[$deal['USER_ID']] = array(
					"ID" => $deal['USER_ID'],
					"NAME" => $deal['USER'],
					"AVATAR" => $deal["USER_PHOTO_ID"],
					"VALUE" => 0
				);
			$personalCntr[$deal['USER_ID']]['VALUE'] += $deal['SUCCESS_SUM'];
		}
		self::prepareUserAvatarInCounters($personalCntr);
		return array($generalCntr, array_values($personalCntr));
	}

	private function initPersonalCountersQueries()
	{
		self::$personalCounterData['pointer'] = array(
			'allFailedActs',
			'leadsWithoutActs',
			'dealsWithoutAtcs'
		);
		reset(self::$personalCounterData['pointer']);
		self::$personalCounterData['dbRes'] = null;
	}

	private function getPersonalCountersQueries($endTime)
	{
		$pointer = current(self::$personalCounterData['pointer']);
		$return = false;
		while ($pointer)
		{
			if (self::$personalCounterData['dbRes'] === null)
			{
				//region Get all fails from acts
				if ($pointer == 'allFailedActs')
				{
					$dbRes = Bitrix\Crm\ActivityTable::getList(array(
						'select' => array(
							new ExpressionField('CNT', "COUNT(*)"),
							'RESPONSIBLE_ID'
						),
						'filter' => array(
							'<=DEADLINE' => $endTime,
							'=COMPLETED' => 'N'
						),
						'group' => array(
							'RESPONSIBLE_ID'
						),
						'order' => array(
							'CNT' => 'DESC'
						),
						'limit' => 5
					));
					$users = array();
					while ($res = $dbRes->fetch())
						$users[] = $res['RESPONSIBLE_ID'];
					if (!empty($users))
					{
						self::$personalCounterData['dbRes'] = Bitrix\Crm\ActivityTable::getList(array(
							'select' => array(
								new ExpressionField('CNT', "COUNT(*)"),
								'RESPONSIBLE_ID',
								'OWNER_TYPE_ID',
								'NAME' => 'ASSIGNED_BY.NAME',
								'LAST_NAME' => 'ASSIGNED_BY.LAST_NAME',
								'SECOND_NAME' => 'ASSIGNED_BY.SECOND_NAME',
								'LOGIN' => 'ASSIGNED_BY.LOGIN',
								'TITLE' => 'ASSIGNED_BY.TITLE',
								'PERSONAL_PHOTO' => 'ASSIGNED_BY.PERSONAL_PHOTO',
								'WORK_POSITION' => 'ASSIGNED_BY.WORK_POSITION'
							),
							'filter' => array(
								'<=DEADLINE' => $endTime,
								'=COMPLETED' => 'N',
								'@RESPONSIBLE_ID' => $users
							),
							'group' => array(
								'RESPONSIBLE_ID',
								'OWNER_TYPE_ID',
								'ASSIGNED_BY.NAME',
								'ASSIGNED_BY.LAST_NAME',
								'ASSIGNED_BY.SECOND_NAME',
								'ASSIGNED_BY.LOGIN',
								'ASSIGNED_BY.TITLE',
								'ASSIGNED_BY.PERSONAL_PHOTO'
							),
							'order' => array(
								'OWNER_TYPE_ID' => 'ASC'
							)
						));
					}
				}
				//endregion
				//region Get leads without acts
				else if ($pointer == 'leadsWithoutActs')
				{
					self::$personalCounterData['dbRes'] = Bitrix\Crm\LeadTable::getList(array(
						'select' => array(
							new ExpressionField('CNT', "COUNT(*)"),
							new ExpressionField('OWNER_TYPE_ID', \CCrmOwnerType::Lead),
							'ASSIGNED_BY_RESPONSIBLE_ID' => 'ASSIGNED_BY_ID',
							'ASSIGNED_BY_NAME' => 'ASSIGNED_BY.NAME',
							'ASSIGNED_BY_LAST_NAME' => 'ASSIGNED_BY.LAST_NAME',
							'ASSIGNED_BY_SECOND_NAME' => 'ASSIGNED_BY.SECOND_NAME',
							'ASSIGNED_BY_LOGIN' => 'ASSIGNED_BY.LOGIN',
							'ASSIGNED_BY_TITLE' => 'ASSIGNED_BY.TITLE',
							'ASSIGNED_BY_PERSONAL_PHOTO' => 'ASSIGNED_BY.PERSONAL_PHOTO',
							'ASSIGNED_BY_WORK_POSITION' => 'ASSIGNED_BY.WORK_POSITION'
						),
						'filter' => array(
							'IS_CONVERT' => false,
							'=ACTS.ID' => null
						),
						'group' => array(
							'ASSIGNED_BY_ID',
							'ASSIGNED_BY.NAME',
							'ASSIGNED_BY.LAST_NAME',
							'ASSIGNED_BY.SECOND_NAME',
							'ASSIGNED_BY.LOGIN',
							'ASSIGNED_BY.TITLE',
							'ASSIGNED_BY.PERSONAL_PHOTO'
						),
						'order' => array(
							'CNT' => 'DESC'
						),
						'limit' => 5,
						'runtime' => array(
							new Entity\ReferenceField(
								'ACTS',
								Entity\Base::compileEntity(
									'AbsentActs',
									array(
										new Entity\IntegerField('ID', array('primary' => true)),
										new Entity\IntegerField('OWNER_ID')
									),
									array(
										'table_name' => '(SELECT AB.ID, AB.OWNER_ID '.
											'FROM b_crm_act_bind AB, b_crm_act A '.
											'WHERE (AB.OWNER_TYPE_ID='.\CCrmOwnerType::Lead.' AND A.ID=AB.ACTIVITY_ID AND A.COMPLETED="N"))'
									)
								),
								array('=this.ID' => 'ref.OWNER_ID'),
								array('join_type' => 'left outer')
							)
						)
					));
				}
				//endregion
				//region Get deals without acts
				else if ($pointer == 'dealsWithoutAtcs')
				{
					self::$personalCounterData['dbRes'] = Bitrix\Crm\DealTable::getList(array(
						'select' => array(
							new ExpressionField('CNT', "COUNT(*)"),
							new ExpressionField('OWNER_TYPE_ID', \CCrmOwnerType::Deal),
							'ASSIGNED_BY_RESPONSIBLE_ID' => 'ASSIGNED_BY_ID',
							'ASSIGNED_BY_NAME' => 'ASSIGNED_BY.NAME',
							'ASSIGNED_BY_LAST_NAME' => 'ASSIGNED_BY.LAST_NAME',
							'ASSIGNED_BY_SECOND_NAME' => 'ASSIGNED_BY.SECOND_NAME',
							'ASSIGNED_BY_LOGIN' => 'ASSIGNED_BY.LOGIN',
							'ASSIGNED_BY_TITLE' => 'ASSIGNED_BY.TITLE',
							'ASSIGNED_BY_PERSONAL_PHOTO' => 'ASSIGNED_BY.PERSONAL_PHOTO',
							'ASSIGNED_BY_POSITION' => 'ASSIGNED_BY.WORK_POSITION'
						),
						'filter' => array(
							'=CLOSED' => "N",
							'=ACTS.ID' => null
						),
						'group' => array(
							'ASSIGNED_BY_ID',
							'ASSIGNED_BY.NAME',
							'ASSIGNED_BY.LAST_NAME',
							'ASSIGNED_BY.SECOND_NAME',
							'ASSIGNED_BY.LOGIN',
							'ASSIGNED_BY.TITLE',
							'ASSIGNED_BY.PERSONAL_PHOTO'
						),
						'order' => array(
							'CNT' => 'DESC'
						),
						'limit' => 5,
						'runtime' => array(
							new Entity\ReferenceField(
								'ACTS',
								Entity\Base::compileEntity(
									'AbsentDealActs',
									array(
										new Entity\IntegerField('ID', array('primary' => true)),
										new Entity\IntegerField('OWNER_ID')
									),
									array(
										'table_name' => '(SELECT AB.ID, AB.OWNER_ID '.
											'FROM b_crm_act_bind AB, b_crm_act A '.
											'WHERE (AB.OWNER_TYPE_ID='.\CCrmOwnerType::Deal.' AND A.ID=AB.ACTIVITY_ID AND A.COMPLETED="N"))'
									)
								),
								array('=this.ID' => 'ref.OWNER_ID'),
								array('join_type' => 'left outer')
							)
						)
					));
				}
				//region Get leads without acts
			}
			if (self::$personalCounterData['dbRes'] && ($return = self::$personalCounterData['dbRes']->fetch()))
			{
				if (isset($return['ASSIGNED_BY_RESPONSIBLE_ID']))
				{
					$res = array();
					foreach ($return as $k => $v)
					{
						if (strpos($k, 'ASSIGNED_BY_') === 0)
							$k = substr($k, 12);
						$res[$k] = $v;
					}
					$return = $res;
				}
				break;
			}

			$pointer = next(self::$personalCounterData['pointer']);
			self::$personalCounterData['dbRes'] = null;
		}
		return $return;
	}

	/**
	 * @param bool $previousDay Make statistic for previous day.
	 * @return array
	 */
	public function getPersonalCounters($previousDay = false)
	{
		$this->initPersonalCountersQueries();
		$result = array();
		$format = \CSite::GetNameFormat(false);
		$startTime = new DateTime();
		$startTime->setTime(0,0,0);
		$endTime = new DateTime();
		if ($previousDay === true)
		{
			$startTime->add(\DateInterval::createFromDateString('-1 day'));
			$endTime->add(\DateInterval::createFromDateString('-1 day'));
			$endTime->setTime(23, 59, 59);
		}

		\CTimeZone::Disable();
		while ($res = $this->getPersonalCountersQueries($endTime))
		{
			if (!isset($result[$res['RESPONSIBLE_ID']]))
			{
				$result[$res['RESPONSIBLE_ID']] = array(
					'ID' => $res['RESPONSIBLE_ID'],
					'NAME' => strip_tags(\CUser::FormatName($format, $res, true, false)),
					'AVATAR' => $res['PERSONAL_PHOTO'],
					'WORK_POSITION' => $res['WORK_POSITION'],
					'VALUE' => array(),
					'TOTAL' => array(0, 0)
				);
			}
			$name = \CCrmOwnerType::ResolveName($res["OWNER_TYPE_ID"]);
			if (!isset($result[$res["RESPONSIBLE_ID"]]["VALUE"][$name]))
				$result[$res["RESPONSIBLE_ID"]]["VALUE"][$name] = array(0, 0);
			$result[$res["RESPONSIBLE_ID"]]["VALUE"][$name][0] += $res["CNT"];
			$result[$res["RESPONSIBLE_ID"]]["TOTAL"][0] += $res["CNT"];
		}

		uasort($result, array('CCrmStartPageComponentCRMCounters', 'sortPersonalStatistic'));

		if (!empty($result))
		{
			if ($dbRes = Bitrix\Crm\ActivityTable::getList(array(
				'select' => array(
					new ExpressionField('CNT', "COUNT(*)"),
					'RESPONSIBLE_ID',
					'OWNER_TYPE_ID'
				),
				'filter' => array(
					'>=LAST_UPDATED' => $startTime,
					'<=LAST_UPDATED' => $endTime,
					'=COMPLETED' => 'Y',
					'@RESPONSIBLE_ID' => array_keys($result)
				),
				'group' => array(
					'RESPONSIBLE_ID',
					'OWNER_TYPE_ID'
				),
				'order' => array(
					'OWNER_TYPE_ID' => 'ASC'
				)
			)))
			{
				while ($res = $dbRes->Fetch())
				{
					$name = \CCrmOwnerType::ResolveName($res["OWNER_TYPE_ID"]);
					if (isset($result[$res["RESPONSIBLE_ID"]]["VALUE"][$name]))
					{
						$result[$res["RESPONSIBLE_ID"]]["VALUE"][$name][1] += $res["CNT"];
					}
				}
			}
		}

		\CTimeZone::Enable();
		self::prepareUserAvatarInCounters($result);
		$result = array_slice($result, 0, 5);
		return $result;
	}

	/**
	 * @return CCrmStartPageComponentCRMCounters
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
			self::$instance = new static;
		return self::$instance;
	}

	public static function prepareUserAvatarInCounters(&$counter, $avatarSize = array("width" => 54, "height" => 54))
	{
		foreach ($counter as $k => $v)
		{
			if ($counter[$k]["AVATAR"] > 0)
			{
				$counter[$k]["AVATAR"] = \CFile::ResizeImageGet(
					$counter[$k]["AVATAR"],
					$avatarSize,
					BX_RESIZE_IMAGE_PROPORTIONAL,
					true
				);
			}
		}
	}
	/**
	 * @param $a
	 * @param $b
	 */
	public static function sortPersonalStatistic($a, $b )
	{
		if (is_array($b["TOTAL"]))
		{
			if ($a["TOTAL"][0] < $b["TOTAL"][0])
				return 1;
			else if ($a["TOTAL"][0] = $b["TOTAL"][0])
				return 0;
			return -1;
		}

		if ($a["TOTAL"] < $b["TOTAL"])
			return 1;
		else if ($a["TOTAL"] = $b["TOTAL"])
			return 0;
		return -1;
	}

	public static function sendJsonResponse($response)
	{
		global $APPLICATION;
		$APPLICATION->restartBuffer();
		while(ob_end_clean()); // hack!
		header('Content-Type:application/json; charset=UTF-8');
		echo \Bitrix\Main\Web\Json::encode($response);
		/** @noinspection PhpUndefinedClassInspection */
		\CMain::finalActions();
		die;


	}
}