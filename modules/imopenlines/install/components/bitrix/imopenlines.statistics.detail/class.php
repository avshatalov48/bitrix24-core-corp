<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Loader,
	\Bitrix\Main\Grid\Panel,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\Main\HttpApplication;

use \Bitrix\Imopenlines\Limit,
	\Bitrix\ImOpenLines\Config,
	\Bitrix\ImOpenLines\Session,
	\Bitrix\ImOpenlines\Security,
	\Bitrix\ImOpenLines\Model\SessionTable,
	\Bitrix\ImOpenlines\Security\Permissions;

class ImOpenLinesComponentStatisticsDetail extends CBitrixComponent
{
	protected $gridId = 'imopenlines_statistic_v3';
	protected $filterId = 'imopenlines_statistic_detail_filter';

	/** @var  \Bitrix\Main\Grid\Options */
	protected $gridOptions;
	protected $excelMode = false;
	protected $enableExport = true;
	/** @var Security\Permissions */
	protected $userPermissions;
	protected $showHistory;
	protected $configId;
	private $enableNextPage;

	/**
	 * @param $status
	 * @param $close
	 * @return bool
	 */
	protected static function isNotCloseSession($status, $close): bool
	{
		$result = false;

		if(
			$status < Session::STATUS_WAIT_CLIENT &&
			$close !== 'Y'
		)
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function init()
	{
		$this->enableExport = Limit::canStatisticsExcel();
		$this->userPermissions = Security\Permissions::createWithCurrentUser();

		$this->gridOptions = new CGridOptions($this->gridId);

		if(isset($_REQUEST['EXPORT_TYPE']) && $_REQUEST['EXPORT_TYPE'] === 'excel'  && $this->enableExport)
		{
			$this->excelMode = true;
		}

		if($this->enableExport)
		{
			$this->arResult['STEXPORT_PARAMS'] = [
				'SITE_ID' => SITE_ID,
				'EXPORT_TYPE' => 'excel',
				'COMPONENT_NAME' => 'bitrix:imopenlines.statistics.detail',
			];
			if ($this->listKeysSignedParameters())
			{
				$this->arResult['STEXPORT_PARAMS']['signedParameters'] = $this->getSignedParameters();
			}

			$this->arResult['BUTTON_EXPORT'] = 'BX.UI.StepProcessing.ProcessManager.get(\'OpenLinesExport\').showDialog()';
			$this->arResult['LIMIT_EXPORT'] = false;

			$this->arResult['STEXPORT_TOTAL_ITEMS'] = (isset($this->arParams['STEXPORT_TOTAL_ITEMS']) ?
				(int)$this->arParams['STEXPORT_TOTAL_ITEMS'] : 0);
			$this->enableNextPage = false;
		}
		else
		{
			$this->arResult['BUTTON_EXPORT'] = 'BX.UI.InfoHelper.show(\'' . Limit::INFO_HELPER_LIMIT_CONTACT_CENTER_STATISTICS_EXCEL . '\');';
			$this->arResult['LIMIT_EXPORT'] = true;
		}

		$request = HttpApplication::getInstance()->getContext()->getRequest();

		$this->configId = 0;
		if ($request->get('CONFIG_ID'))
		{
			$this->configId = $request->get('CONFIG_ID');
			$config = Config::getInstance()->get($request->get('CONFIG_ID'));
			$this->arResult['LINE_NAME'] = $config['LINE_NAME'];
		}

		$this->arResult['LINES'] = $this->getConfigList();
		$this->arResult['groupActionsData'] = $this->getGroupActionsData();
	}

	/**
	 * @return array
	 */
	protected function getConfigList(): array
	{
		$lines = [];

		$configManager = new Config();
		$result = $configManager->getList([
				'select' => [
					'ID',
					'LINE_NAME'
				],
				'filter' => ['=TEMPORARY' => 'N'],
				'order' => ['LINE_NAME']
		],
			['QUEUE' => 'N']
		);

		foreach ($result as $id => $config)
		{
			$lines[$config['ID']] = htmlspecialcharsbx($config['LINE_NAME']);
		}
		return $lines;
	}

	/**
	 * @param array $lines
	 * @return array[]
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function getGroupActionsData(): array
	{
		$result = [
			'transfer' => [
				'items' => [],
				'inputId' => 'action-transfer-text',
				'inputName' => 'transferText',
			]
		];

		if(Limit::canTransferToLine())
		{
			$lines = Config::getQueueList(Security\Helper::getCurrentUserId());

			usort($lines, function($a, $b)
			{
				if($a['TRANSFER_COUNT'] > $b['TRANSFER_COUNT'])
				{
					return -1;
				}
				elseif($a['TRANSFER_COUNT'] < $b['TRANSFER_COUNT'])
				{
					return 1;
				}
				else
				{
					if($a['ID'] > $b['ID'])
					{
						return 1;
					}
					elseif($a['ID'] < $b['ID'])
					{
						return -1;
					}
				}

				return 0;
			});

			if(!empty($lines))
			{
				foreach ($lines as $line)
				{
					$result['transfer']['items'][] = [
						'id' => $line['ID'],
						'entityId' => 'open-line',
						'tabs' => 'open-lines',
						'title' => $line['NAME']
					];
				}
			}
		}

		return $result;
	}

	protected function checkModules()
	{
		if (!Loader::includeModule('imopenlines'))
		{
			\ShowError(Loc::getMessage('OL_COMPONENT_MODULE_NOT_INSTALLED'));
			return false;
		}

		if (!Loader::includeModule('imconnector'))
		{
			\ShowError(Loc::getMessage('OL_COMPONENT_MODULE_NOT_INSTALLED'));
			return false;
		}
		return true;
	}

	protected function checkAccess()
	{
		if(!$this->userPermissions->canPerform(Security\Permissions::ENTITY_SESSION, Security\Permissions::ACTION_VIEW))
		{
			\ShowError(Loc::getMessage('OL_COMPONENT_ACCESS_DENIED'));
			return false;
		}

		return true;
	}

	public static function getFormattedCrmColumn($row)
	{
		$crmData = Array();
		$crmLinks = self::getCrmLink($row['data']);
		if (!empty($crmLinks))
		{
			foreach ($crmLinks as $type => $link)
			{
				$crmData[] = '<a href="' . $link . '" title="" target="_blank">'.self::getCrmName($type).'</a>';
			}
		}

		$crmActivityLink = self::getCrmActivityLink($row['data']);
		if (!empty($crmActivityLink))
		{
			$crmData[] = '<a href="'.$crmActivityLink.'" title="" target="_blank">'.self::getCrmName('ACTIVITY').'</a>';
		}

		if (empty($crmData))
		{
			$result = Loc::getMessage('OL_COMPONENT_TABLE_NO');
		}
		else
		{
			$result = implode('<br>', $crmData);
		}

		return $result;
	}

	private static function getCrmName($type)
	{
		$name = '';

		if (\CModule::IncludeModule('crm'))
		{
			$name = CCrmOwnerType::GetDescription(CCrmOwnerType::ResolveID($type));
		}

		return $name;
	}

	/**
	 * @param $row
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	private static function getCrmLink($row)
	{
		$result = [];

		$domain = \Bitrix\ImOpenLines\Common::getServerAddress();

		if ($row['CRM'] == 'Y' && $row['CRM_ACTIVITY_ID'] > 0)
		{
			$crmEntitiesManager = \Bitrix\ImOpenLines\Crm\Common::getActivityBindings($row['CRM_ACTIVITY_ID']);

			if($crmEntitiesManager->isSuccess())
			{
				foreach ($crmEntitiesManager->getData() as $type => $id)
				{
					if(!empty($id))
					{
						$result[$type] = $domain . \Bitrix\ImOpenLines\Crm\Common::getLink($type, $id);
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param $row
	 * @return bool|mixed|string
	 * @throws \Bitrix\Main\LoaderException
	 */
	private static function getCrmActivityLink($row)
	{
		$result = '';

		$domain = \Bitrix\ImOpenLines\Common::getServerAddress();

		if ($row['CRM'] == 'Y' && $row['CRM_ACTIVITY_ID'] > 0)
		{
			$result = $domain . \Bitrix\ImOpenLines\Crm\Common::getLink('ACTIVITY', $row['CRM_ACTIVITY_ID']);
		}

		return $result;
	}

	private static function formatDate($date)
	{
		if (!$date)
		{
			return '-';
		}

		return formatDate('x', $date->toUserTime()->getTimestamp(), (time() + \CTimeZone::getOffset()));
	}

	private static function formatDuration($duration)
	{
		$duration = intval($duration);
		if ($duration <= 0)
			return '-';

		$currentTime = new \Bitrix\Main\Type\DateTime();
		$formatTime = $currentTime->getTimestamp()-$duration;
		if ($duration < 3600)
		{
			$result = \FormatDate(Array(
				's' => 'sdiff',
				'i' => 'idiff',
			), $formatTime);
		}
		elseif ($duration >= 3600 && $duration < 86400)
		{

			$formatTime = $currentTime->getTimestamp()-$duration;
			$result = \FormatDate('Hdiff', $formatTime);

			if ($duration % 3600 != 0)
			{
				$formatTime = $currentTime->getTimestamp()-($duration % 3600);
				$result = $result .' '. \FormatDate(Array(
				's' => 'sdiff',
				'i' => 'idiff',
				), $formatTime);
			}
		}
		elseif ($duration >= 86400)
		{

			$formatTime = $currentTime->getTimestamp()-$duration;
			$result = \FormatDate('ddiff', $formatTime);

			if ($duration % 86400 != 0 && ceil($duration % 86400) > 3600)
			{
				$formatTime = $currentTime->getTimestamp()-ceil($duration % 86400);
				$result = $result .' '. \FormatDate(Array(
					'i' => 'idiff',
					'H' => 'Hdiff',
				), $formatTime);
			}
		}
		else
		{
			$result = '';
		}

		return $result;
	}

	/**
	 * @param $sessionId
	 * @param $rating
	 * @param string $field
	 * @return string
	 */
	private static function formatVote($sessionId, $rating, $field = 'VOTE'): string
	{
		$rating = (int)$rating;

		$result = '-';

		if ($field === 'VOTE' && in_array($rating, [5,1]))
		{
			$result = '<span class="ol-stat-rating ol-stat-rating-'.$rating.'" title=""></span>';
		}
		else if ($field === 'VOTE_HEAD' && $rating >= 1 && $rating <= 5)
		{
			$result = '<span class="ol-stat-rating-head" title="'.$rating.'/5"><span class="ol-stat-rating-head-wrap ol-stat-rating-head-'.$rating.'"></span></span>';
		}
		else if ($field === 'VOTE_HEAD_PERM')
		{
			if(Limit::canUseVoteHead())
			{
				$result = '<span style="display: inline-flex;" id="ol-vote-head-placeholder-'.$sessionId.'" title=""></span><script>BX.ready(function(){
				var voteChild = BX.MessengerCommon.linesVoteHeadNodes('.$sessionId.', '.$rating.', true);
				BX("ol-vote-head-placeholder-'.$sessionId.'").appendChild(voteChild);
			})</script>';
			}
			else
			{
				$result = '<span style="display: inline-flex;" id="ol-vote-head-placeholder-'.$sessionId.'" onclick="BX.UI.InfoHelper.show(\'' . Limit::INFO_HELPER_LIMIT_CONTACT_CENTER_BOSS_RATE . '\');" title=""><span style="margin-left: -5px;" class="tariff-lock"></span></span><script>BX.ready(function(){
				var voteChild = BX.MessengerCommon.linesVoteHeadNodes('.$sessionId.', '.$rating.', false);
				BX("ol-vote-head-placeholder-'.$sessionId.'").appendChild(voteChild);
			})</script>';
			}

		}

		return $result;
	}

	/**
	 * @param $sessionId
	 * @param $comment
	 * @param string $field
	 * @return string
	 */
	private static function formatComment($sessionId, $comment, $field = 'COMMENT_HEAD')
	{
		$result = '-';

		$comment = htmlspecialcharsbx($comment);

		if(!empty($comment))
		{
			$comment = str_replace(["\r\n", "\r", "\n"], '<br />', $comment);
		}

		if ($field == 'COMMENT_HEAD' && $comment !== '')
		{
			$result = $comment;
		}
		else if ($field == 'COMMENT_HEAD_PERM')
		{
			if(Limit::canUseVoteHead())
			{
				$result = '
				<div id="ol-comment-head-text-'.$sessionId.'" title="">' . $comment . '</div>
				<div id="ol-comment-head-placeholder-'.$sessionId.'" title=""></div><script>BX.ready(function(){
				var voteChild = BX.MessengerCommon.linesCommentHeadNodes('.$sessionId.', BX("ol-comment-head-text-'.$sessionId.'").innerHTML.replace(/<br>/g, "\n"), true, "statistics");
				BX("ol-comment-head-placeholder-'.$sessionId.'").appendChild(voteChild);
				BX.style(BX("ol-comment-head-text-'.$sessionId.'"), \'display\', \'none\');
			})</script>';
			}
			elseif($comment === '')
			{
				$result = '<span class = "bx-messenger-content-item-vote-comment-add"  onclick="BX.UI.InfoHelper.show(\'' . Limit::INFO_HELPER_LIMIT_CONTACT_CENTER_BOSS_RATE . '\');" title=""><span style="margin-top: 1px; margin-left: 5px;" class="tariff-lock"></span>' . Loc::getMessage('OL_STATS_COMMENT_HEAD_ADD') . '<span id="ol-comment-head-text-'.$sessionId.'"></span></span>';
			}
			else
			{
				$result = '<span id="ol-comment-head-text-'.$sessionId.'"  onclick="BX.UI.InfoHelper.show(\'' . Limit::INFO_HELPER_LIMIT_CONTACT_CENTER_BOSS_RATE . '\');" title=""><span style="margin-top: 1px; margin-left: 5px;" class="tariff-lock"></span>' . $comment . '</span>';
			}
		}

		return $result;
	}

	private function getFilterDefinition()
	{
		$result = array(
			"CONFIG_ID" => array(
				"id" => "CONFIG_ID",
				"name" => Loc::getMessage("OL_STATS_HEADER_CONFIG_NAME"),
				"type" => "list",
				"items" => $this->arResult['LINES'],
				"default" => !$this->configId,
				"default_value" => $this->configId? $this->configId: '',
				"params" => array(
					"multiple" => "Y"
				)
			),
		);

		$result = array_merge($result, array(
			"TYPE" => array(
				"id" => "TYPE",
				"name" => Loc::getMessage("OL_STATS_HEADER_MODE_NAME"),
				"type" => "list",
				"items" => array(
					"" => Loc::getMessage("OL_STATS_FILTER_UNSET"),
					"input" => Loc::getMessage("OL_COMPONENT_TABLE_INPUT"),
					"output" => Loc::getMessage("OL_COMPONENT_TABLE_OUTPUT"),
				),
				"default" => false,
			),
			"DATE_CREATE" => array(
				"id" => "DATE_CREATE",
				"name" => Loc::getMessage("OL_STATS_HEADER_DATE_CREATE"),
				"type" => "date",
				"default" => true
			),
			"DATE_CLOSE" => array(
				"id" => "DATE_CLOSE",
				"name" => Loc::getMessage("OL_STATS_HEADER_DATE_CLOSE"),
				"type" => "date",
				"default" => false
			),
			"OPERATOR_ID" => array(
				"id" => "OPERATOR_ID",
				"name" => Loc::getMessage("OL_STATS_HEADER_OPERATOR_NAME"),
				"type" => "dest_selector",
				'params' => array (
					'apiVersion' => '3',
					'context' => 'OL_STATS_FILTER_OPERATOR_ID',
					'multiple' => 'N',
					'contextCode' => 'U',
					'enableAll' => 'N',
					'enableEmpty' => 'Y',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'departmentSelectDisable' => 'Y',
					'isNumeric' => 'Y',
					'prefix' => 'U',
					'allowBots' => 'Y'
				),
				"default" => true,
			),
			"CLIENT_NAME" => array(
				"id" => "CLIENT_NAME",
				"name" => Loc::getMessage("OL_STATS_HEADER_USER_NAME"),
				"type" => "string",
				"default" => false,
			),
			'SOURCE' => array(
				"id" => "SOURCE",
				"name" => Loc::getMessage("OL_STATS_HEADER_SOURCE_TEXT_2"),
				"type" => "list",
				"items" => \Bitrix\ImConnector\Connector::getListConnector(),
				"default" => true,
				"params" => array(
					"multiple" => "Y"
				)
			),
			"ID" => array(
				"id" => "ID",
				"name" => Loc::getMessage("OL_STATS_HEADER_SESSION_ID"),
				"type" => "string",
				"default" => true
			),
			'EXTRA_URL' => array(
				"id" => "EXTRA_URL",
				"name" => Loc::getMessage("OL_STATS_HEADER_EXTRA_URL"),
				"type" => "string",
				"default" => false
			),
			"STATUS" => array(
				"id" => "STATUS",
				"name" => Loc::getMessage("OL_STATS_HEADER_STATUS"),
				"type" => "list",
				"items" => array(
					"" => Loc::getMessage("OL_STATS_FILTER_UNSET"),
					"client" => Loc::getMessage("OL_COMPONENT_TABLE_STATUS_CLIENT_NEW"),
					"operator" => Loc::getMessage("OL_COMPONENT_TABLE_STATUS_OPERATOR_NEW"),
					"closed" => Loc::getMessage("OL_COMPONENT_TABLE_STATUS_CLOSED"),
				),
				"default" => true,
			),
			"STATUS_DETAIL" => array(
				"id" => "STATUS_DETAIL",
				"name" => Loc::getMessage("OL_STATS_HEADER_STATUS_DETAIL"),
				"type" => "list",
				"items" => array(
					"" => Loc::getMessage("OL_STATS_FILTER_UNSET"),
					0 => Loc::getMessage("OL_COMPONENT_TABLE_STATUS_NEW"),
					5 => Loc::getMessage("OL_COMPONENT_TABLE_STATUS_OPERATOR_SKIP_NEW"),
					10 => Loc::getMessage("OL_COMPONENT_TABLE_STATUS_OPERATOR_ANSWER_NEW"),
					20 => Loc::getMessage("OL_COMPONENT_TABLE_STATUS_CLIENT_NEW"),
					25 => Loc::getMessage("OL_COMPONENT_TABLE_STATUS_CLIENT_AFTER_OPERATOR_NEW"),
					40 => Loc::getMessage("OL_COMPONENT_TABLE_STATUS_OPERATOR_NEW"),
					50 => Loc::getMessage("OL_COMPONENT_TABLE_STATUS_WAIT_ACTION_2"),
					60 => Loc::getMessage("OL_COMPONENT_TABLE_STATUS_CLOSED"),
					65 => Loc::getMessage("OL_STATS_HEADER_SPAM_2"),
				),
				"params" => array(
					"multiple" => "Y"
				),
				"default" => false
			),
		));
		if (defined('IMOL_FDC'))
		{
			$result["EXTRA_TARIFF"] = array(
				"id" => "EXTRA_TARIFF",
				"name" => Loc::getMessage("OL_STATS_HEADER_EXTRA_TARIFF"),
				"type" => "string",
				"default" => false
			);
			$result["EXTRA_USER_LEVEL"] = array(
				"id" => "EXTRA_USER_LEVEL",
				"name" => Loc::getMessage("OL_STATS_HEADER_EXTRA_USER_LEVEL"),
				"type" => "string",
				"default" => false
			);
			$result["EXTRA_PORTAL_TYPE"] = array(
				"id" => "EXTRA_PORTAL_TYPE",
				"name" => Loc::getMessage("OL_STATS_HEADER_EXTRA_PORTAL_TYPE"),
				"type" => "string",
				"default" => false
			);
			$result["EXTRA_REGISTER"] = array(
				"id" => "EXTRA_REGISTER",
				"name" => Loc::getMessage("OL_STATS_HEADER_EXTRA_REGISTER"),
				"default" => false,
				"type" => "number"
			);
		}
		if(Loader::includeModule('crm'))
		{
			$result["CRM"] = array(
				"id" => "CRM",
				"name" => Loc::getMessage("OL_STATS_HEADER_CRM"),
				"default" => false,
				"type" => "list",
				"items" => array(
					"" => Loc::getMessage("OL_STATS_FILTER_UNSET"),
					"Y" => Loc::getMessage("OL_STATS_FILTER_Y"),
					"N" => Loc::getMessage("OL_STATS_FILTER_N"),
				)
			);
			/*$result["CRM_ENTITY"] = array(
				"id" => "CRM_ENTITY",
				"name" => Loc::getMessage("OL_STATS_HEADER_CRM_TEXT"),
				"default" => false,
				"type" => "custom_entity",
				"selector" => array(
					"TYPE" => "crm_entity",
					"DATA" => array(
						"ID" => "CRM_ENTITY",
						"FIELD_ID" => "CRM_ENTITY",
						'ENTITY_TYPE_NAMES' => array(CCrmOwnerType::LeadName, CCrmOwnerType::CompanyName, CCrmOwnerType::ContactName, CCrmOwnerType::DealName),
						'IS_MULTIPLE' => false
					)
				)
			);*/
		}

		$result = array_merge($result, Array(
			"SEND_FORM" => array(
				"id" => "SEND_FORM",
				"name" => Loc::getMessage("OL_STATS_HEADER_SEND_FORM"),
				"default" => false,
				"type" => "list",
				"items" => array(
					"" => Loc::getMessage("OL_STATS_FILTER_UNSET"),
					"Y" => Loc::getMessage("OL_STATS_FILTER_Y"),
					"N" => Loc::getMessage("OL_STATS_FILTER_N"),
				)
			),
			"SEND_HISTORY" => array(
				"id" => "SEND_HISTORY",
				"name" => Loc::getMessage("OL_STATS_HEADER_SEND_HISTORY"),
				"default" => false,
				"type" => "list",
				"items" => array(
					"" => Loc::getMessage("OL_STATS_FILTER_UNSET"),
					"Y" => Loc::getMessage("OL_STATS_FILTER_Y"),
					"N" => Loc::getMessage("OL_STATS_FILTER_N"),
				)
			),
			"WORKTIME" => array(
				"id" => "WORKTIME",
				"name" => Loc::getMessage("OL_STATS_HEADER_WORKTIME_TEXT"),
				"default" => false,
				"type" => "list",
				"items" => array(
					"" => Loc::getMessage("OL_STATS_FILTER_UNSET"),
					"Y" => Loc::getMessage("OL_STATS_FILTER_Y"),
					"N" => Loc::getMessage("OL_STATS_FILTER_N"),
				)
			),
			"SPAM" => array(
				"id" => "SPAM",
				"name" => Loc::getMessage("OL_STATS_HEADER_SPAM_2"),
				"default" => false,
				"type" => "list",
				"items" => array(
					"" => Loc::getMessage("OL_STATS_FILTER_UNSET"),
					"Y" => Loc::getMessage("OL_STATS_FILTER_Y"),
					"N" => Loc::getMessage("OL_STATS_FILTER_N"),
				)
			),
			"MESSAGE_COUNT" => array(
				"id" => "MESSAGE_COUNT",
				"name" => Loc::getMessage("OL_STATS_FILTER_MESSAGE_COUNT"),
				"default" => false,
				"type" => "number"
			),
			"VOTE" => array(
				"id" => "VOTE",
				"name" => Loc::getMessage("OL_STATS_HEADER_VOTE_CLIENT"),
				"default" => false,
				"type" => "list",
				"items" => array(
					"" => Loc::getMessage("OL_STATS_FILTER_UNSET"),
					"5" => Loc::getMessage("OL_STATS_HEADER_VOTE_CLIENT_LIKE"),
					"1" => Loc::getMessage("OL_STATS_HEADER_VOTE_CLIENT_DISLIKE"),
				)
			),
			"VOTE_HEAD" => array(
				"id" => "VOTE_HEAD",
				"name" => Loc::getMessage("OL_STATS_HEADER_VOTE_HEAD_1"),
				"default" => false,
				"type" => "list",
				"items" => array(
					"wo" => Loc::getMessage("OL_STATS_HEADER_VOTE_HEAD_WO"),
					"5" => 5,
					"4" => 4,
					"3" => 3,
					"2" => 2,
					"1" => 1,
				),
				"params" => array(
					"multiple" => "Y"
				)
			),
		));

		return $result;
	}

	private function getFilter(array $filterDefinition)
	{
		$request = HttpApplication::getInstance()->getContext()->getRequest();

		$filterOptions = new \Bitrix\Main\UI\Filter\Options($this->filterId);
		$filter = $filterOptions->getFilter($this->getFilterDefinition());

		$result = array();

		$allowedUserIds = Security\Helper::getAllowedUserIds(
			Security\Helper::getCurrentUserId(),
			$this->userPermissions->getPermission(Security\Permissions::ENTITY_SESSION, Security\Permissions::ACTION_VIEW)
		);

		if ($request->get('GUEST_USER_ID'))
		{
			$result['=USER_ID'] = intval($request->get('GUEST_USER_ID'));
		}

		if (!isset($filter["OPERATOR_ID"]) && $request->get('OPERATOR_ID') !== null)
		{
			$value = $request->get('OPERATOR_ID');
			$filter['OPERATOR_ID'] = (
				is_string($value) && mb_strtolower($value) === 'empty'
					? 'empty'
					: intval($value)
			);
		}

		if(isset($filter["CLIENT_NAME"]))
		{
			$filterUserClient = \Bitrix\Main\UserUtils::getUserSearchFilter(Array(
				'FIND' => $filter["CLIENT_NAME"]
			));

			$filterUserClient['EXTERNAL_AUTH_ID'] = array('imconnector');

			$userClientRaw = \Bitrix\Main\UserTable::getList(Array(
				'select' => Array('ID'),
				'filter' => $filterUserClient
			));

			while ($userClientRow = $userClientRaw->fetch())
			{
				$result["=USER_ID"][] = $userClientRow['ID'];
			}

			if(empty($result["=USER_ID"]))
				$result["=USER_ID"] = -1;
		}

		if(isset($filter["OPERATOR_ID"]))
		{
			$filter["OPERATOR_ID"] = (
			mb_strtolower($filter["OPERATOR_ID"]) === 'empty'
					? false
					: (int)$filter["OPERATOR_ID"]
			);

			if(is_array($allowedUserIds))
			{
				$result["=OPERATOR_ID"] = array_intersect(array_merge($allowedUserIds, array(false)), array($filter["OPERATOR_ID"]));
			}
			else
			{
				$result["=OPERATOR_ID"] = $filter["OPERATOR_ID"];
			}
		}
		else
		{
			if(is_array($allowedUserIds))
			{
				$result["=OPERATOR_ID"] = $allowedUserIds;
			}
		}

		if ($filter["DATE_CREATE_from"] <> '')
		{
			try
			{
				$result[">=DATE_CREATE"] = new \Bitrix\Main\Type\DateTime($filter["DATE_CREATE_from"]);
			}
			catch (Exception $e)
			{
			}
		}
		if ($filter["DATE_CREATE_to"] <> '')
		{
			try
			{
				$result["<=DATE_CREATE"] = new \Bitrix\Main\Type\DateTime($filter["DATE_CREATE_to"]);
			}
			catch (Exception $e)
			{
			}
		}

		if ($filter["DATE_CLOSE_from"] <> '')
		{
			try
			{
				$result[">=DATE_CLOSE"] = new \Bitrix\Main\Type\DateTime($filter["DATE_CLOSE_from"]);
			} catch (Exception $e){}
		}
		if ($filter["DATE_CLOSE_to"] <> '')
		{
			try
			{
				$result["<=DATE_CLOSE"] = new \Bitrix\Main\Type\DateTime($filter["DATE_CLOSE_to"]);
			} catch (Exception $e){}
		}

		if(is_array($filter["SOURCE"]))
			$result["=SOURCE"] = $filter["SOURCE"];

		if(is_array($filter["CONFIG_ID"]))
		{
			$result["=CONFIG_ID"] = $filter["CONFIG_ID"];
		}
		else if ($this->configId)
		{
			$result['=CONFIG_ID'] = $this->configId;
		}

		if(!empty($filter["EXTRA_URL"]))
			$result["%EXTRA_URL"] = $filter["EXTRA_URL"];

		if(!empty($filter["EXTRA_TARIFF"]))
			$result["=EXTRA_TARIFF"] = $filter["EXTRA_TARIFF"];

		if(!empty($filter["EXTRA_USER_LEVEL"]))
			$result["=EXTRA_USER_LEVEL"] = $filter["EXTRA_USER_LEVEL"];

		if(!empty($filter["EXTRA_PORTAL_TYPE"]))
			$result["=EXTRA_PORTAL_TYPE"] = $filter["EXTRA_PORTAL_TYPE"];

		if(isset($filter["STATUS"]))
		{
			switch ($filter["STATUS"])
			{
				case "client":
					$result["<STATUS"] = 40;
				break;

				case "operator":
					$result[">=STATUS"] = 40;
					$result["<STATUS"] = 60;
				break;

				case "closed":
					$result[">=STATUS"] = 60;
				break;
			}
		}

		if(is_array($filter["STATUS_DETAIL"]))
			$result["=STATUS"] = $filter["STATUS_DETAIL"];

		if(isset($filter["CRM"]))
			$result["=CRM"] = $filter["CRM"];

		if(isset($filter['CRM_ENTITY']) && $filter['CRM_ENTITY'] != '')
		{
			$crmFilter = array();
			try
			{
				$crmFilter = \Bitrix\Main\Web\Json::decode($filter['CRM_ENTITY']);
			} catch (\Bitrix\Main\ArgumentException $e) {};

			if(count($crmFilter) == 1)
			{
				//TODO: improve search
				$entityTypes = array_keys($crmFilter);
				$entityType = $entityTypes[0];
				$entityId = $crmFilter[$entityType][0];
				//$result['=CRM_ENTITY_TYPE'] = $entityType;
				//$result['=CRM_ENTITY_ID'] = $entityId;
			}
		}

		if(isset($filter["SEND_FORM"]))
		{
			if ($filter["SEND_FORM"] == 'Y')
			{
				$result["!=SEND_FORM"] = 'none';
			}
			else
			{
				$result["=SEND_FORM"] = 'none';
			}
		}

		if(isset($filter["SEND_HISTORY"]))
		{
			if ($filter["SEND_HISTORY"] == 'Y')
			{
				$result["=SEND_HISTORY"] = 'Y';
			}
			else if ($filter["SEND_HISTORY"] == 'N')
			{
				$result["!=SEND_HISTORY"] = 'Y';
			}
		}

		if(isset($filter["SPAM"]))
		{
			if ($filter["SPAM"] == 'Y')
			{
				$result["=SPAM"] = 'Y';
			}
			else if ($filter["SPAM"] == 'N')
			{
				$result["!=SPAM"] = 'Y';
			}
		}

		if (isset($filter["MESSAGE_COUNT_numsel"]))
		{
			if ($filter["MESSAGE_COUNT_numsel"] == 'range')
			{
				if (intval($filter["MESSAGE_COUNT_from"]) > 0 && intval($filter["MESSAGE_COUNT_to"]) == 0)
				{
					$filter["MESSAGE_COUNT_numsel"] = 'more';
					$filter["MESSAGE_COUNT_from"] = $filter["MESSAGE_COUNT_from"]-1;
				}
				else if (intval($filter["MESSAGE_COUNT_from"]) == 0 && intval($filter["MESSAGE_COUNT_to"]) > 0)
				{
					$filter["MESSAGE_COUNT_numsel"] = 'less';
					$filter["MESSAGE_COUNT_to"] = $filter["MESSAGE_COUNT_to"]+1;
				}
				else
				{
					$result[">=MESSAGE_COUNT"] = intval($filter["MESSAGE_COUNT_from"]);
					$result["<=MESSAGE_COUNT"] = intval($filter["MESSAGE_COUNT_to"]);
				}
			}
			if ($filter["MESSAGE_COUNT_numsel"] == 'more')
			{
				$result[">MESSAGE_COUNT"] = intval($filter["MESSAGE_COUNT_from"]);
			}
			else if ($filter["MESSAGE_COUNT_numsel"] == 'less')
			{
				$result["<MESSAGE_COUNT"] = intval($filter["MESSAGE_COUNT_to"]);
			}
			else if ($filter["MESSAGE_COUNT_numsel"] != 'range')
			{
				$result["=MESSAGE_COUNT"] = intval($filter["MESSAGE_COUNT_from"]);
			}
		}
		else if (isset($filter["MESSAGE_COUNT"]))
		{
			$result["=MESSAGE_COUNT"] = intval($filter["MESSAGE_COUNT"]);
		}

		if (isset($filter["EXTRA_REGISTER_numsel"]))
		{
			if ($filter["EXTRA_REGISTER_numsel"] == 'range')
			{
				if (intval($filter["EXTRA_REGISTER_from"]) > 0 && intval($filter["EXTRA_REGISTER_to"]) == 0)
				{
					$filter["EXTRA_REGISTER_numsel"] = 'more';
					$filter["EXTRA_REGISTER_from"] = $filter["EXTRA_REGISTER_from"]-1;
				}
				else if (intval($filter["EXTRA_REGISTER_from"]) == 0 && intval($filter["EXTRA_REGISTER_to"]) > 0)
				{
					$filter["EXTRA_REGISTER_numsel"] = 'less';
					$filter["EXTRA_REGISTER_to"] = $filter["EXTRA_REGISTER_to"]+1;
				}
				else
				{
					$result[">=EXTRA_REGISTER"] = intval($filter["EXTRA_REGISTER_from"]);
					$result["<=EXTRA_REGISTER"] = intval($filter["EXTRA_REGISTER_to"]);
				}
			}
			if ($filter["EXTRA_REGISTER_numsel"] == 'more')
			{
				$result[">EXTRA_REGISTER"] = intval($filter["EXTRA_REGISTER_from"]);
			}
			else if ($filter["EXTRA_REGISTER_numsel"] == 'less')
			{
				$result["<EXTRA_REGISTER"] = intval($filter["EXTRA_REGISTER_to"]);
			}
			else if ($filter["EXTRA_REGISTER_numsel"] != 'range')
			{
				$result["=EXTRA_REGISTER"] = intval($filter["EXTRA_REGISTER_from"]);
			}
		}
		else if (isset($filter["EXTRA_REGISTER"]))
		{
			$result["=EXTRA_REGISTER"] = intval($filter["EXTRA_REGISTER"]);
		}

		if(isset($filter["TYPE"]))
			$result["=MODE"] = $filter["TYPE"];

		if(isset($filter["ID"]))
			$result["=ID"] = $filter["ID"];

		if(isset($filter["WORKTIME"]))
			$result["=WORKTIME"] = $filter["WORKTIME"];

		if(isset($filter["VOTE"]))
			$result["=VOTE"] = intval($filter["VOTE"]);

		if(is_array($filter["VOTE_HEAD"]))
		{
			foreach ($filter["VOTE_HEAD"] as $key => $value)
			{
				if ($value == 'wo')
				{
					$filter["VOTE_HEAD"][$key] = 0;
				}
			}
			$result["=VOTE_HEAD"] = $filter["VOTE_HEAD"];
		}

		$minSearchToken = \Bitrix\Main\Config\Option::get('imopenlines', 'min_search_token');
		if (
			isset($filter['FIND'])
			&& (
				$minSearchToken <= 0
				|| \Bitrix\Main\Search\Content::isIntegerToken($filter['FIND'])
				|| mb_strlen($filter['FIND']) >= $minSearchToken
			)
			&& \Bitrix\Main\Search\Content::canUseFulltextSearch($filter['FIND'], \Bitrix\Main\Search\Content::TYPE_MIXED))
		{
			global $DB;
			if (!\Bitrix\Imopenlines\Model\SessionIndexTable::getEntity()->fullTextIndexEnabled('SEARCH_CONTENT') && $DB->IndexExists("b_imopenlines_session_index", array("SEARCH_CONTENT"), true))
			{
				\Bitrix\Imopenlines\Model\SessionIndexTable::getEntity()->enableFullTextIndex("SEARCH_CONTENT");
			}
			if (\Bitrix\Imopenlines\Model\SessionIndexTable::getEntity()->fullTextIndexEnabled('SEARCH_CONTENT'))
			{
				if (\Bitrix\Main\Search\Content::isIntegerToken($filter['FIND']))
				{
					$result['*INDEX.SEARCH_CONTENT'] = \Bitrix\Main\Search\Content::prepareIntegerToken($filter['FIND']);
				}
				else
				{
					$result['*INDEX.SEARCH_CONTENT'] = \Bitrix\Main\Search\Content::prepareStringToken($filter['FIND']);
				}
			}
		}

		return $result;
	}

	private function getUserHtml($userId, $userData)
	{
		if ($this->excelMode)
		{
			if ($userId > 0)
			{
				$result = $userData[$userId]["FULL_NAME"];
			}
			else
			{
				$result = '-';
			}
		}
		else
		{
			if ($userId > 0)
			{
				$photoStyle = '';
				$photoClass = '';

				if ($userData[$userId]["PHOTO"])
				{
					$photoStyle = "background: url('".$userData[$userId]["PHOTO"]."') no-repeat center;";
				}
				else
				{
					$photoStyle = 'background-position: center;';
					$photoClass = 'user-default-avatar';
				}
				$userHtml = '<span class="ol-stat-user-img user-avatar '.$photoClass.'" style="'.$photoStyle.'"></span>';
				$userHtml .= $userData[$userId]["FULL_NAME"];
			}
			else
			{
				$userHtml = '<span class="ol-stat-user-img user-avatar"></span> &mdash;';
			}
			$result = '<nobr>'.$userHtml.'</nobr>';
		}
		return $result;
	}

	private function getUserData($id = array())
	{
		$users = array();
		if (empty($id))
			return $users;

		$orm = \Bitrix\Main\UserTable::getList(Array(
			'filter' => Array('=ID' => $id)
		));
		while($user = $orm->fetch())
		{
			$users[$user["ID"]]["FULL_NAME"] =  CUser::FormatName("#NAME# #LAST_NAME#", array(
				"NAME" => $user["NAME"],
				"LAST_NAME" => $user["LAST_NAME"],
				"SECOND_NAME" => $user["SECOND_NAME"],
				"LOGIN" => $user["LOGIN"]
			));
			if (intval($user["PERSONAL_PHOTO"]) > 0)
			{
				$imageFile = \CFile::GetFileArray($user["PERSONAL_PHOTO"]);
				if ($imageFile !== false)
				{
					$file = CFile::ResizeImageGet(
						$imageFile,
						array("width" => "30", "height" => "30"),
						BX_RESIZE_IMAGE_EXACT,
						false
					);
					$users[$user["ID"]]["PHOTO"] = $file["src"];
				}
			}
		}

		return $users;
	}

	/**
	 * @return array|array[][][]
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function prepareGroupActions(): array
	{
		$result = [];

		$userPermissions = Permissions::createWithCurrentUser();

		if($userPermissions->canPerform(Permissions::ENTITY_SESSION, Permissions::ACTION_VIEW))
		{
			$prefix = $this->gridId;
			$snippet = new Panel\Snippet();

			$actionList = [
				[
					'NAME' => Loc::getMessage('OL_COMPONENT_SESSION_LIST_CHOOSE_ACTION'),
					'VALUE' => 'none'
				]
			];

			$applyButton = $snippet->getApplyButton(
				[
					'ONCHANGE' => [
						[
							'ACTION' => Panel\Actions::CALLBACK,
							'DATA' => [
								[
									'JS' => 'BX.OpenLines.Actions.confirmGroupAction(\'' . $this->gridId . '\')'
								]
							]
						]
					]
				]
			);

			$actionList[] = [
				'NAME' => Loc::getMessage('OL_COMPONENT_SESSION_LIST_GROUP_ACTION_CLOSE'),
				'VALUE' => 'close',
				'ONCHANGE' => [
					[
						'ACTION' => Panel\Actions::CREATE,
						'DATA' => [
							$applyButton
						]
					],
					[
						'ACTION' => Panel\Actions::CALLBACK,
						'DATA' => [
							['JS' => "BX.OpenLines.Actions.destroyTransferDialogSelector();"],
						],
					],
				]
			];

			$actionList[] = [
				'NAME' => Loc::getMessage('OL_COMPONENT_SESSION_LIST_GROUP_ACTION_SPAM'),
				'VALUE' => 'spam',
				'ONCHANGE' => [
					[
						'ACTION' => Panel\Actions::CREATE,
						'DATA' => [
							$applyButton
						]
					],
					[
						'ACTION' => Panel\Actions::CALLBACK,
						'DATA' => [
							['JS' => "BX.OpenLines.Actions.destroyTransferDialogSelector();"],
						],
					],
				]
			];

			if(Limit::canTransferToLine())
			{
				$actionList[] = [
					'NAME' => Loc::getMessage('OL_COMPONENT_SESSION_LIST_GROUP_ACTION_TRANSFER'),
					'VALUE' => 'transfer',
					'ONCHANGE' => [
						[
							'ACTION' => Panel\Actions::CREATE,
							'DATA' => [
								[
									'TYPE' => Panel\Types::TEXT,
									'ID' => $this->arResult['groupActionsData']['transfer']['inputId'],
									'NAME' => $this->arResult['groupActionsData']['transfer']['inputName'],
									'VALUE' => '',
									'SIZE' => 1,
								],
								$applyButton
							],
						],
						[
							'ACTION' => Panel\Actions::CALLBACK,
							'DATA' => [
								['JS' => "BX.OpenLines.Actions.initTransferDialogSelector();"],
							],
						],
					]
				];
			}
			else
			{
				$actionList[] = [
					'NAME' => Loc::getMessage('OL_COMPONENT_SESSION_LIST_GROUP_ACTION_TRANSFER'),
					'VALUE' => 'transfer',
					'ONCHANGE' => [
						[
							'ACTION' => Panel\Actions::CALLBACK,
							'DATA' => [
								['JS' => "BX.UI.InfoHelper.show('" . Limit::INFO_HELPER_LIMIT_CONTACT_CENTER_OL_CHAT_TRANSFER . "');"],
							],
						],
					]
				];
			}

			/*$actionList[] = [
				'NAME' => Loc::getMessage('OL_COMPONENT_SESSION_LIST_GROUP_ACTION_CHANGE_RESPONSIBLE'),
				'VALUE' => 'setresponsible',
				'ONCHANGE' => [
					[
						'ACTION' => Panel\Actions::CREATE,
						'DATA' => [
							[
								'TYPE' => Panel\Types::TEXT,
								'ID' => 'action_set_responsible_text',
								'NAME' => 'responsibleText',
								'VALUE' => '',
								'SIZE' => 1
							],
							[
								'TYPE' => Panel\Types::HIDDEN,
								'ID' => 'action_set_responsible',
								'NAME' => 'responsibleId',
								'VALUE' => '',
								'SIZE' => 1
							]
						]
					],
					[
						'ACTION' => Panel\Actions::CALLBACK,
						'DATA' => [
							[
								'JS' => 'BX.OpenLines.GridActions.initPopupBaloon(\'user\', \'action_set_responsible_text\',\'action_set_responsible\');'
							]
						]
					]
				]
			];*/

			$result = [
				'GROUPS' => [
					[
						'ITEMS' => [
							[
								'TYPE' => Panel\Types::DROPDOWN,
								'ID' => 'action_button_' . $prefix,
								'NAME' => 'action_button_' . $prefix,
								'ITEMS' => $actionList
							],
							$snippet->getForAllCheckbox()
						]
					]
				]
			];
		}

		return $result;
	}

	/**
	 * @return array|mixed
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function executeComponent()
	{
		global $APPLICATION, $USER_FIELD_MANAGER;

		$this->includeComponentLang('class.php');

		if (!$this->checkModules())
			return false;

		$this->init();

		if (!$this->checkAccess())
			return false;

		$this->arResult["GRID_ID"] = $this->gridId;
		$this->arResult["FILTER_ID"] = $this->filterId;
		$this->arResult["FILTER"] = $this->getFilterDefinition();
		$this->arResult["UF_FIELDS"] = [];

		$this->arResult['GROUP_ACTIONS'] = $this->prepareGroupActions();

		//UF ->
		$ufData = \CUserTypeEntity::GetList(array(), array('ENTITY_ID' => SessionTable::getUfId(), 'LANG' => LANGUAGE_ID));
		//<- UF

		while($ufResult = $ufData->Fetch())
		{
			$this->arResult["UF_FIELDS"][$ufResult["FIELD_NAME"]] = $ufResult;
		}

		$sorting = $this->gridOptions->GetSorting(array("sort" => array("ID" => "DESC")));
		$navParams = $this->gridOptions->GetNavParams();

		if($this->excelMode)
		{
			$pageSize = $this->arParams['STEXPORT_PAGE_SIZE'];
			$pageNum = $this->arParams['PAGE_NUMBER'];
			$total = $this->arParams['STEXPORT_TOTAL_ITEMS'];
			$this->arResult['STEXPORT_IS_FIRST_PAGE'] = $pageNum === 1;

			$processed = ($pageNum - 1) * $pageSize;
			$offset = $pageSize * ($pageNum - 1);
			if (($total > 0) && ($total - $processed <= $pageSize))
			{
				$pageSize = $total - $processed;
			}
			unset($total, $processed);
		}
		else
		{
			$pageSize = $navParams['nPageSize'];
		}

		$gridHeaders = $this->gridOptions->GetVisibleColumns();
		if (empty($gridHeaders))
		{
			$gridHeaders = \Bitrix\ImOpenLines\Model\SessionTable::getSelectFieldsPerformance();
			$isNeedKpi = true;
		}
		$selectHeaders = array_intersect(SessionTable::getSelectFieldsPerformance(), $gridHeaders);

		$requiredHeaders = ['ID', 'USER_CODE', 'CLOSED', 'CHAT_ID', 'CHAT_OPERATOR_ID' => 'CHAT.AUTHOR_ID'];
		$selectHeaders = array_merge($requiredHeaders, $selectHeaders);

		foreach ($gridHeaders as $gridHeader)
		{
			switch ($gridHeader)
			{
				case 'MODE_NAME':
					$selectHeaders[] = 'MODE';
					break;
				case 'STATUS_DETAIL':
					$selectHeaders[] = 'STATUS';
					break;
				case 'SOURCE_TEXT':
					$selectHeaders[] = 'SOURCE';
					break;
				case 'USER_NAME':
					$selectHeaders[] = 'USER_ID';
					break;
				case 'CRM_TEXT':
					$selectHeaders[] = 'CRM';
					$selectHeaders[] = 'CRM_CREATE';
					$selectHeaders[] = 'CRM_CREATE_LEAD';
					$selectHeaders[] = 'CRM_CREATE_COMPANY';
					$selectHeaders[] = 'CRM_CREATE_CONTACT';
					$selectHeaders[] = 'CRM_CREATE_DEAL';
					$selectHeaders[] = 'CRM_ACTIVITY_ID';
					$selectHeaders[] = 'CRM_TRACE_DATA';
					break;
				case 'ACTION':
					$selectHeaders[] = 'CONFIG_ID';
					$selectHeaders[] = 'USER_CODE';
					break;
				case 'PAUSE_TEXT':
					$selectHeaders[] = 'PAUSE';
					break;
				case 'WORKTIME_TEXT':
					$selectHeaders[] = 'WORKTIME';
					break;
				case 'OPERATOR_NAME':
					$selectHeaders[] = 'OPERATOR_ID';
					break;
				case 'TIME_ANSWER_WO_BOT':
					$selectHeaders[] = 'TIME_ANSWER';
					$selectHeaders[] = 'TIME_BOT';
					break;
				case 'TIME_CLOSE_WO_BOT':
					$selectHeaders[] = 'TIME_CLOSE';
					$selectHeaders[] = 'TIME_BOT';
					break;
				case 'TIME_DIALOG_WO_BOT':
					$selectHeaders[] = 'TIME_DIALOG';
					$selectHeaders[] = 'TIME_BOT';
					break;
				case 'TIME_MESSAGE_ANSWER_FIRST':
				case 'TIME_MESSAGE_ANSWER_FULL':
				case 'TIME_MESSAGE_ANSWER_AVERAGE':
				case 'TIME_MESSAGE_ANSWER_MAX':
					$isNeedKpi = true;
					break;
			}
		}

		$nav = new \Bitrix\Main\UI\PageNavigation("page");
		$nav->allowAllRecords(false)
			->setPageSize($pageSize)
			->initFromUri();

		$cursor = SessionTable::getList(array(
			'order' => $sorting["sort"],
			'filter' => $this->getFilter($this->arResult["FILTER"]),
			'select' => $selectHeaders,
			"count_total" => true,
			'limit' => ($this->excelMode ? $pageSize : $nav->getLimit()),
			'offset' => ($this->excelMode ? $offset : $nav->getOffset())
		));

		$this->arResult["ROWS_COUNT"] = $cursor->getCount();
		$nav->setRecordCount($cursor->getCount());

		$this->arResult["SORT"] = $sorting["sort"];
		$this->arResult["SORT_VARS"] = $sorting["vars"];
		$this->arResult["NAV_OBJECT"] = $nav;

		$userId = array();
		$this->arResult["ELEMENTS_ROWS"] = [];
		while($data = $cursor->fetch())
		{
			if($pageSize >= count($this->arResult["ELEMENTS_ROWS"]))
			{
				$this->enableNextPage = true;
			}
			if ($data["USER_ID"] > 0)
			{
				$userId[$data["USER_ID"]] = $data["USER_ID"];
			}
			if ($data["OPERATOR_ID"] > 0)
			{
				$userId[$data["OPERATOR_ID"]] = $data["OPERATOR_ID"];
			}
			$this->arResult["ELEMENTS_ROWS"][] = ["data" => $data, "columns" => []];
		}
		$this->arResult['STEXPORT_IS_LAST_PAGE'] = $this->enableNextPage ? false : true;
		$this->showHistory = Security\Helper::getAllowedUserIds(
			Security\Helper::getCurrentUserId(),
			$this->userPermissions->getPermission(Security\Permissions::ENTITY_HISTORY, Security\Permissions::ACTION_VIEW)
		);
		$configManager = new Config();

		$arUsers = $this->getUserData($userId);
		$arSources = \Bitrix\ImConnector\Connector::getListConnector();
		foreach($this->arResult["ELEMENTS_ROWS"] as $key => $row)
		{
			$newRow = $this->arResult["ELEMENTS_ROWS"][$key]["columns"];

			$userFields = $USER_FIELD_MANAGER->getUserFields(SessionTable::getUfId(), $row["data"]['ID'], LANGUAGE_ID);

			foreach ($userFields as $ufResult)
			{
				if(isset($row["data"][$ufResult["FIELD_NAME"]]))
				{
					ob_start();
					$APPLICATION->IncludeComponent(
						"bitrix:system.field.view",
						$ufResult["USER_TYPE_ID"],
						//array("arUserField" => array_merge($ufResult, array('VALUE' => $row["data"][$ufResult["FIELD_NAME"]]))),
						array("arUserField" => $ufResult),
						null,
						array("HIDE_ICONS" => "Y")
					);

					$newRow[$ufResult["FIELD_NAME"]] = ob_get_contents();

					ob_end_clean();
				}
			}

			$newRow["CONFIG_ID"] = $this->arResult['LINES'][$row["data"]["CONFIG_ID"]];

			$newRow["USER_NAME"] = $this->getUserHtml($row["data"]["USER_ID"], $arUsers);
			$newRow["OPERATOR_NAME"] = $this->getUserHtml($row["data"]["OPERATOR_ID"], $arUsers);
			$newRow["MODE_NAME"] = $row["data"]["MODE"] == 'input'? Loc::getMessage('OL_COMPONENT_TABLE_INPUT'): Loc::getMessage('OL_COMPONENT_TABLE_OUTPUT');

			$newRow["SOURCE_TEXT"] = $arSources[$row["data"]["SOURCE"]];

			if ($row["data"]["STATUS"] < Session::STATUS_OPERATOR)
			{
				$newRow["STATUS"] = Loc::getMessage("OL_COMPONENT_TABLE_STATUS_CLIENT_NEW");
			}
			else if ($row["data"]["STATUS"] >= Session::STATUS_OPERATOR && $row["data"]["STATUS"] < Session::STATUS_CLOSE)
			{
				$newRow["STATUS"] = Loc::getMessage("OL_COMPONENT_TABLE_STATUS_OPERATOR_NEW");
			}
			else if ($row["data"]["STATUS"] >= Session::STATUS_CLOSE)
			{
				$newRow["STATUS"] = Loc::getMessage("OL_COMPONENT_TABLE_STATUS_CLOSED");
			}

			switch ($row["data"]["STATUS"])
			{
				case Session::STATUS_NEW:
					$newRow["STATUS_DETAIL"] = Loc::getMessage("OL_COMPONENT_TABLE_STATUS_NEW");
				break;
				case Session::STATUS_SKIP:
					$newRow["STATUS_DETAIL"] = Loc::getMessage("OL_COMPONENT_TABLE_STATUS_OPERATOR_SKIP_NEW");
				break;
				case Session::STATUS_ANSWER:
					$newRow["STATUS_DETAIL"] = Loc::getMessage("OL_COMPONENT_TABLE_STATUS_OPERATOR_ANSWER_NEW");
				break;
				case Session::STATUS_CLIENT:
					$newRow["STATUS_DETAIL"] = Loc::getMessage("OL_COMPONENT_TABLE_STATUS_CLIENT_NEW");
				break;
				case Session::STATUS_CLIENT_AFTER_OPERATOR:
					$newRow["STATUS_DETAIL"] = Loc::getMessage("OL_COMPONENT_TABLE_STATUS_CLIENT_AFTER_OPERATOR_NEW");
				break;
				case Session::STATUS_OPERATOR:
					$newRow["STATUS_DETAIL"] = Loc::getMessage("OL_COMPONENT_TABLE_STATUS_OPERATOR_NEW");
				break;
				case Session::STATUS_WAIT_CLIENT:
					$newRow["STATUS_DETAIL"] = Loc::getMessage("OL_COMPONENT_TABLE_STATUS_WAIT_ACTION_2");
				break;
				case Session::STATUS_CLOSE:
				case Session::STATUS_DUPLICATE:
				case Session::STATUS_SILENTLY_CLOSE:
					$newRow["STATUS_DETAIL"] = Loc::getMessage("OL_COMPONENT_TABLE_STATUS_CLOSED");
				break;
				case Session::STATUS_SPAM:
					$newRow["STATUS_DETAIL"] = Loc::getMessage("OL_STATS_HEADER_SPAM_2");
				break;
			}

			if (!self::isNotCloseSession($row['data']['STATUS'], $row['data']['CLOSED']))
			{
				$this->arResult['ELEMENTS_ROWS'][$key]['editable'] = false;
				$this->arResult['ELEMENTS_ROWS'][$key]['draggable'] = false;
				$this->arResult['ELEMENTS_ROWS'][$key]['expand'] = false;
				$this->arResult['ELEMENTS_ROWS'][$key]['not_count'] = true;
			}

			$newRow["PAUSE_TEXT"] = $row["data"]["PAUSE"] == 'Y'? Loc::getMessage('OL_COMPONENT_TABLE_YES'): Loc::getMessage('OL_COMPONENT_TABLE_NO');

			$newRow["SEND_FORM"] = $row["data"]["SEND_FORM"] != 'none'? Loc::getMessage('OL_COMPONENT_TABLE_YES'): Loc::getMessage('OL_COMPONENT_TABLE_NO');
			$newRow["SEND_HISTORY"] = $row["data"]["SEND_HISTORY"] == 'Y'? Loc::getMessage('OL_COMPONENT_TABLE_YES'): Loc::getMessage('OL_COMPONENT_TABLE_NO');

			$newRow["CRM_TEXT"] = self::getFormattedCrmColumn($row);

			if ($this->excelMode)
			{
				$newRow["CRM_TEXT"] = $row["data"]["CRM"] == 'Y'? Loc::getMessage('OL_COMPONENT_TABLE_YES'): Loc::getMessage('OL_COMPONENT_TABLE_NO');
				$newRow["CRM_LINK"] = implode(" ", self::getCrmLink($row["data"])) . ' ' . self::getCrmActivityLink($row["data"]);
			}

			$newRow["WORKTIME_TEXT"] = $row["data"]["WORKTIME"] == 'Y'? Loc::getMessage('OL_COMPONENT_TABLE_YES'): Loc::getMessage('OL_COMPONENT_TABLE_NO');

			if (!$this->excelMode)
			{
				if (!is_array($this->showHistory) || in_array($row["data"]["OPERATOR_ID"], $this->showHistory))
				{
					$newRow["ACTION"] = '<nobr><a href="#history" title="" onclick="BXIM.openHistory(\'imol|'.$row["data"]["ID"].'\'); return false;">'.Loc::getMessage('OL_COMPONENT_TABLE_ACTION_HISTORY').'</a></nobr> ';
				}
				if ($configManager->canJoin($row["data"]["CONFIG_ID"]))
				{
					$newRow["ACTION"] .= '<nobr><a href="#startSession" title="" onclick="BXIM.openMessenger(\'imol|'.$row["data"]["USER_CODE"].'\'); return false;">'.Loc::getMessage('OL_COMPONENT_TABLE_ACTION_START').'</a></nobr>';
				}
			}

			$newRow["TIME_ANSWER_WO_BOT"] = $row["data"]["TIME_ANSWER"]? $row["data"]["TIME_ANSWER"]-$row["data"]["TIME_BOT"]: 0;
			$newRow["TIME_CLOSE_WO_BOT"] = $row["data"]["TIME_CLOSE"]? $row["data"]["TIME_CLOSE"]-$row["data"]["TIME_BOT"]: 0;
			$newRow["TIME_CLOSE"] = $row["data"]["TIME_CLOSE"] != $row["data"]["TIME_BOT"]? $row["data"]["TIME_CLOSE"]: 0;
			$newRow["TIME_DIALOG_WO_BOT"] = $row["data"]["TIME_DIALOG"]? $row["data"]["TIME_DIALOG"]-$row["data"]["TIME_BOT"]: 0;
			$newRow["TIME_FIRST_ANSWER"] = $row["data"]["TIME_FIRST_ANSWER"]? $row["data"]["TIME_FIRST_ANSWER"]-$row["data"]["TIME_BOT"]: 0;
			$newRow["EXTRA_REGISTER"] = $row["data"]["EXTRA_REGISTER"]? $row["data"]["EXTRA_REGISTER"]: ($this->excelMode? '': '-');
			$newRow["EXTRA_TARIFF"] = $row["data"]["EXTRA_TARIFF"]? $row["data"]["EXTRA_TARIFF"]: ($this->excelMode? '': '-');
			$newRow["EXTRA_USER_LEVEL"] = $row["data"]["EXTRA_USER_LEVEL"]? $row["data"]["EXTRA_USER_LEVEL"]: ($this->excelMode? '': '-');
			$newRow["EXTRA_PORTAL_TYPE"] = $row["data"]["EXTRA_PORTAL_TYPE"]? $row["data"]["EXTRA_PORTAL_TYPE"]: ($this->excelMode? '': '-');

			if(isset($isNeedKpi))
			{
				$kpi = new \Bitrix\ImOpenLines\KpiManager($row["data"]["ID"]);
				$newRow["TIME_MESSAGE_ANSWER_FIRST"] = $kpi->getFirstMessageAnswerTime();
				$newRow["TIME_MESSAGE_ANSWER_FULL"] = $kpi->getFullAnswerTime();
				$newRow["TIME_MESSAGE_ANSWER_AVERAGE"] = $kpi->getAverageAnswerTime();
				$newRow["TIME_MESSAGE_ANSWER_MAX"] = $kpi->getMaxAnswerTime();
			}

			if ($row["data"]["EXTRA_URL"])
			{
				$parsedUrl = parse_url($row["data"]["EXTRA_URL"]);
				if ($this->excelMode)
				{
					$newRow["EXTRA_DOMAIN"] = $parsedUrl['host'];
					$newRow["EXTRA_URL"] = $row["data"]["EXTRA_URL"];
				}
				else
				{
					$newRow["EXTRA_URL"] = '<a href="'.htmlspecialcharsbx($row["data"]["EXTRA_URL"]).'" title="" target="_blank">'.htmlspecialcharsbx($parsedUrl['host']).'</a>';
				}
			}
			else
			{
				$newRow["EXTRA_URL"] = $this->excelMode? '': '-';
				if ($this->excelMode)
				{
					$newRow["EXTRA_DOMAIN"] = '';
				}
			}

			$newRow["SPAM"] = $row["data"]["SPAM"] == 'Y'? Loc::getMessage('OL_COMPONENT_TABLE_YES'): Loc::getMessage('OL_COMPONENT_TABLE_NO');

			if (!$this->excelMode)
			{
				$newRow["DATE_CREATE"] = self::formatDate($row["data"]["DATE_CREATE"]);
				$newRow["DATE_OPERATOR"] = self::formatDate($row["data"]["DATE_OPERATOR"]);
				$newRow["DATE_OPERATOR_ANSWER"] = self::formatDate($row["data"]["DATE_OPERATOR_ANSWER"]);
				$newRow["DATE_OPERATOR_CLOSE"] = self::formatDate($row["data"]["DATE_OPERATOR_CLOSE"]);
				$newRow["DATE_CLOSE"] = self::formatDate($row["data"]["DATE_CLOSE"]);
				$newRow["DATE_LAST_MESSAGE"] = self::formatDate($row["data"]["DATE_LAST_MESSAGE"]);
				$newRow["DATE_FIRST_ANSWER"] = self::formatDate($row["data"]["DATE_FIRST_ANSWER"]);
				$newRow["TIME_ANSWER_WO_BOT"] = self::formatDuration($newRow["TIME_ANSWER_WO_BOT"]);
				$newRow["TIME_CLOSE_WO_BOT"] = self::formatDuration($newRow["TIME_CLOSE_WO_BOT"]);
				$newRow["TIME_ANSWER"] = self::formatDuration($row["data"]["TIME_ANSWER"]);
				$newRow["TIME_CLOSE"] = self::formatDuration($newRow["TIME_CLOSE"]);
				$newRow["TIME_BOT"] = self::formatDuration($row["data"]["TIME_BOT"]);
				$newRow["TIME_DIALOG_WO_BOT"] = self::formatDuration($newRow["TIME_DIALOG_WO_BOT"]);
				$newRow["TIME_FIRST_ANSWER"] = self::formatDuration($newRow["TIME_FIRST_ANSWER"]);
				$newRow["TIME_DIALOG"] = self::formatDuration($row["data"]["TIME_DIALOG"]);
				$newRow["VOTE"] = self::formatVote($row["data"]["ID"], $row["data"]["VOTE"], 'VOTE');
				$newRow["TIME_MESSAGE_ANSWER_FIRST"] = self::formatDuration($newRow["TIME_MESSAGE_ANSWER_FIRST"]);
				$newRow["TIME_MESSAGE_ANSWER_FULL"] = self::formatDuration($newRow["TIME_MESSAGE_ANSWER_FULL"]);
				$newRow["TIME_MESSAGE_ANSWER_AVERAGE"] = self::formatDuration($newRow["TIME_MESSAGE_ANSWER_AVERAGE"]);
				$newRow["TIME_MESSAGE_ANSWER_MAX"] = self::formatDuration($newRow["TIME_MESSAGE_ANSWER_MAX"]);

				if (Config::canVoteAsHead($row['data']['CONFIG_ID'], false))
				{
					$permissionVoteHead = 'VOTE_HEAD_PERM';
					$permissionCommentHead = 'COMMENT_HEAD_PERM';
				}
				else
				{
					$permissionVoteHead = 'VOTE_HEAD';
					$permissionCommentHead = 'COMMENT_HEAD';
				}
				$newRow['VOTE_HEAD'] = self::formatVote($row['data']['ID'], $row['data']['VOTE_HEAD'], $permissionVoteHead);
				$newRow['COMMENT_HEAD'] = self::formatComment($row['data']['ID'], $row['data']['COMMENT_HEAD'], $permissionCommentHead);
			}

			$actions = [];
			if (!$this->excelMode)
			{
				if (!is_array($this->showHistory) || in_array($row["data"]["OPERATOR_ID"], $this->showHistory))
				{
					$actions[] = $arActivityMenuItems[] = [
						'TITLE' => Loc::getMessage('OL_COMPONENT_TABLE_ACTION_HISTORY'),
						'TEXT' => Loc::getMessage('OL_COMPONENT_TABLE_ACTION_HISTORY'),
						'ONCLICK' => "BXIM.openHistory('imol|{$row['data']['ID']}')",
						'DEFAULT' => true
					];
				}
				if ($configManager->canJoin($row['data']['CONFIG_ID']))
				{
					$actions[] = $arActivityMenuItems[] = [
						'TITLE' => Loc::getMessage('OL_COMPONENT_TABLE_ACTION_START'),
						'TEXT' => Loc::getMessage('OL_COMPONENT_TABLE_ACTION_START'),
						'ONCLICK' => "BXIM.openMessenger('imol|{$row['data']['USER_CODE']}')"
					];

					if(self::isNotCloseSession($row['data']['STATUS'], $row['data']['CLOSED']))
					{
						$currentUserId = $GLOBALS['USER']->GetId();

						if($row['data']['CHAT_OPERATOR_ID'] <= 0)
						{
							$textClose = Loc::getMessage('OL_COMPONENT_TABLE_ACTION_ANSWER_AND_CLOSE');
						}
						elseif($row['data']['CHAT_OPERATOR_ID'] == $currentUserId)
						{
							$textClose = Loc::getMessage('OL_COMPONENT_TABLE_ACTION_CLOSE');
						}
						else
						{
							$textClose = Loc::getMessage('OL_COMPONENT_TABLE_ACTION_CLOSE_ON_OPERATOR');
						}
						$actions[] = $arActivityMenuItems[] = [
							'TITLE' => $textClose,
							'TEXT' => $textClose,
							'ONCLICK' => "BX.OpenLines.Actions.close('{$row['data']['CHAT_ID']}')"
						];

						$actions[] = $arActivityMenuItems[] = [
							'TITLE' => Loc::getMessage('OL_COMPONENT_TABLE_ACTION_SPAN'),
							'TEXT' => Loc::getMessage('OL_COMPONENT_TABLE_ACTION_SPAN'),
							'ONCLICK' => "BX.OpenLines.Actions.closeSpam('{$row['data']['CHAT_ID']}')"
						];
					}
				}
			}

			if (!empty($actions))
			{
				$this->arResult["ELEMENTS_ROWS"][$key]["actions"] = $actions;
			}

			$this->arResult["ELEMENTS_ROWS"][$key]["columns"] = $newRow;
		}


		$this->arResult["HEADERS"] = array(
			array("id"=>"ID", "name"=> GetMessage("OL_STATS_HEADER_MODE_ID"), "default"=>true, "editable"=>false, "sort"=>"ID"),
			array("id"=>"CONFIG_ID", "name"=>GetMessage("OL_STATS_HEADER_CONFIG_NAME"), "default"=>false, "editable"=>false, "sort"=>"CONFIG_ID")
		);

		$this->arResult["HEADERS"] = array_merge($this->arResult["HEADERS"], Array(
			array("id"=>"MODE_NAME", "name"=>GetMessage("OL_STATS_HEADER_MODE_NAME"), "default"=>true, "editable"=>false, "sort"=>"MODE"),
			array("id"=>"STATUS", "name"=>GetMessage("OL_STATS_HEADER_STATUS"), "default"=>true, "editable"=>false),
			array("id"=>"STATUS_DETAIL", "name"=>GetMessage("OL_STATS_HEADER_STATUS_DETAIL"), "default"=>false, "editable"=>false),
			array("id"=>"SPAM", "name"=>GetMessage("OL_STATS_HEADER_SPAM"), "default"=>true, "editable"=>false, "sort"=>"SPAM"),
			array("id"=>"SOURCE_TEXT", "name"=>GetMessage("OL_STATS_HEADER_SOURCE_TEXT_2"), "default"=>true, "editable"=>false, "sort"=>"SOURCE"),
			array("id"=>"USER_NAME", "name"=>GetMessage("OL_STATS_HEADER_USER_NAME"), "default"=>true, "editable"=>false, "sort"=>"USER_ID"),
			array("id"=>"SEND_FORM", "name"=>GetMessage("OL_STATS_HEADER_SEND_FORM"), "default"=>false, "editable"=>false, "sort"=>"SEND_FORM"),
			array("id"=>"SEND_HISTORY", "name"=>GetMessage("OL_STATS_HEADER_SEND_HISTORY"), "default"=>false, "editable"=>false, "sort"=>"SEND_HISTORY"),
			array("id"=>"CRM_TEXT", "name"=>GetMessage("OL_STATS_HEADER_CRM_TEXT"), "default"=>true, "editable"=>false),
			array("id"=>"ACTION", "name"=>GetMessage("OL_STATS_HEADER_ACTION"), "default"=>true, "editable"=>false),
		));
		if ($this->excelMode)
		{
			$this->arResult["HEADERS"] = array_merge($this->arResult["HEADERS"], Array(
				array("id"=>"CRM_LINK", "name"=>GetMessage("OL_STATS_HEADER_CRM_LINK"), "default"=>true, "editable"=>false),
				array("id"=>"EXTRA_DOMAIN", "name"=>GetMessage("OL_STATS_HEADER_EXTRA_DOMAIN"), "default"=>true, "editable"=>false),
				array("id"=>"EXTRA_URL", "name"=>GetMessage("OL_STATS_HEADER_EXTRA_URL"), "default"=>true, "editable"=>false),
			));
		}
		else
		{
			$this->arResult["HEADERS"] = array_merge($this->arResult["HEADERS"], Array(
				array("id"=>"EXTRA_URL", "name"=>GetMessage("OL_STATS_HEADER_EXTRA_URL"), "default"=>true, "editable"=>false, "sort"=>"EXTRA_URL"),
			));
		}

		if (defined('IMOL_FDC'))
		{
			$this->arResult["HEADERS"] = array_merge($this->arResult["HEADERS"], Array(
				array("id"=>"EXTRA_REGISTER", "name"=>GetMessage("OL_STATS_HEADER_EXTRA_REGISTER"), "default"=>true, "editable"=>false, "sort"=>"EXTRA_REGISTER"),
				array("id"=>"EXTRA_TARIFF", "name"=>GetMessage("OL_STATS_HEADER_EXTRA_TARIFF"), "default"=>true, "editable"=>false, "sort"=>"EXTRA_TARIFF"),
				array("id"=>"EXTRA_USER_LEVEL", "name"=>GetMessage("OL_STATS_HEADER_EXTRA_USER_LEVEL"), "default"=>true, "editable"=>false, "sort"=>"EXTRA_USER_LEVEL"),
				array("id"=>"EXTRA_PORTAL_TYPE", "name"=>GetMessage("OL_STATS_HEADER_EXTRA_PORTAL_TYPE"), "default"=>true, "editable"=>false, "sort"=>"EXTRA_PORTAL_TYPE"),
			));
		}

		$this->arResult["HEADERS"] = array_merge($this->arResult["HEADERS"], [
			["id"=>"PAUSE_TEXT", "name"=>GetMessage("OL_STATS_HEADER_PAUSE_TEXT"), "default"=>false, "editable"=>false, "sort"=>"PAUSE"],
			["id"=>"WORKTIME_TEXT", "name"=>GetMessage("OL_STATS_HEADER_WORKTIME_TEXT"), "default"=>false, "editable"=>false, "sort"=>"WORKTIME"],
			["id"=>"MESSAGE_COUNT", "name"=>GetMessage("OL_STATS_HEADER_MESSAGE_COUNT_NEW_NEW"), "default"=>true, "editable"=>false, "sort"=>"MESSAGE_COUNT"],
			["id"=>"OPERATOR_NAME", "name"=>GetMessage("OL_STATS_HEADER_OPERATOR_NAME"), "default"=>true, "editable"=>false, "sort"=>"OPERATOR_ID"],
			["id"=>"DATE_CREATE", "name"=>GetMessage("OL_STATS_HEADER_DATE_CREATE"), "default"=>true, "editable"=>false, "sort"=>"DATE_CREATE"],
			["id"=>"DATE_OPERATOR", "name"=>GetMessage("OL_STATS_HEADER_DATE_OPERATOR_NEW_1"), "default"=>false, "editable"=>false],
			["id"=>"DATE_FIRST_ANSWER", "name"=>GetMessage("OL_STATS_HEADER_DATE_FIRST_ANSWER_NEW"), "default"=>true, "editable"=>false],
			["id"=>"DATE_OPERATOR_ANSWER", "name"=>GetMessage("OL_STATS_HEADER_DATE_OPERATOR_ANSWER_NEW_1"), "default"=>false, "editable"=>false],
			["id"=>"DATE_LAST_MESSAGE", "name"=>GetMessage("OL_STATS_HEADER_DATE_LAST_MESSAGE"), "default"=>true, "editable"=>false],
			["id"=>"DATE_OPERATOR_CLOSE", "name"=>GetMessage("OL_STATS_HEADER_DATE_OPERATOR_CLOSE_NEW"), "default"=>true, "editable"=>false],
			["id"=>"DATE_CLOSE", "name"=>GetMessage("OL_STATS_HEADER_DATE_CLOSE"), "default"=>false, "editable"=>false, "sort"=>"DATE_CLOSE"],
			["id"=>"DATE_MODIFY", "name"=>GetMessage("OL_STATS_HEADER_DATE_MODIFY"), "default"=>false, "editable"=>false, "sort"=>"DATE_MODIFY"],
			["id"=>"TIME_FIRST_ANSWER", "name"=>GetMessage("OL_STATS_HEADER_TIME_FIRST_ANSWER_NEW"), "default"=>true, "editable"=>false],
			["id"=>"TIME_ANSWER_WO_BOT", "name"=>GetMessage("OL_STATS_HEADER_TIME_ANSWER_WO_BOT_NEW"), "default"=>false, "editable"=>false],
			["id"=>"TIME_CLOSE_WO_BOT", "name"=>GetMessage("OL_STATS_HEADER_TIME_CLOSE_WO_BOT_1_NEW"), "default"=>false, "editable"=>false],
		//	["id"=>"TIME_ANSWER", "name"=>GetMessage("OL_STATS_HEADER_TIME_ANSWER_NEW"), "default"=>false, "editable"=>false],
		//	["id"=>"TIME_CLOSE", "name"=>GetMessage("OL_STATS_HEADER_TIME_CLOSE_1_NEW"), "default"=>false, "editable"=>false],
			["id"=>"TIME_DIALOG_WO_BOT", "name"=>GetMessage("OL_STATS_HEADER_TIME_DIALOG_WO_BOT_1"), "default"=>true, "editable"=>false],
		//	["id"=>"TIME_DIALOG", "name"=>GetMessage("OL_STATS_HEADER_TIME_DIALOG_1"), "default"=>false, "editable"=>false],
			["id"=>"TIME_BOT", "name"=>GetMessage("OL_STATS_HEADER_TIME_BOT"), "default"=>true, "editable"=>false],
			["id"=>"TIME_MESSAGE_ANSWER_FIRST", "name"=>GetMessage("OL_STATS_HEADER_TIME_MESSAGE_ANSWER_FIRST"), "default"=>true, "editable"=>false],
			["id"=>"TIME_MESSAGE_ANSWER_FULL", "name"=>GetMessage("OL_STATS_HEADER_TIME_MESSAGE_ANSWER_FULL"), "default"=>true, "editable"=>false],
			["id"=>"TIME_MESSAGE_ANSWER_AVERAGE", "name"=>GetMessage("OL_STATS_HEADER_TIME_MESSAGE_ANSWER_AVERAGE"), "default"=>true, "editable"=>false],
			["id"=>"TIME_MESSAGE_ANSWER_MAX", "name"=>GetMessage("OL_STATS_HEADER_TIME_MESSAGE_ANSWER_MAX"), "default"=>true, "editable"=>false],
			["id"=>"VOTE", "name"=>GetMessage("OL_STATS_HEADER_VOTE_CLIENT"), "default"=>true, "editable"=>false, "sort"=>"VOTE"],
			["id"=>"COMMENT_HEAD", "name"=>GetMessage("OL_STATS_HEADER_COMMENT_HEAD"), "default"=>true, "editable"=>false],
			["id"=>"VOTE_HEAD", "name"=>GetMessage("OL_STATS_HEADER_VOTE_HEAD_1"), "default"=>true, "editable"=>false, "sort"=>"VOTE_HEAD"],
		]);

		//UF ->
		foreach($this->arResult["UF_FIELDS"] as $ufResult)
		{
			if(!empty($ufResult["LIST_COLUMN_LABEL"]))
			{
				$name = $ufResult["LIST_COLUMN_LABEL"];
			}
			else if(!empty($ufResult["EDIT_FORM_LABEL"]))
			{
				$name = $ufResult["EDIT_FORM_LABEL"];
			}
			else
			{
				$name = $ufResult["FIELD_NAME"];
			}

			$this->arResult["HEADERS"][$ufResult["FIELD_NAME"]] = array("id"=>$ufResult["FIELD_NAME"], "name"=>$name, "default"=>false, "editable"=>false);
		}
		//<- UF

		if ($this->excelMode)
		{
			// We should use only selected grid columns for export
			if (!empty($this->gridOptions->GetVisibleColumns()))
			{
				foreach ($gridHeaders as $gridHeader)
				{
					foreach ($this->arResult['HEADERS'] as $header)
					{
						if ($gridHeader === $header['id'])
						{
							$this->arResult['SELECTED_HEADERS'][] = $header;
						}
					}

					if ($gridHeader === 'CRM_TEXT')
					{
						$this->arResult['SELECTED_HEADERS'][] = ['id' => 'CRM_LINK', 'name' => Loc::getMessage('OL_STATS_HEADER_CRM_LINK')];
					}
				}
			}
			else //case when grid columns are never changed
			{
				foreach ($this->arResult['HEADERS'] as $header)
				{
					if ($header['default'])
					{
						$this->arResult['SELECTED_HEADERS'][] = $header;
					}
				}
			}

			$this->includeComponentTemplate('excel');
			return [
				'PROCESSED_ITEMS' => count($this->arResult['ELEMENTS_ROWS']),
				'TOTAL_ITEMS' => $this->arResult['ROWS_COUNT']
			];
		}
		else
		{
			if(\Bitrix\Main\Loader::includeModule("pull"))
			{
				global $USER;
				\CPullWatch::Add($USER->GetId(), 'IMOL_STATISTICS');
			}

			$this->includeComponentTemplate();
			return $this->arResult;
		}
	}
};
