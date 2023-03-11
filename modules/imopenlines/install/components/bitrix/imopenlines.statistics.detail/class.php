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
use Bitrix\Main\Web\Uri;

class ImOpenLinesComponentStatisticsDetail extends \CBitrixComponent
{
	protected $gridId = 'imopenlines_statistic_v3';
	protected $filterId = 'imopenlines_statistic_detail_filter';

	/** @var  \Bitrix\Main\Grid\Options */
	protected $gridOptions;

	// export flags
	protected $excelMode = false;
	protected $enableExport = true;
	protected $enableNextPage = false;

	/** @var Security\Permissions */
	protected $userPermissions;
	protected $showHistory;
	protected $configId;
	protected $ufFields;
	protected $isNeedKpi = false;
	protected $filterFields;

	//region Init

	/**
	 * @return void
	 */
	private function init(): void
	{
		$this->enableExport = Limit::canStatisticsExcel();

		$this->gridOptions = new \Bitrix\Main\Grid\Options($this->gridId);

		$request = HttpApplication::getInstance()->getContext()->getRequest();

		if ($this->enableExport && $request->get('EXPORT_TYPE') === 'excel')
		{
			$this->excelMode = true;
		}

		if ($this->enableExport)
		{
			$this->arResult['STEXPORT_PARAMS'] = [
				'SITE_ID' => SITE_ID,
				'EXPORT_TYPE' => 'excel',
				'COMPONENT_NAME' => $this->getName(),
			];
			if ($this->listKeysSignedParameters())
			{
				$this->arResult['STEXPORT_PARAMS']['signedParameters'] = $this->getSignedParameters();
			}

			$this->arResult['BUTTON_EXPORT'] = 'BX.UI.StepProcessing.ProcessManager.get(\'OpenLinesExport\').showDialog()';
			$this->arResult['LIMIT_EXPORT'] = false;

			$this->arResult['STEXPORT_TOTAL_ITEMS'] = (isset($this->arParams['STEXPORT_TOTAL_ITEMS']) ?
				(int)$this->arParams['STEXPORT_TOTAL_ITEMS'] : 0);
		}
		else
		{
			$this->arResult['BUTTON_EXPORT'] = 'BX.UI.InfoHelper.show(\'' . Limit::INFO_HELPER_LIMIT_CONTACT_CENTER_STATISTICS_EXCEL . '\');';
			$this->arResult['LIMIT_EXPORT'] = true;
		}

		$this->configId = 0;
		if ($request->get('CONFIG_ID'))
		{
			$this->configId = (int)$request->get('CONFIG_ID');
			$config = Config::getInstance()->get($this->configId);
			$this->arResult['LINE_NAME'] = $config['LINE_NAME'];
		}

		$this->arResult['LINES'] = $this->getConfigList();
		$this->arResult['groupActionsData'] = $this->getGroupActionsData();
		//UF
		$this->arResult['UF_FIELDS'] = $this->getUfFieldList();

		$this->showHistory = Security\Helper::getAllowedUserIds(
			Security\Helper::getCurrentUserId(),
			$this->userPermissions->getPermission(Security\Permissions::ENTITY_HISTORY, Security\Permissions::ACTION_VIEW)
		);

		$this->arResult['FDC_MODE'] = $this->isFdcMode();
		$this->arResult['ALLOW_MODIFY_SETTINGS'] = $this->isFdcMode() && $this->userPermissions->canModifySettings();
		if ($this->arResult['ALLOW_MODIFY_SETTINGS'] && Loader::includeModule('intranet'))
		{
			$this->arResult['UF_LIST_CONFIG_URL'] = \Bitrix\Intranet\Util::getUserFieldListConfigUrl('imopenlines', 'IMOPENLINES_SESSION');
		}
	}

	/**
	 * @return \CUser
	 */
	private function getCurrentUser()
	{
		/** @global \CUser $USER */
		global $USER;
		return $USER;
	}

	/**
	 * @return \CUserTypeManager
	 */
	private function getUfTypeManager()
	{
		return \Bitrix\Imopenlines\Helpers\Filter::getUfTypeManager();
	}

