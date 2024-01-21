<?php
namespace Bitrix\Crm\Widget\Data\Activity;
use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\LeadTable;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Pseudoactivity\Entity\WaitTable;
use Bitrix\Crm\UserActivityTable;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\DB\SqlHelper;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\UserTable;
use Bitrix\Main\Web\Uri;

class ManagerCounters extends DataSource
{
	const TYPE_NAME = 'ACTIVITY_MANAGER_COUNTERS';
	static $counters = array();
	static $subordinates = null;
	private static $personalCounterData = array(
		'pointer' => array(),
		'dbRes' => null,
		'users' => array()
	);
	private static $messagesLoaded = array();
	/**
	 * @return string
	 */
	public function getTypeName()
	{
		return self::TYPE_NAME;
	}

	public function getAttributes()
	{
		return array("isConfigurable" => false);
	}

	/**
	 * @param array $params
	 * @return array
	 */
	public function getList(array $params)
	{
		$this->initPersonalCountersQueries();
		$result = array();
		$format = \CSite::GetNameFormat(false);
		$startTime = new DateTime();
		$startTime->setTime(0,0,0);
		$endTime = new DateTime();
		$endTime->setTime(23, 59, 59);

		$entities = array(
			\CCrmOwnerType::Lead,
			\CCrmOwnerType::Contact,
			\CCrmOwnerType::Company,
			\CCrmOwnerType::Deal
		);

		\CTimeZone::Disable();
		while ($res = $this->getPersonalCountersQueries($endTime))
		{
			if (!in_array($res["OWNER_TYPE_ID"], $entities))
				continue;
			if (!isset($result[$res['RESPONSIBLE_ID']]))
			{
				$result[$res['RESPONSIBLE_ID']] = array(
					'USER_ID' => $res['RESPONSIBLE_ID'],
					'USER' => \CUser::FormatName($format, $res, true, true),
					'USER_PHOTO' => $res['PERSONAL_PHOTO'],
					'WORK_POSITION' => htmlspecialcharsbx($res['WORK_POSITION']),
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

		uasort($result, array(__CLASS__, 'sortPersonalStatistic'));

		if (!empty($result))
		{
			if ($dbRes = ActivityTable::getList(array(
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

			if ($dbRes = WaitTable::getList(array(
				'select' => array(
					new ExpressionField('CNT', 'COUNT(*)'),
					'AUTHOR_ID',
					'OWNER_TYPE_ID'
				),
				'filter' => array(
					'=COMPLETED' => 'N',
					'=AUTHOR_ID' => array_keys($result),
				),
			)))
			{
				while ($res = $dbRes->Fetch())
				{
					$name = \CCrmOwnerType::ResolveName($res["OWNER_TYPE_ID"]);
					$result[$res["AUTHOR_ID"]]["VALUE"][$name][0] -= $res["CNT"];
					$result[$res["AUTHOR_ID"]]["TOTAL"][0] -= $res["CNT"];
					$result[$res["AUTHOR_ID"]]["VALUE"][$name][0] = max(0, $result[$res["AUTHOR_ID"]]["VALUE"][$name][0]);
					$result[$res["AUTHOR_ID"]]["TOTAL"][0] = max(0, $result[$res["AUTHOR_ID"]]["TOTAL"][0]);
				}
			}
		}

		return array(
			array(
				'title' => GetMessage("CRM_MANAGER_CNTR_TITLE"),
				'html' => self::parseTemplate($result),
			)
		);
	}

	/**
	 *
	 * @return string
	 */
	/**
	 * @param array $result Array kind of: array(...,
	array(
		"ID" => 6,
		"NAME" => 'Sergey',
		"AVATAR" => Array(
			"src" => "/avatar-man-3.png",
			"width" => "98",
			"height" => "98",
			"size" => "6388",
		),
		"WORK_POSITION" => "lead softdeveloper",
		"VALUE" => Array(
		"LEAD" => Array(24, 0),
		"DEAL" => Array(6, 0),
		"COMPANY" => Array(6, 0),
		"CONTACT" => Array(3, 0)
		)
	), ...
	 * )
	 * @return mixed
	 */
	private static function parseTemplate(array $result)
	{
		foreach ($result as $k => $user)
		{
			$result[$k]["TOTAL"][0] = 0;
			$result[$k]["TOTAL"][1] = 0;
			foreach ($user["VALUE"] as $ownerTypeId => $val)
			{
				$val = is_array($val) ? $val : array(0, 0);
				$result[$k]["TOTAL"][0] += ($val[0] > 0 ? $val[0] : 0);
				$result[$k]["TOTAL"][1] += ($val[1] > 0 ? $val[1] : 0);
			}
		}
		uasort($result, array(__CLASS__, 'sortPersonalStatistic'));
		unset($user);
		$mess = self::includeModuleFile();
		\CTimeZone::Enable();
		self::prepareUserAvatarInCounters($result);
		$result = array_slice($result, 0, 5);
		$innerHTML = <<<HTML
				<div class="crm-start-channel-header crm-start-channel-header-big">
					<div class="crm-start-channel-header-title">
				</div>
					<div class="crm-start-channel-header-title-column">
						<div class="crm-start-channel-header-title-inner">{$mess["CRM_MANAGER_CNTR_SUCCEED"]}</div>
						<div class="crm-start-channel-header-title-outer">{$mess["CRM_MANAGER_CNTR_FAILED"]}</div>
					</div>
				</div>
HTML;


		$entities = array(
			\CCrmOwnerType::LeadName => Loc::getMessage("CRM_MANAGER_CNTR_LEAD"),
			\CCrmOwnerType::ContactName => Loc::getMessage("CRM_MANAGER_CNTR_CONTACT"),
			\CCrmOwnerType::CompanyName => Loc::getMessage("CRM_MANAGER_CNTR_COMPANY"),
			\CCrmOwnerType::DealName => Loc::getMessage("CRM_MANAGER_CNTR_DEAL")
		);

		$max = array(0, 0);
		foreach ($result as $userId => $user)
		{
			$max[0] = max($max[0], $user["TOTAL"][0]);
			$max[1] = max($max[1], $user["TOTAL"][1]);
		}
		foreach ($result as $userId => $user)
		{
			$user["STYLE_USER_PHOTO"] = ($user["USER_PHOTO"] && $user["USER_PHOTO"]["src"] ? ' style="background-image:url('. Uri::urnEncode(htmlspecialcharsbx($user["USER_PHOTO"]["src"])).');"' : "");
			$k0 = max(($max[0] > 0 ? intval($user["TOTAL"][0] * 100 / $max[0]) : 0), 1);
			$k1 = max(($max[1] > 0 ? intval($user["TOTAL"][1] * 100 / $max[1]) : 0), 1);
			$position = $user["WORK_POSITION"];
			$class = ($position !== '' ? '' : ' crm-start-channel-username-without-position');
			$innerHTML .= <<<HTML
					<div class="crm-start-channel-item crm-start-channel-item-manager">
						<div class="crm-start-channel-item-header" data-role="open-chanel">
							<div class="crm-start-channel-open"></div>
							<div class="crm-start-channel-title">
								<div class="ui-icon ui-icon-common-user crm-start-channel-user-avatar">
									<i {$user["STYLE_USER_PHOTO"]}></i>
								</div>
								<div class="crm-start-channel-username$class">{$user["USER"]}</div>
								<div class="crm-start-channel-user-position">{$user["WORK_POSITION"]}</div>
							</div>
							<div class="crm-start-channel-graph">
								<div class="crm-start-channel-graph-half">
									<div class="crm-start-channel-graph-total" title="{$user["TOTAL"][1]}">{$user["TOTAL"][1]}</div>
									<div class="crm-start-channel-graph-line" style="max-width:{$k1}%;"></div>
								</div>
								<div class="crm-start-channel-graph-half crm-start-channel-graph-half-right">
									<div class="crm-start-channel-graph-line" style="max-width:{$k0}%;"></div>
									<div class="crm-start-channel-graph-total" title="{$user["TOTAL"][0]}">{$user["TOTAL"][0]}</div>
								</div>
							</div>
						</div>
						<div class="crm-start-channel-deep" data-role="inner-chanel">
							<div class="crm-start-channel-deep-wrapper">
HTML;
			$maxInner = array(0, 0);
			foreach ($entities as $entityId => $entityName)
			{
				$user["VALUE"][$entityId][0] = ($user["VALUE"][$entityId][0] ?? 0);
				$user["VALUE"][$entityId][1] = ($user["VALUE"][$entityId][1] ?? 0);
				$maxInner[0] = max($maxInner[0], $user["VALUE"][$entityId][0]);
				$maxInner[1] = max($maxInner[1], $user["VALUE"][$entityId][1]);
			}
			foreach ($entities as $entityId => $entityName)
			{
				$p0 = max(($max[0] > 0 ? intval($user["VALUE"][$entityId][0] * 100 / $max[0]) : 0), 1);
				$p1 = max(($max[1] > 0 ? intval($user["VALUE"][$entityId][1] * 100 / $max[1]) : 0), 1);
				$innerHTML .= <<<HTML
								<div class="crm-start-channel-deep-item">
									<div class="crm-start-channel-deep-title">$entityName</div>
									<div class="crm-start-channel-graph">
										<div class="crm-start-channel-graph-half">
											<div class="crm-start-channel-graph-total" title="{$user["VALUE"][$entityId][1]}">{$user["VALUE"][$entityId][1]}</div>
											<div class="crm-start-channel-graph-line" style="max-width:$p1%;"></div>
										</div>
										<div class="crm-start-channel-graph-half crm-start-channel-graph-half-right">
											<div class="crm-start-channel-graph-line" style="max-width:$p0%;"></div>
											<div class="crm-start-channel-graph-total" title="{$user["VALUE"][$entityId][0]}">{$user["VALUE"][$entityId][0]}</div>
										</div>
									</div>
								</div>
HTML;
			}

			$innerHTML .= <<<HTML
							</div>
						</div>
					</div>
HTML;
		}
		$id = randString(10);
		$innerHTML .= <<<HTML
<script>
BX.ready(function() {
	var node = BX('custom_widget_{$id}');
	if (node && (node = BX.findParent(node, {className : "crm-widget-container"})) && node)
		BX.addClass(node, "crm-widget-container-height-auto");
	if (node && (node = BX.findParent(node, {className : "crm-widget-row"})) && node)
		BX.addClass(node, "crm-widget-row-height-auto");
	var openChanelDeeper = function() {
			var block				= this.parentNode,
				blockDeeper 		= block.querySelector('[data-role="inner-chanel"]'),
				blockDeeperHeight 	= blockDeeper.firstElementChild.offsetHeight,
				i;

			if (block.classList.contains('crm-start-channel-item-open'))
			{
				block.classList.remove('crm-start-channel-item-open');
				blockDeeper.style.height = '';

				var blockDeeperInner = block.querySelectorAll('[data-role="inner-chanel"]');

				for (i = 0; i < blockDeeperInner.length; i++)
				{
					blockDeeperInner[i].parentNode.classList.remove('crm-start-channel-item-open');
					blockDeeperInner[i].style.height = '';
				}
			}
			else
			{
				block.classList.add('crm-start-channel-item-open');
				blockDeeper.style.height = blockDeeperHeight + 'px';
				setTimeout(function () { blockDeeper.style.height = 'auto'; }, 220);
			}
		}, 
		showChanelButton = node.querySelectorAll('[data-role="open-chanel"]'), i;
	for (i = 0; i < showChanelButton.length; i++)
		BX.bind(showChanelButton[i], "click", openChanelDeeper);
});
</script>
HTML;
		return '<div id="custom_widget_'.$id.'">'.$innerHTML.'</div>';
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

	private function getPersonalCountersQueries()
	{
		$pointer = current(self::$personalCounterData['pointer']);
		$return = false;
		$startTime = new DateTime();
		$startTime->setTime(0,0,0);
		$endTime = new DateTime();
		$endTime->setTime(23, 59, 59);
		$helper = Application::getConnection()->getSqlHelper();

		while ($pointer)
		{
			if (self::$personalCounterData['dbRes'] === null)
			{
				//region Get all fails from acts
				if ($pointer == 'allFailedActs')
				{
					$dbRes = ActivityBindingTable::getList(array(
						'select' => array(
							new ExpressionField('CNT', "COUNT(DISTINCT " . $helper->getConcatFunction('%s', "'_'", '%s') . ")", ['OWNER_TYPE_ID', 'OWNER_ID']),
							'TODAY' => new ExpressionField('TODAY',
								'CASE WHEN %s >= '.Application::getConnection()->getSqlHelper()->getCharToDateFunction($startTime->format("Y-m-d H:i:s")).' THEN 1 ELSE 0 END', array('AD.DEADLINE')),
							'RESPONSIBLE_ID' => 'AD.RESPONSIBLE_ID'
						),
						'filter' => (array(
							'<=AD.DEADLINE' => $endTime,
							'=AD.COMPLETED' => 'N',
							array(
								'LOGIC' => 'OR',
								'!OWNER_TYPE_ID' => new SqlExpression('?i', \CCrmOwnerType::Deal),
								array(
									'LOGIC' => 'AND',
									'=DD.STAGE_SEMANTIC_ID' => PhaseSemantics::PROCESS,
									'=DD.IS_RECURRING' => 'N'
								)
							),
							array(
								'LOGIC' => 'OR',
								'!OWNER_TYPE_ID' => new SqlExpression('?i', \CCrmOwnerType::Lead),
								'=DL.STATUS_SEMANTIC_ID' => PhaseSemantics::PROCESS
							),
							array(
								'LOGIC' => 'OR',
								'!OWNER_TYPE_ID' => new SqlExpression('?i', \CCrmOwnerType::Company),
								'=DC.IS_MY_COMPANY' => 'N'
							)
						) + (!\CCrmPerms::IsAdmin($this->getUserID()) ? array('@AD.RESPONSIBLE_ID' => $this->getSubordinates()) : array())),
						'group' => array(
							'AD.RESPONSIBLE_ID',
							'TODAY'
						),
						'order' => array(
							'CNT' => 'DESC'
						),
						'runtime' => array(
							'AD' => new ReferenceField('AD',
								ActivityTable::getEntity(),
								array(
									'this.ACTIVITY_ID' => 'ref.ID'
								),
								array(
									'join_type' => 'INNER')
							),
							'ASSIGNED_BY' => new ReferenceField('ASSIGNED_BY',
								UserTable::getEntity(),
								array(
									'=ref.ID' => 'this.AD.RESPONSIBLE_ID',
									'=ref.ACTIVE' => new SqlExpression('?s', 'Y')
								),
								array('join_type' => 'INNER')
							),
							'DD' => new ReferenceField('DD',
								DealTable::getEntity(),
								array(
									'this.OWNER_TYPE_ID' => new SqlExpression('?i', \CCrmOwnerType::Deal),
									'=ref.ID' => 'this.OWNER_ID',
									'=ref.ASSIGNED_BY_ID' => 'this.AD.RESPONSIBLE_ID'
								),
								array(
									'join_type' => 'LEFT')
							),
							'DL' => new ReferenceField('DL',
								LeadTable::getEntity(),
								array(
									'this.OWNER_TYPE_ID' => new SqlExpression('?i', \CCrmOwnerType::Lead),
									'=ref.ID' => 'this.OWNER_ID'
								),
								array(
									'join_type' => 'LEFT')
							),
							'DC' => new ReferenceField('DC',
								CompanyTable::getEntity(),
								array(
									'this.OWNER_TYPE_ID' => new SqlExpression('?i', \CCrmOwnerType::Company),
									'=ref.ID' => 'this.OWNER_ID',
									'=ref.IS_MY_COMPANY' => new SqlExpression('?s', 'N'),
									'=ref.CATEGORY_ID' => new SqlExpression('?i', 0)
								),
								array(
									'join_type' => 'LEFT')
							)
						),
						'limit' => 50
					));
					$users = array();
					while ($res = $dbRes->fetch())
						$users[] = $res['RESPONSIBLE_ID'];
					if (!empty($users))
					{
						self::$personalCounterData['dbRes'] = ActivityBindingTable::getList(array(
							'select' => array(
								new ExpressionField('CNT', "COUNT(DISTINCT " . $helper->getConcatFunction('%s', "'_'", '%s') . ")", array('OWNER_TYPE_ID', 'OWNER_ID')),
								'TODAY' => new ExpressionField('TODAY',
									'CASE WHEN %s >= '.Application::getConnection()->getSqlHelper()->getCharToDateFunction($startTime->format("Y-m-d H:i:s")).' THEN 1 ELSE 0 END', array('AD.DEADLINE')),
								'RESPONSIBLE_ID' => 'AD.RESPONSIBLE_ID',
								'OWNER_TYPE_ID' => 'OWNER_TYPE_ID'
							),
							'filter' => array(
								'<=AD.DEADLINE' => $endTime,
								'=AD.COMPLETED' => 'N',
								'@AD.RESPONSIBLE_ID' => $users,
								array(
									'LOGIC' => 'OR',
									'!OWNER_TYPE_ID' => new SqlExpression('?i', \CCrmOwnerType::Deal),
									array(
										'LOGIC' => 'AND',
										'=DD.STAGE_SEMANTIC_ID' => PhaseSemantics::PROCESS,
										'=DD.IS_RECURRING' => 'N'
									)
								),
								array(
									'LOGIC' => 'OR',
									'!OWNER_TYPE_ID' => new SqlExpression('?i', \CCrmOwnerType::Lead),
									'=DL.STATUS_SEMANTIC_ID' => PhaseSemantics::PROCESS
								),
								array(
									'LOGIC' => 'OR',
									'!OWNER_TYPE_ID' => new SqlExpression('?i', \CCrmOwnerType::Company),
									'=DC.IS_MY_COMPANY' => 'N'
								)
							),
							'group' => array(
								'TODAY',
								'AD.RESPONSIBLE_ID',
								'OWNER_TYPE_ID'
							),
							'order' => array(
								'OWNER_TYPE_ID' => 'ASC'
							),
							'runtime' => array(
								'AD' => new ReferenceField('AD',
									ActivityTable::getEntity(),
									array(
										'this.ACTIVITY_ID' => 'ref.ID'
									),
									array(
										'join_type' => 'INNER')
								),
								'DD' => new ReferenceField('DD',
									DealTable::getEntity(),
									array(
										'this.OWNER_TYPE_ID' => new SqlExpression('?i', \CCrmOwnerType::Deal),
										'=ref.ID' => 'this.OWNER_ID',
										'=ref.ASSIGNED_BY_ID' => 'this.AD.RESPONSIBLE_ID'
									),
									array(
										'join_type' => 'LEFT')
								),
								'DL' => new ReferenceField('DL',
									LeadTable::getEntity(),
									array(
										'this.OWNER_TYPE_ID' => new SqlExpression('?i', \CCrmOwnerType::Lead),
										'=ref.ID' => 'this.OWNER_ID'
									),
									array(
										'join_type' => 'LEFT')
								),
								'DC' => new ReferenceField('DC',
									CompanyTable::getEntity(),
									array(
										'this.OWNER_TYPE_ID' => new SqlExpression('?i', \CCrmOwnerType::Company),
										'=ref.ID' => 'this.OWNER_ID',
										'=ref.IS_MY_COMPANY' => new SqlExpression('?s', 'N'),
										'=ref.CATEGORY_ID' => new SqlExpression('?i', 0)
									),
									array(
										'join_type' => 'LEFT')
								)
							)
						));
					}
				}
				//endregion
				//region Get leads without acts
				else if ($pointer == 'leadsWithoutActs' && \CCrmUserCounterSettings::GetValue(\CCrmUserCounterSettings::ReckonActivitylessItems, true))
				{
					self::$personalCounterData['dbRes'] = \Bitrix\Crm\LeadTable::getList(array(
						'select' => array(
							new ExpressionField('CNT', "COUNT(*)"),
							new ExpressionField('OWNER_TYPE_ID', \CCrmOwnerType::Lead),
							'ASSIGNED_BY_RESPONSIBLE_ID' => 'ASSIGNED_BY_ID',
						),
						'filter' => (array(
							'=STATUS_SEMANTIC_ID' => PhaseSemantics::PROCESS,
							'==UA.OWNER_ID' => null,
						) + (!\CCrmPerms::IsAdmin($this->getUserID()) ? array('@ASSIGNED_BY_ID' => $this->getSubordinates()) : array())),
						'group' => array(
							'ASSIGNED_BY_ID',
						),
						'order' => array(
							'CNT' => 'DESC'
						),
						'limit' => 50,
						'runtime' => array(
							new ReferenceField('ASSIGNED_BY',
								UserTable::getEntity(),
								array(
									'=ref.ID' => 'this.ASSIGNED_BY_ID',
									'=ref.ACTIVE' => new SqlExpression('?s', 'Y')
								),
								array('join_type' => 'INNER')
							),
							new ReferenceField('UA',
								UserActivityTable::getEntity(),
								array(
									'=ref.OWNER_ID' => 'this.ID',
									'=ref.OWNER_TYPE_ID' => new SqlExpression(\CCrmOwnerType::Lead),
									'=ref.USER_ID' => new SqlExpression(0)
								),
								array('join_type' => 'LEFT')
							),
							new ReferenceField('W',
								WaitTable::getEntity(),
								array(
									'=ref.OWNER_ID' => 'this.ID',
									'=ref.OWNER_TYPE_ID' => new SqlExpression(\CCrmOwnerType::Lead),
									'=ref.COMPLETED' => new SqlExpression('?s', 'N')
								),
								array('join_type' => 'LEFT')
							)
						)
					));
				}
				//endregion
				//region Get deals without acts
				else if ($pointer == 'dealsWithoutAtcs' && \CCrmUserCounterSettings::GetValue(\CCrmUserCounterSettings::ReckonActivitylessItems, true))
				{
					self::$personalCounterData['dbRes'] = \Bitrix\Crm\DealTable::getList(array(
						'select' => array(
							new ExpressionField('CNT', "COUNT(*)"),
							new ExpressionField('OWNER_TYPE_ID', \CCrmOwnerType::Deal),
							'ASSIGNED_BY_RESPONSIBLE_ID' => 'ASSIGNED_BY_ID'
						),
						'filter' => (array(
								'=STAGE_SEMANTIC_ID' => PhaseSemantics::PROCESS,
								'=IS_RECURRING' => 'N',
								'==UA.OWNER_ID' => null,
							) + (!\CCrmPerms::IsAdmin($this->getUserID()) ? array('@ASSIGNED_BY_ID' => $this->getSubordinates()) : array())),
						'group' => array(
							'ASSIGNED_BY_ID'
						),
						'order' => array(
							'CNT' => 'DESC'
						),
						'limit' => 50,
						'runtime' => array(
							new ReferenceField('ASSIGNED_BY',
								UserTable::getEntity(),
								array(
									'=ref.ID' => 'this.ASSIGNED_BY_ID',
									'=ref.ACTIVE' => new SqlExpression('?s', 'Y')
								),
								array('join_type' => 'INNER')
							),
							new ReferenceField('UA',
								UserActivityTable::getEntity(),
								array(
									'=ref.OWNER_ID' => 'this.ID',
									'=ref.OWNER_TYPE_ID' => new SqlExpression(\CCrmOwnerType::Deal),
									'=ref.USER_ID' => new SqlExpression(0)
								),
								array('join_type' => 'LEFT')
							),
							new ReferenceField('W',
								WaitTable::getEntity(),
								array(
									'=ref.OWNER_ID' => 'this.ID',
									'=ref.OWNER_TYPE_ID' => new SqlExpression(\CCrmOwnerType::Deal),
									'=ref.COMPLETED' => new SqlExpression('?s', 'N')
								),
								array('join_type' => 'LEFT')
							)
						)
					));
				}
				// endregion
				//region Get User names
				if (self::$personalCounterData['dbRes'] && ($res = self::$personalCounterData['dbRes']->fetch()))
				{
					$userList = array();
					$recoverDbRes = array();
					do
					{
						$recoverDbRes[] = $res;
						$userKey = isset($res['ASSIGNED_BY_RESPONSIBLE_ID']) ? 'ASSIGNED_BY_RESPONSIBLE_ID' : 'RESPONSIBLE_ID';
						if (isset($res[$userKey]) && !isset(self::$personalCounterData["users"][$res[$userKey]]))
						{
							$userList[] = $res[$userKey];
						}
					} while ($res = self::$personalCounterData['dbRes']->fetch());
					if (!empty($userList))
					{
						$dbRes = \CUser::getList("ID", "ASC",
							array("ID" => implode("|", array_unique($userList))),
							array("FIELDS" => array("ID", "NAME", "LAST_NAME", "SECOND_NAME", "LOGIN", "TITLE", "PERSONAL_PHOTO", "WORK_POSITION")));
						while($u = $dbRes->fetch())
						{
							self::$personalCounterData["users"][$u["ID"]] = array(
								"USER_ID" => $u["ID"],
								"NAME" => $u["NAME"],
								"LAST_NAME" => $u["LAST_NAME"],
								"SECOND_NAME" => $u["SECOND_NAME"],
								"LOGIN" => $u["LOGIN"],
								"TITLE" => $u["TITLE"],
								"PERSONAL_PHOTO" => $u["PERSONAL_PHOTO"],
								"WORK_POSITION" => $u["WORK_POSITION"]
							);
						}
					}
					foreach ($recoverDbRes as $k => $r)
					{
						$userID = isset($r['ASSIGNED_BY_RESPONSIBLE_ID']) ? $r['ASSIGNED_BY_RESPONSIBLE_ID'] : $r['RESPONSIBLE_ID'];
						if (isset(self::$personalCounterData["users"][$userID]))
						{
							$recoverDbRes[$k] = array_merge(
								$r,
								array("RESPONSIBLE_ID" => $userID),
								self::$personalCounterData["users"][$userID]
							);
						}
					}
					self::$personalCounterData['dbRes'] = new \Bitrix\Main\DB\ArrayResult($recoverDbRes);
				}
				// endregion
			}

			if (self::$personalCounterData['dbRes'] && ($return = self::$personalCounterData['dbRes']->fetch()))
				break;
			$pointer = next(self::$personalCounterData['pointer']);
			self::$personalCounterData['dbRes'] = null;
		}
		return $return;
	}

	/**
	 * Returns user subordinates.
	 * @return array
	 */
	private function getSubordinates()
	{
		if (self::$subordinates !== null)
			return self::$subordinates;
		self::$subordinates = array();
		if (\Bitrix\Main\Loader::includeModule("intranet"))
		{
			$dbRes = \CIntranetUtils::getSubordinateEmployees($this->getUserID(), true);
			while ($res = $dbRes->fetch())
				self::$subordinates[] = $res["ID"];
		}
		self::$subordinates[] = $this->getUserID();
		return self::$subordinates;
	}

	/**
	 * @param $counter
	 * @param array $avatarSize
	 * @return void
	 */
	private static function prepareUserAvatarInCounters(&$counter, $avatarSize = array("width" => 65, "height" => 65))
	{
		foreach ($counter as $k => $v)
		{
			if (!is_array($counter[$k]["USER_PHOTO"]) && $counter[$k]["USER_PHOTO"] > 0)
			{
				$counter[$k]["USER_PHOTO"] = \CFile::ResizeImageGet(
					$counter[$k]["USER_PHOTO"],
					$avatarSize,
					BX_RESIZE_IMAGE_PROPORTIONAL,
					true,
					false,
					true
				);
			}
		}
	}
	/**
	 * @return array
	 */
	protected static function includeModuleFile()
	{
		if (empty(self::$messagesLoaded))
		{
			Loc::loadMessages(__FILE__);
			self::$messagesLoaded = Loc::loadLanguageFile(__FILE__);
		}
		return self::$messagesLoaded;
	}
	/**
	 * @param $a
	 * @param $b
	 */
	private static function sortPersonalStatistic($a, $b )
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
	/**
	 * Initialize Demo data.
	 * @param array $data Data.
	 * @param array $params Parameters.
	 * @return array
	 */
	public function initializeDemoData(array $data, array $params)
	{
		return array(
			"items" => array(
				array(
					"title" => isset($data["title"]) ? $data["title"] : GetMessage("CRM_MANAGER_CNTR_TITLE"),
					"html" => isset($data["html"]) ? $data["html"] : self::parseTemplate($data["items"])
				)
			),
			"attributes" => $this->getAttributes()
		);
	}
}