	/**
	 * @return bool
	 */
	private function isFdcMode(): bool
	{
		return \Bitrix\Imopenlines\Helpers\Filter::isFdcMode();
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
	 * @return array
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

	/**
	 * @return array
	 */
	protected function getUfFieldList(): array
	{
		if ($this->ufFields === null)
		{
			$this->ufFields = \Bitrix\ImOpenLines\Helpers\Filter::getUfFieldList();
		}

		return $this->ufFields;
	}

	/**
	 * @return bool
	 */
	protected function checkModules(): bool
	{
		if (!Loader::includeModule('im'))
		{
			\ShowError(Loc::getMessage('OL_COMPONENT_MODULE_NOT_INSTALLED'));
			return false;
		}

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

	/**
	 * @return bool
	 */
	protected function checkAccess(): bool
	{
		$this->userPermissions = Security\Permissions::createWithCurrentUser();

		if(!$this->userPermissions->canPerform(Security\Permissions::ENTITY_SESSION, Security\Permissions::ACTION_VIEW))
		{
			\ShowError(Loc::getMessage('OL_COMPONENT_ACCESS_DENIED'));
			return false;
		}

		return true;
	}

	//endregion

	//region Format data

	/**
	 * @param array $row
	 *
	 * @return string
	 */
	public static function getFormattedCrmColumn($row): string
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

	/**
	 * @param string $type
	 *
	 * @return string
	 */
	private static function getCrmName($type): string
	{
		$name = '';

		if (\Bitrix\Main\Loader::includeModule('crm'))
		{
			$name = \CCrmOwnerType::GetDescription(\CCrmOwnerType::ResolveID($type));
		}

		return $name;
	}

	/**
	 * @param $row
	 * @return array
	 */
	private static function getCrmLink($row): array
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
	 * @param array $row
	 * @return string
	 */
	private static function getCrmActivityLink($row): string
	{
		$result = '';

		$domain = \Bitrix\ImOpenLines\Common::getServerAddress();

		if ($row['CRM'] == 'Y' && $row['CRM_ACTIVITY_ID'] > 0)
		{
			$result = $domain . \Bitrix\ImOpenLines\Crm\Common::getLink('ACTIVITY', $row['CRM_ACTIVITY_ID']);
		}

		return $result;
	}

	/**
	 * @param \Bitrix\Main\Type\DateTime $date
	 *
	 * @return string
	 */
	private static function formatDate($date): string
	{
		if (
			!$date
			|| !($date instanceof \Bitrix\Main\Type\DateTime)
		)
		{
			return '-';
		}

		return \FormatDate('x', $date->toUserTime()->getTimestamp(), (time() + \CTimeZone::getOffset()));
	}

	/**
	 * @param int $duration
	 *
	 * @return string
	 */
	private static function formatDuration($duration): string
	{
		$duration = (int)$duration;
		if ($duration <= 0)
		{
			return '-';
		}

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
	 * @param int $sessionId
	 * @param int $rating
	 * @param string $field Enum: VOTE | VOTE_HEAD | VOTE_HEAD_PERM.
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
	 * @param int $sessionId
	 * @param string $comment
	 * @param string $field Enum: COMMENT_HEAD | COMMENT_HEAD_PERM.
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

	//endregion

	//region Filter

	/**
	 * Returns list with filter fields.
	 * @return array
	 */
	private function getFilterDefinition()
	{
		if ($this->filterFields === null)
		{
			$this->filterFields = array(
				'CONFIG_ID' => array(
					'id' => 'CONFIG_ID',
					'name' => Loc::getMessage('OL_STATS_HEADER_CONFIG_NAME'),
					'type' => 'list',
					'items' => $this->arResult['LINES'],
					'default' => !$this->configId,
					'default_value' => $this->configId ? $this->configId : '',
					'params' => array(
						'multiple' => 'Y'
					)
				),

				'TYPE' => array(
					'id' => 'TYPE',
					'name' => Loc::getMessage('OL_STATS_HEADER_MODE_NAME'),
					'type' => 'list',
					'items' => array(
						"" => Loc::getMessage('OL_STATS_FILTER_UNSET'),
						'input' => Loc::getMessage('OL_COMPONENT_TABLE_INPUT'),
						'output' => Loc::getMessage('OL_COMPONENT_TABLE_OUTPUT'),
					),
					'default' => false,
				),
				'DATE_CREATE' => array(
					'id' => 'DATE_CREATE',
					'name' => Loc::getMessage('OL_STATS_HEADER_DATE_CREATE'),
					'type' => 'date',
					'default' => true
				),
				'DATE_CLOSE' => array(
					'id' => 'DATE_CLOSE',
					'name' => Loc::getMessage('OL_STATS_HEADER_DATE_CLOSE'),
					'type' => 'date',
					'default' => false
				),
				'OPERATOR_ID' => array(
					'id' => 'OPERATOR_ID',
					'name' => Loc::getMessage('OL_STATS_HEADER_OPERATOR_NAME'),
					'type' => 'dest_selector',
					'params' => array(
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
					'default' => true,
				),
				'CLIENT_NAME' => array(
					'id' => 'CLIENT_NAME',
					'name' => Loc::getMessage('OL_STATS_HEADER_USER_NAME'),
					'type' => 'string',
					'default' => false,
				),
				'SOURCE' => array(
					'id' => 'SOURCE',
					'name' => Loc::getMessage('OL_STATS_HEADER_SOURCE_TEXT_2'),
					'type' => 'list',
					'items' => \Bitrix\ImConnector\Connector::getListConnector(),
					'default' => true,
					'params' => array(
						'multiple' => 'Y'
					)
				),
				'ID' => array(
					'id' => 'ID',
					'name' => Loc::getMessage('OL_STATS_HEADER_SESSION_ID'),
					'type' => 'string',
					'default' => true
				),
				'EXTRA_URL' => array(
					'id' => 'EXTRA_URL',
					'name' => Loc::getMessage('OL_STATS_HEADER_EXTRA_URL'),
					'type' => 'string',
					'default' => false
				),
				'STATUS' => array(
					'id' => 'STATUS',
					'name' => Loc::getMessage('OL_STATS_HEADER_STATUS'),
					'type' => 'list',
					'items' => array(
						"" => Loc::getMessage('OL_STATS_FILTER_UNSET'),
						'client' => Loc::getMessage('OL_COMPONENT_TABLE_STATUS_CLIENT_NEW'),
						'operator' => Loc::getMessage('OL_COMPONENT_TABLE_STATUS_OPERATOR_NEW'),
						'closed' => Loc::getMessage('OL_COMPONENT_TABLE_STATUS_CLOSED'),
					),
					'default' => true,
				),
				'STATUS_DETAIL' => array(
					'id' => 'STATUS_DETAIL',
					'name' => Loc::getMessage('OL_STATS_HEADER_STATUS_DETAIL'),
					'type' => 'list',
					'items' => array(
						'' => Loc::getMessage('OL_STATS_FILTER_UNSET'),
						(string)Session::STATUS_NEW => Loc::getMessage('OL_COMPONENT_TABLE_STATUS_NEW'),
						(string)Session::STATUS_SKIP => Loc::getMessage('OL_COMPONENT_TABLE_STATUS_OPERATOR_SKIP_NEW'),
						(string)Session::STATUS_ANSWER => Loc::getMessage('OL_COMPONENT_TABLE_STATUS_OPERATOR_ANSWER_NEW'),
						(string)Session::STATUS_CLIENT => Loc::getMessage('OL_COMPONENT_TABLE_STATUS_CLIENT_NEW'),
						(string)Session::STATUS_CLIENT_AFTER_OPERATOR => Loc::getMessage('OL_COMPONENT_TABLE_STATUS_CLIENT_AFTER_OPERATOR_NEW'),
						(string)Session::STATUS_OPERATOR => Loc::getMessage('OL_COMPONENT_TABLE_STATUS_OPERATOR_NEW'),
						(string)Session::STATUS_WAIT_CLIENT => Loc::getMessage('OL_COMPONENT_TABLE_STATUS_WAIT_ACTION_2'),
						(string)Session::STATUS_CLOSE => Loc::getMessage('OL_COMPONENT_TABLE_STATUS_CLOSED'),
						(string)Session::STATUS_SPAM => Loc::getMessage('OL_STATS_HEADER_SPAM_2'),
					),
					'params' => array(
						'multiple' => 'Y'
					),
					'default' => false
				),
			);
			if ($this->isFdcMode())
			{
				$this->filterFields['EXTRA_TARIFF'] = array(
					'id' => 'EXTRA_TARIFF',
					'name' => Loc::getMessage('OL_STATS_HEADER_EXTRA_TARIFF'),
					'type' => 'string',
					'default' => false
				);
				$this->filterFields['EXTRA_USER_LEVEL'] = array(
					'id' => 'EXTRA_USER_LEVEL',
					'name' => Loc::getMessage('OL_STATS_HEADER_EXTRA_USER_LEVEL'),
					'type' => 'string',
					'default' => false
				);
				$this->filterFields['EXTRA_PORTAL_TYPE'] = array(
					'id' => 'EXTRA_PORTAL_TYPE',
					'name' => Loc::getMessage('OL_STATS_HEADER_EXTRA_PORTAL_TYPE'),
					'type' => 'string',
					'default' => false
				);
				$this->filterFields['EXTRA_REGISTER'] = array(
					'id' => 'EXTRA_REGISTER',
					'name' => Loc::getMessage('OL_STATS_HEADER_EXTRA_REGISTER'),
					'default' => false,
					'type' => 'number'
				);
			}
			if (Loader::includeModule('crm'))
			{
				$this->filterFields['CRM'] = array(
					'id' => 'CRM',
					'name' => Loc::getMessage('OL_STATS_HEADER_CRM'),
					'default' => false,
					'type' => 'list',
					'items' => array(
						"" => Loc::getMessage('OL_STATS_FILTER_UNSET'),
						'Y' => Loc::getMessage('OL_STATS_FILTER_Y'),
						'N' => Loc::getMessage('OL_STATS_FILTER_N'),
					)
				);
				/*
				$this->filterFields['CRM_ENTITY'] = array(
					'id' => 'CRM_ENTITY',
					'name' => Loc::getMessage('OL_STATS_HEADER_CRM_TEXT'),
					'default' => false,
					'type' => 'custom_entity',
					'selector' => array(
						'TYPE' => 'crm_entity',
						'DATA' => array(
							'ID' => 'CRM_ENTITY',
							'FIELD_ID' => 'CRM_ENTITY',
							'ENTITY_TYPE_NAMES' => array(CCrmOwnerType::LeadName, CCrmOwnerType::CompanyName, CCrmOwnerType::ContactName, CCrmOwnerType::DealName),
							'IS_MULTIPLE' => false
						)
					)
				);
				*/
			}

			$this->filterFields = array_merge($this->filterFields, array(
				'SEND_FORM' => array(
					'id' => 'SEND_FORM',
					'name' => Loc::getMessage('OL_STATS_HEADER_SEND_FORM'),
					'default' => false,
					'type' => 'list',
					'items' => array(
						"" => Loc::getMessage('OL_STATS_FILTER_UNSET'),
						'Y' => Loc::getMessage('OL_STATS_FILTER_Y'),
						'N' => Loc::getMessage('OL_STATS_FILTER_N'),
					)
				),
				'SEND_HISTORY' => array(
					'id' => 'SEND_HISTORY',
					'name' => Loc::getMessage('OL_STATS_HEADER_SEND_HISTORY'),
					'default' => false,
					'type' => 'list',
					'items' => array(
						"" => Loc::getMessage('OL_STATS_FILTER_UNSET'),
						'Y' => Loc::getMessage('OL_STATS_FILTER_Y'),
						'N' => Loc::getMessage('OL_STATS_FILTER_N'),
					)
				),
				'WORKTIME' => array(
					'id' => 'WORKTIME',
					'name' => Loc::getMessage('OL_STATS_HEADER_WORKTIME_TEXT'),
					'default' => false,
					'type' => 'list',
					'items' => array(
						"" => Loc::getMessage('OL_STATS_FILTER_UNSET'),
						'Y' => Loc::getMessage('OL_STATS_FILTER_Y'),
						'N' => Loc::getMessage('OL_STATS_FILTER_N'),
					)
				),
				'SPAM' => array(
					'id' => 'SPAM',
					'name' => Loc::getMessage('OL_STATS_HEADER_SPAM_2'),
					'default' => false,
					'type' => 'list',
					'items' => array(
						"" => Loc::getMessage('OL_STATS_FILTER_UNSET'),
						'Y' => Loc::getMessage('OL_STATS_FILTER_Y'),
						'N' => Loc::getMessage('OL_STATS_FILTER_N'),
					)
				),
				'MESSAGE_COUNT' => array(
					'id' => 'MESSAGE_COUNT',
					'name' => Loc::getMessage('OL_STATS_FILTER_MESSAGE_COUNT'),
					'default' => false,
					'type' => 'number'
				),
				'VOTE' => array(
					'id' => 'VOTE',
					'name' => Loc::getMessage('OL_STATS_HEADER_VOTE_CLIENT'),
					'default' => false,
					'type' => 'list',
					'items' => array(
						"" => Loc::getMessage('OL_STATS_FILTER_UNSET'),
						'5' => Loc::getMessage('OL_STATS_HEADER_VOTE_CLIENT_LIKE'),
						'1' => Loc::getMessage('OL_STATS_HEADER_VOTE_CLIENT_DISLIKE'),
					)
				),
				'VOTE_HEAD' => array(
					'id' => 'VOTE_HEAD',
					'name' => Loc::getMessage('OL_STATS_HEADER_VOTE_HEAD_1'),
					'default' => false,
					'type' => 'list',
					'items' => array(
						'wo' => Loc::getMessage('OL_STATS_HEADER_VOTE_HEAD_WO'),
						'5' => Session::VOTE_LIKE,
						'4' => 4,
						'3' => 3,
						'2' => 2,
						'1' => Session::VOTE_DISLIKE,
					),
					'params' => array(
						'multiple' => 'Y'
					)
				),
			));

			// UF
			foreach ($this->getUfFieldList() as $fieldName => $field)
			{
				if (
					$field['SHOW_FILTER'] != 'N'
					&& $field['USER_TYPE']['BASE_TYPE'] != \CUserTypeManager::BASE_TYPE_FILE
				)
				{
					$fieldClass = $field['USER_TYPE']['CLASS_NAME'];
					if (
						is_a($fieldClass, \Bitrix\Main\UserField\Types\BaseType::class, true)
						&& is_callable([$fieldClass, 'getFilterData'])
					)
					{
						$this->filterFields[$fieldName] = $fieldClass::getFilterData(
							$field,
							[
								'ID' => $fieldName,
								'NAME' => $field['LIST_FILTER_LABEL'] ?
									$field['LIST_FILTER_LABEL'] : $field['FIELD_NAME'],
							]
						);
					}
				}
			}
		}

		return $this->filterFields;
	}

	/**
	 * @return array
	 */
	private function getFilter()
	{
		$filterDefinition = $this->getFilterDefinition();
		return \Bitrix\ImOpenLines\Helpers\Filter::getFilter($this->filterId, $this->arResult, $filterDefinition);
	}

	/**
	 * @param string $path
	 * @param array $parameters
	 *
	 * @return string
	 */
	protected function getFilterUrl(string $path, array $parameters = []): string
	{
		$uri = new Uri($path);

		$filterReserved = [
			//filter
			'clear_filter',
			'apply_filter',
			'PRESET_ID',
			'FILTER_ID',
			'FILTER_APPLIED',
			//grid
			'clear_nav',
			'internal',
			'grid_id',
			'grid_action',
			//ajax
			'bxajaxid',
			'AJAX_CALL',
		];

		$filterKeys = array_keys($this->getFilterDefinition());
		$filterKeys[] = 'FIND';

		foreach ($this->getFilterDefinition() as $key => $field)
		{
			if ($field['type'] === 'date')
			{
				$filterKeys[] = $key. '_datesel';
				$filterKeys[] = $key. '_month';
				$filterKeys[] = $key. '_year';
				$filterKeys[] = $key. '_quarter';
				$filterKeys[] = $key. '_from';
				$filterKeys[] = $key. '_to';
			}
			elseif ($field['type'] === 'number')
			{
				$filterKeys[] = $key. '_numsel';
				$filterKeys[] = $key. '_from';
				$filterKeys[] = $key. '_to';
			}
		}

		$uri->deleteParams(array_merge(
			\Bitrix\Main\HttpRequest::getSystemParameters(),
			$filterReserved,
			$filterKeys
		));

		$filterOptions = new \Bitrix\Main\UI\Filter\Options($this->filterId);
		$filter = $filterOptions->getFilter($this->getFilterDefinition());

		$params = [];
		foreach ($filter as $key => $value)
		{
			if (in_array($key, $filterReserved) || !in_array($key, $filterKeys))
			{
				continue;
			}
			if (!empty($value))
			{
				$params[$key] = $value;
			}
		}
		foreach ($parameters as $key => $value)
		{
			if (!empty($value))
			{
				$params[$key] = $value;
			}
		}
		if (count($params) > 0)
		{
			$params['apply_filter'] = 'Y';
		}
		else
		{
			$params['apply_filter'] = 'Y';
			$params['clear_filter'] = 'Y';
		}

		$uri->addParams($params);

		return $uri->getLocator();
	}

	//endregion

	//region Format User

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
			if ($userId > 0 && isset($userData[$userId]))
			{
				$photoStyle = '';
				$photoClass = '';

				if (!empty($userData[$userId]["PHOTO"]))
				{
					$photoStyle = "background: url('".Uri::urnEncode($userData[$userId]["PHOTO"])."') no-repeat center;";
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

	// endregion

	//region Status

	/**
	 * @param $status
	 * @param $close
	 * @return bool
	 */
	protected function isNotCloseSession($status, $close): bool
	{
		$result = false;

		if (
			$status < Session::STATUS_WAIT_CLIENT &&
			$close !== 'Y'
		)
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @param $status
	 * @return string
	 */
	protected function formatStatus($status): string
	{
		$result = $status;

		if ($status < Session::STATUS_OPERATOR)
		{
			$result = Loc::getMessage('OL_COMPONENT_TABLE_STATUS_CLIENT_NEW');
		}
		else if ($status >= Session::STATUS_OPERATOR && $status < Session::STATUS_CLOSE)
		{
			$result = Loc::getMessage('OL_COMPONENT_TABLE_STATUS_OPERATOR_NEW');
		}
		else if ($status >= Session::STATUS_CLOSE)
		{
			$result = Loc::getMessage('OL_COMPONENT_TABLE_STATUS_CLOSED');
		}

		return $result;
	}

	/**
	 * @param $status
	 * @return string
	 */
	protected function formatStatusDetail($status): string
	{
		$result = $status;

		switch ($status)
		{
			case Session::STATUS_NEW:
				$result = Loc::getMessage('OL_COMPONENT_TABLE_STATUS_NEW');
				break;
			case Session::STATUS_SKIP:
				$result = Loc::getMessage('OL_COMPONENT_TABLE_STATUS_OPERATOR_SKIP_NEW');
				break;
			case Session::STATUS_ANSWER:
				$result = Loc::getMessage('OL_COMPONENT_TABLE_STATUS_OPERATOR_ANSWER_NEW');
				break;
			case Session::STATUS_CLIENT:
				$result = Loc::getMessage('OL_COMPONENT_TABLE_STATUS_CLIENT_NEW');
				break;
			case Session::STATUS_CLIENT_AFTER_OPERATOR:
				$result = Loc::getMessage('OL_COMPONENT_TABLE_STATUS_CLIENT_AFTER_OPERATOR_NEW');
				break;
			case Session::STATUS_OPERATOR:
				$result = Loc::getMessage('OL_COMPONENT_TABLE_STATUS_OPERATOR_NEW');
				break;
			case Session::STATUS_WAIT_CLIENT:
				$result = Loc::getMessage('OL_COMPONENT_TABLE_STATUS_WAIT_ACTION_2');
				break;
			case Session::STATUS_CLOSE:
			case Session::STATUS_DUPLICATE:
			case Session::STATUS_SILENTLY_CLOSE:
				$result = Loc::getMessage('OL_COMPONENT_TABLE_STATUS_CLOSED');
				break;
			case Session::STATUS_SPAM:
				$result = Loc::getMessage('OL_STATS_HEADER_SPAM_2');
				break;
		}

		return $result;
	}

	//endregion

	/**
	 * @return array
	 */
	protected function prepareGroupActions(): array
	{
		$result = [];

		$userPermissions = Permissions::createWithCurrentUser();

		if ($userPermissions->canPerform(Permissions::ENTITY_SESSION, Permissions::ACTION_VIEW))
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
	 * @return array
	 */
	protected function prepareHeaders(): array
	{
		$result = [
			[
				'id' => 'ID',
				'name' => Loc::getMessage('OL_STATS_HEADER_MODE_ID'),
				'default' => true,
				'editable' => false,
				'sort' => 'ID',
			],
			[
				'id' => 'CONFIG_ID',
				'name' => Loc::getMessage('OL_STATS_HEADER_CONFIG_NAME'),
				'default' => false,
				'editable' => false,
				'sort' => 'CONFIG_ID',
			],
			[
				'id' => 'MODE_NAME',
				'name' => Loc::getMessage('OL_STATS_HEADER_MODE_NAME'),
				'default' => true,
				'editable' => false,
				'sort' => 'MODE',
			],
			[
				'id' => 'STATUS',
				'name' => Loc::getMessage('OL_STATS_HEADER_STATUS'),
				'default' => true,
				'editable' => false,
				'sort' => 'STATUS',
			],
			[
				'id' => 'STATUS_DETAIL',
				'name' => Loc::getMessage('OL_STATS_HEADER_STATUS_DETAIL'),
				'default' => false,
				'editable' => false,
				'sort' => 'STATUS',
			],
			[
				'id' => 'SPAM',
				'name' => Loc::getMessage('OL_STATS_HEADER_SPAM'),
				'default' => true,
				'editable' => false,
				'sort' => 'SPAM',
			],
			[
				'id' => 'SOURCE_TEXT',
				'name' => Loc::getMessage('OL_STATS_HEADER_SOURCE_TEXT_2'),
				'default' => true,
				'editable' => false,
				'sort' => 'SOURCE',
			],
			[
				'id' => 'USER_NAME',
				'name' => Loc::getMessage('OL_STATS_HEADER_USER_NAME'),
				'default' => true,
				'editable' => false,
				'sort' => 'USER_ID',
			],
			[
				'id' => 'SEND_FORM',
				'name' => Loc::getMessage('OL_STATS_HEADER_SEND_FORM'),
				'default' => false,
				'editable' => false,
				'sort' => 'SEND_FORM',
			],
			[
				'id' => 'SEND_HISTORY',
				'name' => Loc::getMessage('OL_STATS_HEADER_SEND_HISTORY'),
				'default' => false,
				'editable' => false,
				'sort' => 'SEND_HISTORY',
			],
			[
				'id' => 'CRM_TEXT',
				'name' => Loc::getMessage('OL_STATS_HEADER_CRM_TEXT'),
				'default' => true,
				'editable' => false,
			],
			[
				'id' => 'ACTION',
				'name' => Loc::getMessage('OL_STATS_HEADER_ACTION'),
				'default' => true,
				'editable' => false,
			]
		];

		if ($this->excelMode)
		{
			$result[] = [
				'id' => 'CRM_LINK',
				'name' => Loc::getMessage('OL_STATS_HEADER_CRM_LINK'),
				'default' => true,
				'editable' => false,
			];
			$result[] = [
				'id' => 'EXTRA_DOMAIN',
				'name' => Loc::getMessage('OL_STATS_HEADER_EXTRA_DOMAIN'),
				'default' => true,
				'editable' => false,
			];
			$result[] = [
				'id' => 'EXTRA_URL',
				'name' => Loc::getMessage('OL_STATS_HEADER_EXTRA_URL'),
				'default' => true,
				'editable' => false,
			];
		}
		else
		{
			$result[] = [
				'id' => 'EXTRA_URL',
				'name' => Loc::getMessage('OL_STATS_HEADER_EXTRA_URL'),
				'default' => true,
				'editable' => false,
				'sort' => 'EXTRA_URL',
			];
		}

		if ($this->isFdcMode())
		{
			$result[] = [
				'id' => 'EXTRA_REGISTER',
				'name' => Loc::getMessage('OL_STATS_HEADER_EXTRA_REGISTER'),
				'default' => true,
				'editable' => false,
				'sort' => 'EXTRA_REGISTER'
			];
			$result[] = [
				'id' => 'EXTRA_TARIFF',
				'name' => Loc::getMessage('OL_STATS_HEADER_EXTRA_TARIFF'),
				'default' => true,
				'editable' => false,
				'sort' => 'EXTRA_TARIFF'
			];
			$result[] = [
				'id' => 'EXTRA_USER_LEVEL',
				'name' => Loc::getMessage('OL_STATS_HEADER_EXTRA_USER_LEVEL'),
				'default' => true,
				'editable' => false,
				'sort' => 'EXTRA_USER_LEVEL'
			];
			$result[] = [
				'id' => 'EXTRA_PORTAL_TYPE',
				'name' => Loc::getMessage('OL_STATS_HEADER_EXTRA_PORTAL_TYPE'),
				'default' => true,
				'editable' => false,
				'sort' => 'EXTRA_PORTAL_TYPE'
			];
		}

		$result = array_merge($result, [
			[
				'id' => 'PAUSE_TEXT',
				'name' => Loc::getMessage('OL_STATS_HEADER_PAUSE_TEXT'),
				'default' => false,
				'editable' => false,
				'sort' => 'PAUSE',
			],
			[
				'id' => 'WORKTIME_TEXT',
				'name' => Loc::getMessage('OL_STATS_HEADER_WORKTIME_TEXT'),
				'default' => false,
				'editable' => false,
				'sort' => 'WORKTIME',
			],
			[
				'id' => 'MESSAGE_COUNT',
				'name' => Loc::getMessage('OL_STATS_HEADER_MESSAGE_COUNT_NEW_NEW'),
				'default' => true,
				'editable' => false,
				'sort' => 'MESSAGE_COUNT',
			],
			[
				'id' => 'OPERATOR_NAME',
				'name' => Loc::getMessage('OL_STATS_HEADER_OPERATOR_NAME'),
				'default' => true,
				'editable' => false,
				'sort' => 'OPERATOR_ID',
			],
			[
				'id' => 'DATE_CREATE',
				'name' => Loc::getMessage('OL_STATS_HEADER_DATE_CREATE'),
				'default' => true,
				'editable' => false,
				'sort' => 'DATE_CREATE',
			],
			[
				'id' => 'DATE_OPERATOR',
				'name' => Loc::getMessage('OL_STATS_HEADER_DATE_OPERATOR_NEW_1'),
				'default' => false,
				'editable' => false,
			],
			[
				'id' => 'DATE_FIRST_ANSWER',
				'name' => Loc::getMessage('OL_STATS_HEADER_DATE_FIRST_ANSWER_NEW'),
				'default' => true,
				'editable' => false,
			],
			[
				'id' => 'DATE_OPERATOR_ANSWER',
				'name' => Loc::getMessage('OL_STATS_HEADER_DATE_OPERATOR_ANSWER_NEW_1'),
				'default' => false,
				'editable' => false,
			],
			[
				'id' => 'DATE_LAST_MESSAGE',
				'name' => Loc::getMessage('OL_STATS_HEADER_DATE_LAST_MESSAGE'),
				'default' => true,
				'editable' => false,
			],
			[
				'id' => 'DATE_OPERATOR_CLOSE',
				'name' => Loc::getMessage('OL_STATS_HEADER_DATE_OPERATOR_CLOSE_NEW'),
				'default' => true,
				'editable' => false,
			],
			[
				'id' => 'DATE_CLOSE',
				'name' => Loc::getMessage('OL_STATS_HEADER_DATE_CLOSE'),
				'default' => false,
				'editable' => false,
				'sort' => 'DATE_CLOSE',
			],
			[
				'id' => 'DATE_MODIFY',
				'name' => Loc::getMessage('OL_STATS_HEADER_DATE_MODIFY'),
				'default' => false,
				'editable' => false,
				'sort' => 'DATE_MODIFY',
			],
			[
				'id' => 'TIME_FIRST_ANSWER',
				'name' => Loc::getMessage('OL_STATS_HEADER_TIME_FIRST_ANSWER_NEW'),
				'default' => true,
				'editable' => false,
			],
			[
				'id' => 'TIME_ANSWER_WO_BOT',
				'name' => Loc::getMessage('OL_STATS_HEADER_TIME_ANSWER_WO_BOT_NEW'),
				'default' => false,
				'editable' => false,
			],
			[
				'id' => 'TIME_CLOSE_WO_BOT',
				'name' => Loc::getMessage('OL_STATS_HEADER_TIME_CLOSE_WO_BOT_1_NEW'),
				'default' => false,
				'editable' => false,
			],
			/*
			[
			'id' => 'TIME_ANSWER',
				'name' => Loc::getMessage('OL_STATS_HEADER_TIME_ANSWER_NEW'),
				'default' => false,
				'editable' => false
			],
			[
			'id' => 'TIME_CLOSE',
				'name' => Loc::getMessage('OL_STATS_HEADER_TIME_CLOSE_1_NEW'),
				'default' => false,
				'editable' => false
			],
			*/
			[
				'id' => 'TIME_DIALOG_WO_BOT',
				'name' => Loc::getMessage('OL_STATS_HEADER_TIME_DIALOG_WO_BOT_1'),
				'default' => true,
				'editable' => false
			],
			/*
			[
			'id' => 'TIME_DIALOG',
				'name' => Loc::getMessage('OL_STATS_HEADER_TIME_DIALOG_1'),
				'default' => false,
				'editable' => false
			],
			*/
			[
				'id' => 'TIME_BOT',
				'name' => Loc::getMessage('OL_STATS_HEADER_TIME_BOT'),
				'default' => true,
				'editable' => false,
			],
			[
				'id' => 'TIME_MESSAGE_ANSWER_FIRST',
				'name' => Loc::getMessage('OL_STATS_HEADER_TIME_MESSAGE_ANSWER_FIRST'),
				'default' => true,
				'editable' => false,
			],
			[
				'id' => 'TIME_MESSAGE_ANSWER_FULL',
				'name' => Loc::getMessage('OL_STATS_HEADER_TIME_MESSAGE_ANSWER_FULL'),
				'default' => true,
				'editable' => false,
			],
			[
				'id' => 'TIME_MESSAGE_ANSWER_AVERAGE',
				'name' => Loc::getMessage('OL_STATS_HEADER_TIME_MESSAGE_ANSWER_AVERAGE'),
				'default' => true,
				'editable' => false,
				],
			[
				'id' => 'TIME_MESSAGE_ANSWER_MAX',
				'name' => Loc::getMessage('OL_STATS_HEADER_TIME_MESSAGE_ANSWER_MAX'),
				'default' => true,
				'editable' => false,
			],
			[
				'id' => 'VOTE',
				'name' => Loc::getMessage('OL_STATS_HEADER_VOTE_CLIENT'),
				'default' => true,
				'editable' => false,
				'sort' => 'VOTE',
			],
			[
				'id' => 'COMMENT_HEAD',
				'name' => Loc::getMessage('OL_STATS_HEADER_COMMENT_HEAD'),
				'default' => true,
				'editable' => false,
			],
			[
				'id' => 'VOTE_HEAD',
				'name' => Loc::getMessage('OL_STATS_HEADER_VOTE_HEAD_1'),
				'default' => true,
				'editable' => false,
				'sort' => 'VOTE_HEAD',
			],
		]);

		//UF ->
		foreach ($this->getUfFieldList() as $fieldName => $field)
		{
			if (!empty($field["LIST_COLUMN_LABEL"]))
			{
				$name = $field["LIST_COLUMN_LABEL"];
			}
			else if (!empty($field["EDIT_FORM_LABEL"]))
			{
				$name = $field["EDIT_FORM_LABEL"];
			}
			else
			{
				$name = $fieldName;
			}

			$result[$fieldName] = [
				'id' => $fieldName,
				'name' => $name,
				'default' => false,
				'editable' => false,
			];

			if (
				$field['MULTIPLE'] != 'Y'
				&& $field['USER_TYPE']['BASE_TYPE'] != \CUserTypeManager::BASE_TYPE_FILE
			)
			{
				$result[$fieldName]['sort'] = $fieldName;
			}
		}
		//<- UF

		return $result;
	}

	/**
	 * Runs component.
	 * @return array|bool
	 */
	public function executeComponent()
	{
		$this->includeComponentLang('class.php');

		if (!$this->checkModules())
		{
			return false;
		}

		if (!$this->checkAccess())
		{
			return false;
		}

		$this->init();

		$this->arResult["GRID_ID"] = $this->gridId;
		$this->arResult["FILTER_ID"] = $this->filterId;
		$this->arResult["FILTER"] = $this->getFilterDefinition();
		$this->arResult['GROUP_ACTIONS'] = $this->prepareGroupActions();
		$this->arResult["HEADERS"] = $this->prepareHeaders();

		$sorting = $this->gridOptions->getSorting(array("sort" => array("ID" => "DESC")));
		$navParams = $this->gridOptions->getNavParams();

		if ($this->excelMode)
		{
			$pageSize = $this->arParams['STEXPORT_PAGE_SIZE'];
			$pageNum = $this->arParams['PAGE_NUMBER'];
			$this->arResult['STEXPORT_IS_FIRST_PAGE'] = $pageNum === 1;
			$offset = $pageSize * ($pageNum - 1);

			/*
			$total = $this->arParams['STEXPORT_TOTAL_ITEMS'];
			$processed = ($pageNum - 1) * $pageSize;
			if (($total > 0) && ($total - $processed <= $pageSize))
			{
				$pageSize = $total - $processed;
			}
			unset($total, $processed);
			*/
		}
		else
		{
			$pageSize = $navParams['nPageSize'];
		}

		$gridHeaders = $this->gridOptions->getVisibleColumns();
		if (empty($gridHeaders))
		{
			$gridHeaders = SessionTable::getSelectFieldsPerformance();
			$this->isNeedKpi = true;
		}
		$selectHeaders = array_intersect(SessionTable::getSelectFieldsPerformance(), $gridHeaders);

		$requiredHeaders = ['ID', 'USER_CODE', 'CLOSED', 'CHAT_ID'];
		if (Loader::includeModule('im'))
		{
			$requiredHeaders['CHAT_OPERATOR_ID'] = 'CHAT.AUTHOR_ID';
		}
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
					$this->isNeedKpi = true;
					break;
			}
		}

		$nav = new \Bitrix\Main\UI\PageNavigation("page");
		$nav->allowAllRecords(false)
			->setPageSize($pageSize)
			->initFromUri();

		if ($this->excelMode)
		{
			$nav->setCurrentPage($pageNum);
		}

		$cursor = SessionTable::getList(array(
			'order' => $sorting["sort"],
			'filter' => $this->getFilter(),
			'select' => $selectHeaders,
			'count_total' => true,
			'limit' => ($this->excelMode ? $pageSize : $nav->getLimit()),
			'offset' => ($this->excelMode ? $offset : $nav->getOffset())
		));

		$this->arResult["ROWS_COUNT"] = $cursor->getCount();
		$nav->setRecordCount($cursor->getCount());

		$this->arResult["SORT"] = $sorting["sort"];
		$this->arResult["SORT_VARS"] = $sorting["vars"];
		$this->arResult["NAV_OBJECT"] = $nav;

		$this->enableNextPage = $nav->getCurrentPage() < $nav->getPageCount();

		$userId = array();
		$this->arResult["ELEMENTS_ROWS"] = [];
		while ($data = $cursor->fetch())
		{
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

		$configManager = new Config();

		$arUsers = $this->getUserData($userId);
		$arSources = \Bitrix\ImConnector\Connector::getListConnector();
		foreach($this->arResult["ELEMENTS_ROWS"] as $key => $row)
		{
			$newRow = $this->arResult["ELEMENTS_ROWS"][$key]["columns"];

			if ($this->ufFields)
			{
				$userFields = $this->getUfTypeManager()->getUserFields(SessionTable::getUfId(), $row["data"]['ID'], LANGUAGE_ID);

				foreach ($userFields as $ufResult)
				{
					if (isset($row["data"][$ufResult["FIELD_NAME"]]))
					{
						$field = new \Bitrix\Main\UserField\Renderer(
							$ufResult,
							[
								'mode' => \Bitrix\Main\UserField\Types\BaseType::MODE_VIEW
							]
						);
						$newRow[$ufResult["FIELD_NAME"]] = $field->render();
					}
				}
			}

			$newRow["CONFIG_ID"] = $this->arResult['LINES'][$row["data"]["CONFIG_ID"]];

			$newRow["USER_NAME"] = $this->getUserHtml($row["data"]["USER_ID"], $arUsers);
			$newRow["OPERATOR_NAME"] = $this->getUserHtml($row["data"]["OPERATOR_ID"], $arUsers);
			$newRow["MODE_NAME"] = $row["data"]["MODE"] == 'input'? Loc::getMessage('OL_COMPONENT_TABLE_INPUT'): Loc::getMessage('OL_COMPONENT_TABLE_OUTPUT');

			$newRow["SOURCE_TEXT"] = $arSources[mb_strtolower($row["data"]["SOURCE"])];

			$newRow['STATUS'] = $this->formatStatus($row['data']['STATUS']);
			$newRow['STATUS_DETAIL'] = $this->formatStatusDetail($row['data']['STATUS']);

			if (!$this->isNotCloseSession($row['data']['STATUS'], $row['data']['CLOSED']))
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

			if ($this->isNeedKpi)
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

					if($this->isNotCloseSession($row['data']['STATUS'], $row['data']['CLOSED']))
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

		if ($this->excelMode)
		{
			$this->arResult['STEXPORT_IS_LAST_PAGE'] = !$this->enableNextPage;

			// We should use only selected grid columns for export
			if (!empty($this->gridOptions->getVisibleColumns()))
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
				\CPullWatch::Add($this->getCurrentUser()->getId(), 'IMOL_STATISTICS');
			}

			if ($this->isFdcMode())
			{
				$this->arResult['FILTER_URL'] = $this->getFilterUrl(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getRequestUri());
				array_unshift(
					$this->arResult["ELEMENTS_ROWS"],
					array(
						'editable' => false,
						'not_count' => true,
						'custom' => '<a id="ol-stat-filter-list-url" href="'.htmlspecialcharsbx($this->arResult['FILTER_URL']).'" style="display:none;"></a>'
					)
				);
			}

			$this->includeComponentTemplate();
			return $this->arResult;
		}
	}
}
