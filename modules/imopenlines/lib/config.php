<?php
namespace Bitrix\ImOpenLines;

use \Bitrix\Main,
	\Bitrix\Main\Loader,
	Bitrix\Main\Text\Emoji,
	\Bitrix\Main\ModuleManager,
	\Bitrix\Main\Localization\Loc;

use \Bitrix\Bitrix24\Feature;

use \Bitrix\Imopenlines\Limit,
	\Bitrix\ImOpenLines\Model\ConfigTable,
	\Bitrix\ImOpenLines\Model\ConfigQueueTable,
	\Bitrix\ImOpenlines\QuickAnswers\ListsDataManager,
	\Bitrix\ImOpenLines\Model\SessionAutomaticTasksTable,
	\Bitrix\Imopenlines\Model\ConfigAutomaticMessagesTable;

Loc::loadMessages(__FILE__);

class Config
{
	const MODE_ADD = 'add';
	const MODE_UPDATE = 'update';

	const CRM_SOURCE_AUTO_CREATE = 'create';

	const CRM_CREATE_NONE = 'none';
	const CRM_CREATE_LEAD = 'lead';
	const CRM_CREATE_DEAL = 'deal';

	const TYPE_MAX_CHAT_ANSWERED = 'answered';
	const TYPE_MAX_CHAT_ANSWERED_NEW = 'answered_new';
	const TYPE_MAX_CHAT_CLOSED = 'closed';

	const QUEUE_TYPE_EVENLY = 'evenly';
	const QUEUE_TYPE_STRICTLY = 'strictly';
	const QUEUE_TYPE_ALL = 'all';
	/** @deprecated */
	const RULE_FORM = 'form';
	/** @deprecated */
	const RULE_QUEUE = 'queue';
	const RULE_QUALITY = 'text';
	const RULE_TEXT = 'text';
	const RULE_NONE = 'none';

	const BOT_JOIN_FIRST = 'first';
	const BOT_JOIN_ALWAYS = 'always';

	const BOT_LEFT_QUEUE = 'queue';
	const BOT_LEFT_CLOSE = 'close';

	const OPERATOR_DATA_PROFILE = 'profile';
	const OPERATOR_DATA_QUEUE = 'queue';
	const OPERATOR_DATA_HIDE = 'hide';

	const EVENT_IMOPENLINE_CREATE = 'OnImopenlineCreate';
	const EVENT_IMOPENLINE_DELETE = 'OnImopenlineDelete';
	const EVENT_IMOPENLINE_CHANGE_QUEUE_TYPE = 'OnImopenlineChangeQueueType';
	const EVENT_AFTER_IMOPENLINE_ACTIVE_CHANGE = 'OnAfterImopenlineActiveChange';

	const CONFIG_CACHE_TIME = 86400;

	private $error = null;

	static $cacheOperation = [];
	static $cachePermission = [];

	public function __construct()
	{
		$this->error = new BasicError(null, '', '');
	}

	private function prepareFields($params, $mode = self::MODE_ADD)
	{
		$companyName = \Bitrix\Main\Config\Option::get("main", "site_name", "");

		$fields = [];
		if (isset($params['LINE_NAME']) && !empty($params['LINE_NAME']))
		{
			$fields['LINE_NAME'] = $params['LINE_NAME'];
		}
		else if ($mode == self::MODE_ADD)
		{
			$configCount = Model\ConfigTable::getList(array(
				'select' => array('CNT'),
				'runtime' => array(new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)'))
			))->fetch();
			if ($configCount['CNT'] == 0)
			{
				$fields['LINE_NAME'] = Loc::getMessage('IMOL_CONFIG_LINE_NAME', Array('#NAME#' => $companyName));
			}
			if (empty($fields['LINE_NAME']))
			{
				$fakeLineNumber = \CGlobalCounter::GetValue('imol_line_number', \CGlobalCounter::ALL_SITES);
				$fields['LINE_NAME'] = Loc::getMessage('IMOL_CONFIG_LINE_NAME', Array('#NAME#' => $fakeLineNumber+1));
			}
		}

		if (\IsModuleInstalled('crm'))
		{
			if (isset($params['CRM']))
			{
				$fields['CRM'] = $params['CRM'] == 'N'? 'N': 'Y';
			}
			else if ($mode == self::MODE_ADD)
			{
				$fields['CRM'] = 'Y';
			}
		}
		else
		{
			$fields['CRM'] = 'N';
		}

		if (isset($params['CRM_CREATE']))
		{
			$fields['CRM_CREATE'] = in_array($params['CRM_CREATE'], [self::CRM_CREATE_NONE, self::CRM_CREATE_LEAD, self::CRM_CREATE_DEAL])? $params['CRM_CREATE']: self::CRM_CREATE_LEAD;
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['CRM_CREATE'] = self::CRM_CREATE_LEAD;
		}

		if (isset($params['CRM_CREATE_SECOND']))
		{
			$fields['CRM_CREATE_SECOND'] = $params['CRM_CREATE_SECOND'];
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['CRM_CREATE_SECOND'] = '';
		}

		if (isset($params['CRM_CREATE_THIRD']))
		{
			$fields['CRM_CREATE_THIRD'] = $params['CRM_CREATE_THIRD'] === 'N'? 'N': 'Y';
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['CRM_CREATE_THIRD'] = 'Y';
		}

		if (isset($params['CRM_FORWARD']))
		{
			$fields['CRM_FORWARD'] = $params['CRM_FORWARD'] == 'N'? 'N': 'Y';
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['CRM_FORWARD'] = 'Y';
		}

		if (isset($params['CRM_CHAT_TRACKER']))
		{
			$fields['CRM_CHAT_TRACKER'] = $params['CRM_CHAT_TRACKER'] == 'N'? 'N': 'Y';
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['CRM_CHAT_TRACKER'] = 'Y';
		}

		if (isset($params['CRM_TRANSFER_CHANGE']))
		{
			$fields['CRM_TRANSFER_CHANGE'] = $params['CRM_TRANSFER_CHANGE'] == 'N'? 'N': 'Y';
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['CRM_TRANSFER_CHANGE'] = 'Y';
		}

		if (isset($params['CRM_SOURCE']))
		{
			$fields['CRM_SOURCE'] = $params['CRM_SOURCE'];
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['CRM_SOURCE'] = self::CRM_SOURCE_AUTO_CREATE;
		}

		if (isset($params['QUEUE_TIME']))
		{
			$fields['QUEUE_TIME'] = intval($params['QUEUE_TIME']);
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['QUEUE_TIME'] = 60;
		}

//		if (isset($params['CRM_FORM_TO_USE']))
//		{
//			$fields['CRM_FORM_TO_USE'] = intval($params['CRM_FORM_TO_USE']);
//		}
//		else if ($mode == self::MODE_ADD)
//		{
//			$fields['CRM_FORM_TO_USE'] = 0;
//		}

		if (isset($params['NO_ANSWER_TIME']))
		{
			$fields['NO_ANSWER_TIME'] = intval($params['NO_ANSWER_TIME']);
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['NO_ANSWER_TIME'] = 60;
		}

		if (isset($params['MAX_CHAT']))
		{
			if($fields['MAX_CHAT'] >= 0)
				$fields['MAX_CHAT'] = intval($params['MAX_CHAT']);
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['MAX_CHAT'] = 0;
		}

		if (isset($params['TYPE_MAX_CHAT']))
		{
			$fields['TYPE_MAX_CHAT'] = in_array($params['TYPE_MAX_CHAT'], Array(self::TYPE_MAX_CHAT_ANSWERED_NEW, self::TYPE_MAX_CHAT_ANSWERED, self::TYPE_MAX_CHAT_CLOSED))? $params['TYPE_MAX_CHAT']: self::TYPE_MAX_CHAT_ANSWERED_NEW;
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['TYPE_MAX_CHAT'] = self::TYPE_MAX_CHAT_ANSWERED;
		}

		if (isset($params['CATEGORY_ENABLE']))
		{
			$fields['CATEGORY_ENABLE'] = $params['CATEGORY_ENABLE'] == 'Y'? 'Y': 'N';
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields["CATEGORY_ENABLE"] = 'N';
		}

		if (isset($params['CATEGORY_ID']))
		{
			$fields['CATEGORY_ID'] = $fields['CATEGORY_ENABLE'] == 'Y'? intval($params['CATEGORY_ID']): 0;
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields["CATEGORY_ID"] = 0;
		}

		if (isset($params['SESSION_PRIORITY']))
		{
			$params['SESSION_PRIORITY'] = intval($params['SESSION_PRIORITY']);
			$fields['SESSION_PRIORITY'] = $params['SESSION_PRIORITY'] >= 0 && $params['SESSION_PRIORITY'] <= 86400? $params['SESSION_PRIORITY']: ($params['SESSION_PRIORITY'] > 0? 86400: 0);
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields["SESSION_PRIORITY"] = 0;
		}

		if (isset($params['LANGUAGE_ID']))
		{
			$fields['LANGUAGE_ID'] = mb_substr($params['LANGUAGE_ID'], 0, 2);
		}

		if (isset($params['AGREEMENT_MESSAGE']))
		{
			$fields['AGREEMENT_MESSAGE'] = $params['AGREEMENT_MESSAGE'] == 'Y'? 'Y': 'N';
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields["AGREEMENT_MESSAGE"] = 'N';
		}

		if (isset($params['AGREEMENT_ID']))
		{
			$fields['AGREEMENT_ID'] = intval($params['AGREEMENT_ID']);
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields["AGREEMENT_ID"] = 0;
		}

		if (isset($params['WELCOME_BOT_ENABLE']))
		{
			$fields['WELCOME_BOT_ENABLE'] = $params['WELCOME_BOT_ENABLE'] == 'Y'? 'Y': 'N';
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields["WELCOME_BOT_ENABLE"] = 'N';
		}

		if (isset($params['WELCOME_BOT_JOIN']))
		{
			$fields['WELCOME_BOT_JOIN'] = $params['WELCOME_BOT_JOIN'] == self::BOT_JOIN_FIRST? self::BOT_JOIN_FIRST: self::BOT_JOIN_ALWAYS;
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['WELCOME_BOT_JOIN'] = self::BOT_JOIN_ALWAYS;
		}

		if (isset($params['WELCOME_BOT_ID']))
		{
			$fields['WELCOME_BOT_ID'] = $fields["WELCOME_BOT_ENABLE"] == 'Y'? intval($params['WELCOME_BOT_ID']): 0;
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['WELCOME_BOT_ID'] = 0;
		}

		if (isset($params['WELCOME_BOT_TIME']))
		{
			$fields['WELCOME_BOT_TIME'] = $fields["WELCOME_BOT_ENABLE"] == 'Y'? intval($params['WELCOME_BOT_TIME']): 600;
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['WELCOME_BOT_TIME'] = 600;
		}

		if (isset($params['WELCOME_BOT_LEFT']))
		{
			$fields['WELCOME_BOT_LEFT'] = $fields["WELCOME_BOT_ENABLE"] == 'Y' && $params['WELCOME_BOT_LEFT'] == self::BOT_LEFT_CLOSE? self::BOT_LEFT_CLOSE: self::BOT_LEFT_QUEUE;
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['WELCOME_BOT_LEFT'] = self::BOT_LEFT_QUEUE;
		}

		if (isset($params['QUEUE_TYPE']))
		{
			$fields['QUEUE_TYPE'] = in_array($params['QUEUE_TYPE'], Array(self::QUEUE_TYPE_STRICTLY, self::QUEUE_TYPE_ALL, self::QUEUE_TYPE_EVENLY))? $params['QUEUE_TYPE']: self::QUEUE_TYPE_ALL;
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['QUEUE_TYPE'] = self::QUEUE_TYPE_ALL;
		}
		if ($fields['QUEUE_TYPE'] == self::QUEUE_TYPE_ALL && !\Bitrix\Imopenlines\Limit::canUseQueueAll())
		{
			$fields['QUEUE_TYPE'] = self::QUEUE_TYPE_EVENLY;
		}

		if (isset($params['CHECK_AVAILABLE']))
		{
			$fields['CHECK_AVAILABLE'] = $params['CHECK_AVAILABLE'] == 'N'? 'N': 'Y';
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields["CHECK_AVAILABLE"] = 'N';
		}

		if (isset($params['WATCH_TYPING']))
		{
			$fields['WATCH_TYPING'] = $params['WATCH_TYPING'] == 'Y'? 'Y': 'N';
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields["WATCH_TYPING"] = 'Y';
		}

		if (isset($params['WELCOME_MESSAGE']))
		{
			$fields['WELCOME_MESSAGE'] = $params['WELCOME_MESSAGE'] == 'N'? 'N': 'Y';
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['WELCOME_MESSAGE'] = 'Y';
		}

		if (isset($params['WELCOME_MESSAGE_TEXT']))
		{
			$fields['WELCOME_MESSAGE_TEXT'] = Emoji::encode($params['WELCOME_MESSAGE_TEXT']);
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['WELCOME_MESSAGE_TEXT'] = Loc::getMessage('IMOL_CONFIG_WELCOME_MESSAGE', Array('#COMPANY_NAME#' => $companyName));
		}

		if (isset($params['OPERATOR_DATA']))
		{
			$fields['OPERATOR_DATA'] = in_array($params['OPERATOR_DATA'], Array(self::OPERATOR_DATA_PROFILE, self::OPERATOR_DATA_QUEUE, self::OPERATOR_DATA_HIDE))? $params['OPERATOR_DATA']: self::OPERATOR_DATA_PROFILE;
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['OPERATOR_DATA'] = self::OPERATOR_DATA_PROFILE;
		}

		if (isset($params['DEFAULT_OPERATOR_DATA']))
		{
			$fields['DEFAULT_OPERATOR_DATA'] = $params['DEFAULT_OPERATOR_DATA'];
		}

		$defaultAuthFormId = $this->getFormForAuth();
		$defaultRatingFormId = $this->getFormForRating();
		$formValues = $this->getFormValues();

		if (isset($params['NO_ANSWER_RULE']))
		{
			$fields['NO_ANSWER_RULE'] = in_array($params["NO_ANSWER_RULE"], Array(self::RULE_TEXT, self::RULE_NONE))? $params["NO_ANSWER_RULE"]: self::RULE_NONE;
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['NO_ANSWER_RULE'] = self::RULE_NONE;
		}

		if (isset($params['NO_ANSWER_FORM_ID']))
		{
			$fields['NO_ANSWER_FORM_ID'] = isset($formValues[$params['NO_ANSWER_FORM_ID']])? $params['NO_ANSWER_FORM_ID']: $defaultAuthFormId;
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['NO_ANSWER_FORM_ID'] = $defaultAuthFormId;
		}

		if (isset($params['NO_ANSWER_BOT_ID']))
		{
			$fields['NO_ANSWER_BOT_ID'] = intval($params['NO_ANSWER_BOT_ID']);
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['NO_ANSWER_BOT_ID'] = 0;
		}

		if (isset($params['NO_ANSWER_TEXT']))
		{
			$fields['NO_ANSWER_TEXT'] = $params['NO_ANSWER_TEXT'];
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['NO_ANSWER_TEXT'] = Loc::getMessage('IMOL_CONFIG_NO_ANSWER_NEW', Array('#COMPANY_NAME#' => $companyName));
		}

		if (isset($params['WORKTIME_ENABLE']))
		{
			$fields['WORKTIME_ENABLE'] = $params['WORKTIME_ENABLE'] == 'Y'? 'Y': 'N';
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields["WORKTIME_ENABLE"] = 'N';
		}
		if ($fields['WORKTIME_ENABLE'] == 'Y' && !\Bitrix\Imopenlines\Limit::canWorkHourSettings())
		{
			$fields['WORKTIME_ENABLE'] = 'N';
		}

		if (isset($params['WORKTIME_TIMEZONE']))
		{
			$fields['WORKTIME_TIMEZONE'] = $params['WORKTIME_TIMEZONE'];
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields["WORKTIME_TIMEZONE"] = '';
		}

		if (isset($params["WORKTIME_DAYOFF"]) && is_array($params["WORKTIME_DAYOFF"]))
		{
			$arAvailableValues = array('MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU');
			foreach($params["WORKTIME_DAYOFF"] as $key => $value)
			{
				if (!in_array($value, $arAvailableValues))
				{
					unset($params["WORKTIME_DAYOFF"][$key]);
				}
			}
			$fields['WORKTIME_DAYOFF'] = implode(",", $params["WORKTIME_DAYOFF"]);
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['WORKTIME_DAYOFF'] = '';
		}

		if (isset($params["WORKTIME_FROM"]) && isset($params["WORKTIME_TO"]))
		{
			preg_match("/^\d{1,2}(\.\d{1,2})?$/i", $params["WORKTIME_FROM"], $matchesFrom);
			preg_match("/^\d{1,2}(\.\d{1,2})?$/i", $params["WORKTIME_TO"], $matchesTo);

			if (isset($matchesFrom[0]) && isset($matchesTo[0]))
			{
				$fields['WORKTIME_FROM'] = $params['WORKTIME_FROM'];
				$fields['WORKTIME_TO'] = $params['WORKTIME_TO'];

				if($fields['WORKTIME_FROM'] > 23.30)
				{
					$fields['WORKTIME_FROM'] = 23.30;
				}
				if ($fields['WORKTIME_TO'] <= $fields['WORKTIME_FROM'])
				{
					$fields['WORKTIME_TO'] = $fields['WORKTIME_FROM'] < 23.30 ? $fields['WORKTIME_FROM'] + 1 : 23.59;
				}
			}
			else
			{
				$fields['WORKTIME_FROM'] = "9";
				$fields['WORKTIME_TO'] = "18.30";
			}
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['WORKTIME_FROM'] = "9";
			$fields['WORKTIME_TO'] = "18.30";
		}

		if (isset($params["WORKTIME_HOLIDAYS"]))
		{
			$params["WORKTIME_HOLIDAYS"] = str_replace(' ', '', $params["WORKTIME_HOLIDAYS"]);
			$params["WORKTIME_HOLIDAYS"] = implode(',', $params["WORKTIME_HOLIDAYS"]);
			preg_match("/^(\d{1,2}\.\d{1,2},?)+$/i", $params["WORKTIME_HOLIDAYS"], $matches);
			$fields['WORKTIME_HOLIDAYS'] = isset($matches[0])? $params["WORKTIME_HOLIDAYS"]: "";
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['WORKTIME_HOLIDAYS'] = "";
		}

		if (isset($params['WORKTIME_DAYOFF_RULE']))
		{
			$fields['WORKTIME_DAYOFF_RULE'] = in_array($params["WORKTIME_DAYOFF_RULE"], Array( self::RULE_TEXT, self::RULE_NONE))? $params["WORKTIME_DAYOFF_RULE"]: self::RULE_NONE;
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['WORKTIME_DAYOFF_RULE'] = self::RULE_NONE;
		}

		if (isset($params['WORKTIME_DAYOFF_FORM_ID']))
		{
			$fields['WORKTIME_DAYOFF_FORM_ID'] = isset($formValues[$params['WORKTIME_DAYOFF_FORM_ID']])? $params['WORKTIME_DAYOFF_FORM_ID']: $defaultAuthFormId;
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['WORKTIME_DAYOFF_FORM_ID'] = $defaultAuthFormId;
		}

		if (isset($params['WORKTIME_DAYOFF_BOT_ID']))
		{
			$fields['WORKTIME_DAYOFF_BOT_ID'] = intval($params['WORKTIME_DAYOFF_BOT_ID']);
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['WORKTIME_DAYOFF_BOT_ID'] = 0;
		}

		if (isset($params['WORKTIME_DAYOFF_TEXT']))
		{
			$fields['WORKTIME_DAYOFF_TEXT'] = Emoji::encode($params['WORKTIME_DAYOFF_TEXT']);
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['WORKTIME_DAYOFF_TEXT'] = Loc::getMessage('IMOL_CONFIG_WORKTIME_DAYOFF_3', Array('#COMPANY_NAME#' => $companyName));
		}

		if (isset($params['CLOSE_RULE']))
		{
			$fields['CLOSE_RULE'] = in_array($params["CLOSE_RULE"], Array(self::RULE_TEXT, self::RULE_QUALITY, self::RULE_NONE))? $params["CLOSE_RULE"]: self::RULE_NONE;
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['CLOSE_RULE'] = self::RULE_QUALITY;
		}

		if (isset($params['CLOSE_FORM_ID']))
		{
			$fields['CLOSE_FORM_ID'] = isset($formValues[$params['CLOSE_FORM_ID']])? $params['CLOSE_FORM_ID']: $defaultRatingFormId;
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['CLOSE_FORM_ID'] = $defaultRatingFormId;
		}

		if (isset($params['CLOSE_BOT_ID']))
		{
			$fields['CLOSE_BOT_ID'] = intval($params['CLOSE_BOT_ID']);
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['CLOSE_BOT_ID'] = 0;
		}

		if (isset($params['CLOSE_TEXT']))
		{
			$fields['CLOSE_TEXT'] = Emoji::encode($params['CLOSE_TEXT']);
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['CLOSE_TEXT'] = Loc::getMessage('IMOL_CONFIG_CLOSE_TEXT_2');
		}

		if (isset($params['VOTE_MESSAGE']))
		{
			$fields['VOTE_MESSAGE'] = $params['VOTE_MESSAGE'] == 'N'? 'N': 'Y';
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['VOTE_MESSAGE'] = 'Y';
		}
		if ($fields['VOTE_MESSAGE'] == 'Y' && !\Bitrix\Imopenlines\Limit::canUseVoteClient())
		{
			$fields['VOTE_MESSAGE'] = 'N';
		}
		if (isset($params['VOTE_TIME_LIMIT']))
		{
			$fields['VOTE_TIME_LIMIT'] = (int)$params['VOTE_TIME_LIMIT'] > 0 ? (int)$params['VOTE_TIME_LIMIT']: 0;
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['VOTE_TIME_LIMIT'] = 0;
		}

		if (isset($params['VOTE_CLOSING_DELAY']))
		{
			$fields['VOTE_CLOSING_DELAY'] = $params['VOTE_CLOSING_DELAY'] == 'Y'? 'Y': 'N';
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields["VOTE_CLOSING_DELAY"] = 'N';
		}

		if (isset($params['VOTE_BEFORE_FINISH']))
		{
			$fields['VOTE_BEFORE_FINISH'] = $params['VOTE_BEFORE_FINISH'] == 'Y'? 'Y': 'N';
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields["VOTE_BEFORE_FINISH"] = 'Y';
		}

		if (isset($params['VOTE_MESSAGE_1_TEXT']))
		{
			$params['VOTE_MESSAGE_1_TEXT'] = Emoji::encode($params['VOTE_MESSAGE_1_TEXT']);
			$fields['VOTE_MESSAGE_1_TEXT'] = mb_substr($params['VOTE_MESSAGE_1_TEXT'], 0, 100);
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['VOTE_MESSAGE_1_TEXT'] = Loc::getMessage('IMOL_CONFIG_VOTE_MESSAGE_1_TEXT');
		}
		if (isset($params['VOTE_MESSAGE_1_LIKE']))
		{
			$params['VOTE_MESSAGE_1_LIKE'] = Emoji::encode($params['VOTE_MESSAGE_1_LIKE']);
			$fields['VOTE_MESSAGE_1_LIKE'] = mb_substr($params['VOTE_MESSAGE_1_LIKE'], 0, 100);
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['VOTE_MESSAGE_1_LIKE'] = Loc::getMessage('IMOL_CONFIG_VOTE_MESSAGE_1_LIKE');
		}
		if (isset($params['VOTE_MESSAGE_1_DISLIKE']))
		{
			$params['VOTE_MESSAGE_1_DISLIKE'] = Emoji::encode($params['VOTE_MESSAGE_1_DISLIKE']);
			$fields['VOTE_MESSAGE_1_DISLIKE'] = mb_substr($params['VOTE_MESSAGE_1_DISLIKE'], 0, 100);
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['VOTE_MESSAGE_1_DISLIKE'] = Loc::getMessage('IMOL_CONFIG_VOTE_MESSAGE_1_DISLIKE');
		}
		if (isset($params['VOTE_MESSAGE_2_TEXT']))
		{
			$params['VOTE_MESSAGE_2_TEXT'] = Emoji::encode($params['VOTE_MESSAGE_2_TEXT']);
			$fields['VOTE_MESSAGE_2_TEXT'] = $params['VOTE_MESSAGE_2_TEXT'];
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['VOTE_MESSAGE_2_TEXT'] = Loc::getMessage('IMOL_CONFIG_VOTE_MESSAGE_2_TEXT');
		}
		if (isset($params['VOTE_MESSAGE_2_LIKE']))
		{
			$params['VOTE_MESSAGE_2_LIKE'] = Emoji::encode($params['VOTE_MESSAGE_2_LIKE']);
			$fields['VOTE_MESSAGE_2_LIKE'] = $params['VOTE_MESSAGE_2_LIKE'];
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['VOTE_MESSAGE_2_LIKE'] = Loc::getMessage('IMOL_CONFIG_VOTE_MESSAGE_2_LIKE');
		}
		if (isset($params['VOTE_MESSAGE_2_DISLIKE']))
		{
			$params['VOTE_MESSAGE_2_DISLIKE'] = Emoji::encode($params['VOTE_MESSAGE_2_DISLIKE']);
			$fields['VOTE_MESSAGE_2_DISLIKE'] = $params['VOTE_MESSAGE_2_DISLIKE'];
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['VOTE_MESSAGE_2_DISLIKE'] = Loc::getMessage('IMOL_CONFIG_VOTE_MESSAGE_2_DISLIKE');
		}

		if (isset($params['AUTO_CLOSE_RULE']))
		{
			$fields['AUTO_CLOSE_RULE'] = in_array($params["AUTO_CLOSE_RULE"], Array(self::RULE_TEXT, self::RULE_NONE))? $params["AUTO_CLOSE_RULE"]: self::RULE_NONE;
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['AUTO_CLOSE_RULE'] = self::RULE_NONE;
		}

		if (isset($params['FULL_CLOSE_TIME']) && $params['FULL_CLOSE_TIME'] >=0)
		{
			$fields['FULL_CLOSE_TIME'] = intval($params['FULL_CLOSE_TIME']);
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['FULL_CLOSE_TIME'] = 10;
		}

		if (isset($params['AUTO_CLOSE_TIME']) && $params['AUTO_CLOSE_TIME'] >=0)
		{
			$fields['AUTO_CLOSE_TIME'] = intval($params['AUTO_CLOSE_TIME']);
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['AUTO_CLOSE_TIME'] = 14400;
		}

		if (isset($params['AUTO_CLOSE_FORM_ID']))
		{
			$fields['AUTO_CLOSE_FORM_ID'] = isset($formValues[$params['AUTO_CLOSE_FORM_ID']])? $params['AUTO_CLOSE_FORM_ID']: $defaultRatingFormId;
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['AUTO_CLOSE_FORM_ID'] = $defaultRatingFormId;
		}

		if (isset($params['AUTO_CLOSE_BOT_ID']))
		{
			$fields['AUTO_CLOSE_BOT_ID'] = intval($params['AUTO_CLOSE_BOT_ID']);
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['AUTO_CLOSE_BOT_ID'] = 0;
		}

		if (isset($params['AUTO_CLOSE_TEXT']))
		{
			$fields['AUTO_CLOSE_TEXT'] = $params['AUTO_CLOSE_TEXT'];
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['AUTO_CLOSE_TEXT'] = Loc::getMessage('IMOL_CONFIG_AUTO_CLOSE_TEXT');
		}

		if (isset($params['AUTO_EXPIRE_TIME']))
		{
			$fields['AUTO_EXPIRE_TIME'] = intval($params['AUTO_EXPIRE_TIME']);
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['AUTO_EXPIRE_TIME'] = 86400;
		}

		if (isset($params['TEMPORARY']))
		{
			$fields['TEMPORARY'] = $params['TEMPORARY'] == 'N'? 'N': 'Y';
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['TEMPORARY'] = 'N';
		}

		if (isset($params['ACTIVE']))
		{
			$fields['ACTIVE'] = $params['ACTIVE'] == 'N'? 'N': 'Y';
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['ACTIVE'] = 'Y';
		}

		if (isset($params['QUICK_ANSWERS_IBLOCK_ID']))
		{
			$fields['QUICK_ANSWERS_IBLOCK_ID'] = intval($params['QUICK_ANSWERS_IBLOCK_ID']);
		}

		if (isset($params['KPI_FIRST_ANSWER_TIME']))
		{
			$fields['KPI_FIRST_ANSWER_TIME'] = intval($params['KPI_FIRST_ANSWER_TIME']) > 0 ? intval($params['KPI_FIRST_ANSWER_TIME']) : 0;
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['KPI_FIRST_ANSWER_TIME'] = 0;
		}

		if (isset($params['KPI_FIRST_ANSWER_ALERT']))
		{
			$fields['KPI_FIRST_ANSWER_ALERT'] = $params['KPI_FIRST_ANSWER_ALERT'] == 'Y' ? 'Y' : 'N';
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['KPI_FIRST_ANSWER_ALERT'] = 'N';
		}

		if (isset($params['KPI_FIRST_ANSWER_LIST']))
		{
			$fields['KPI_FIRST_ANSWER_LIST'] = $params['KPI_FIRST_ANSWER_LIST'];
		}

		if (isset($params['KPI_FIRST_ANSWER_TEXT']))
		{
			$fields['KPI_FIRST_ANSWER_TEXT'] = htmlspecialcharsbx($params['KPI_FIRST_ANSWER_TEXT']);
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['KPI_FIRST_ANSWER_TEXT'] = Loc::getMessage('IMOL_CONFIG_KPI_FIRST_ANSWER_TEXT');
		}

		if (isset($params['KPI_FURTHER_ANSWER_TIME']))
		{
			$fields['KPI_FURTHER_ANSWER_TIME'] = intval($params['KPI_FURTHER_ANSWER_TIME']) > 0 ? intval($params['KPI_FURTHER_ANSWER_TIME']) : 0;
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['KPI_FURTHER_ANSWER_TIME'] = 0;
		}

		if (isset($params['KPI_FURTHER_ANSWER_ALERT']))
		{
			$fields['KPI_FURTHER_ANSWER_ALERT'] = $params['KPI_FURTHER_ANSWER_ALERT'] == 'Y' ? 'Y' : 'N';
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['KPI_FURTHER_ANSWER_ALERT'] = 'N';
		}

		if (isset($params['KPI_FURTHER_ANSWER_LIST']))
		{
			$fields['KPI_FURTHER_ANSWER_LIST'] = $params['KPI_FURTHER_ANSWER_LIST'];
		}

		if (isset($params['KPI_FURTHER_ANSWER_TEXT']))
		{
			$fields['KPI_FURTHER_ANSWER_TEXT'] = htmlspecialcharsbx($params['KPI_FURTHER_ANSWER_TEXT']);
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['KPI_FURTHER_ANSWER_TEXT'] = Loc::getMessage('IMOL_CONFIG_KPI_FURTHER_ANSWER_TEXT');
		}

		if (isset($params['KPI_CHECK_OPERATOR_ACTIVITY']))
		{
			$fields['KPI_CHECK_OPERATOR_ACTIVITY'] = $params['KPI_CHECK_OPERATOR_ACTIVITY'] == 'Y' ? 'Y' : 'N';
		}
		else if ($mode == self::MODE_ADD)
		{
			$fields['KPI_CHECK_OPERATOR_ACTIVITY'] = 'N';
		}

		return $fields;
	}

	/**
	 * @param array $params
	 * @return array|bool|int
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function create($params = [])
	{
		$fields = $this->prepareFields($params);

		global $USER;
		$userId = is_object($USER) && $USER->GetID()? $USER->GetID(): 0;
		if ($userId)
		{
			$fields['MODIFY_USER_ID'] = $userId;
		}

		$result = Model\ConfigTable::add($fields);
		if(!$result->isSuccess())
		{
			$this->error = new BasicError(__METHOD__, 'ADD_ERROR', Loc::getMessage('IMOL_ADD_ERROR'));
			return false;
		}
		$id = $result->getId();
		$data = $result->getData();


		ConfigStatistic::add($id);

		$queueManager = new QueueManager($id);
		if (isset($params['QUEUE']) && is_array($params['QUEUE']) && !empty($params['QUEUE']))
		{
			if(!isset($params['QUEUE_USERS_FIELDS']))
			{
				$params['QUEUE_USERS_FIELDS'] = false;
			}

			$queueManager->compatibleUpdate($params['QUEUE'], $params['QUEUE_USERS_FIELDS']);
		}
		else
		{
			$queueManager->update([]);
		}

		\CGlobalCounter::Increment('imol_line_number', \CGlobalCounter::ALL_SITES, false);
		if ($fields['TEMPORARY'] == 'Y')
		{
			$date = new \Bitrix\Main\Type\DateTime();
			$date->add('8 HOUR');
			\CAgent::AddAgent('\Bitrix\ImOpenLines\Config::deleteTemporaryConfigAgent('.$id.');', "imopenlines", "N", 28800, "", "Y", $date);
		}

		self::sendUpdateForQueueList(Array(
			'ID' => $id,
			'NAME' => $data['LINE_NAME'],
			'SESSION_PRIORITY' => $data['SESSION_PRIORITY'],
			'QUEUE_TYPE' => $data['QUEUE_TYPE'],
		));

		if($fields['QUICK_ANSWERS_IBLOCK_ID'] > 0)
		{
			ListsDataManager::updateIblockRights($fields['QUICK_ANSWERS_IBLOCK_ID']);
		}

		$eventData = array(
			'line' => $id
		);
		$event = new Main\Event('imopenlines', self::EVENT_IMOPENLINE_CREATE, $eventData);
		$event->send();

		return $id;
	}

	/**
	 * @param $id
	 * @param array $params
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function update($id, $params = [])
	{
		$fields = $this->prepareFields($params, self::MODE_UPDATE);

		$orm = Model\ConfigTable::getById($id);
		if (!($config = $orm->fetch()))
			return false;

		if (!isset($params['SKIP_MODIFY_MARK']))
		{
			$fields['DATE_MODIFY'] = new \Bitrix\Main\Type\DateTime();
			global $USER;
			$userId = is_object($USER) && $USER->GetID()? $USER->GetID(): 0;
			if ($userId)
			{
				$fields['MODIFY_USER_ID'] = $userId;
			}
		}

		$result = Model\ConfigTable::update($id, $fields);

		if(!$result->isSuccess())
		{
			$this->error = new BasicError(__METHOD__, 'UPDATE_ERROR', Loc::getMessage('IMOL_UPDATE_ERROR'));
			return false;
		}
		else
		{
			if ($config['ACTIVE'] !== $fields['ACTIVE'])
			{
				$eventData = [
					'line' => $id,
					'active' => $fields['ACTIVE']
				];
				$event = new Main\Event('imopenlines', self::EVENT_AFTER_IMOPENLINE_ACTIVE_CHANGE, $eventData);
				$event->send();
			}

			if ($config['QUEUE_TYPE'] !== $fields['QUEUE_TYPE'])
			{
				$eventData = array(
					'line' => $id,
					'typeBefore' => $config['QUEUE_TYPE'],
					'typeAfter' => $fields['QUEUE_TYPE']
				);
				$event = new Main\Event('imopenlines', self::EVENT_IMOPENLINE_CHANGE_QUEUE_TYPE, $eventData);
				$event->send();
			}
		}

		if(
			isset($params['DEFAULT_OPERATOR_DATA']) &&
			!empty($config['DEFAULT_OPERATOR_DATA']['AVATAR_ID']) &&
			(
				empty($params['DEFAULT_OPERATOR_DATA']['AVATAR_ID']) ||
				$config['DEFAULT_OPERATOR_DATA']['AVATAR_ID'] != $params['DEFAULT_OPERATOR_DATA']['AVATAR_ID']
			)
		)
		{
			\CFile::Delete($config['DEFAULT_OPERATOR_DATA']['AVATAR_ID']);
		}

		if (isset($params['QUEUE']) && is_array($params['QUEUE']))
		{
			$queueManager = new QueueManager($id);

			if(!isset($params['QUEUE_USERS_FIELDS']))
			{
				$params['QUEUE_USERS_FIELDS'] = false;
			}
			$queueManager->compatibleUpdate($params['QUEUE'], $params['QUEUE_USERS_FIELDS']);
		}

		if($config['QUICK_ANSWERS_IBLOCK_ID'] != $fields['QUICK_ANSWERS_IBLOCK_ID'] && $config['QUICK_ANSWERS_IBLOCK_ID'] > 0)
		{
			ListsDataManager::updateIblockRights($config['QUICK_ANSWERS_IBLOCK_ID']);
		}

		if($fields['QUICK_ANSWERS_IBLOCK_ID'] > 0)
		{
			ListsDataManager::updateIblockRights($fields['QUICK_ANSWERS_IBLOCK_ID']);
		}

		$sendUpdate = false;
		$sendDelete = false;
		$lineName = $config['LINE_NAME'];
		$queueType = $config['QUEUE_TYPE'];

		if (isset($fields['ACTIVE']) && $config['ACTIVE'] != $fields['ACTIVE'])
		{
			if ($fields['ACTIVE'] == 'Y')
			{
				$sendUpdate = true;
			}
			else
			{
				$sendDelete = true;
			}
		}
		else
		{
			if (isset($fields['LINE_NAME']) && $config['LINE_NAME'] != $fields['LINE_NAME'])
			{
				$lineName = $fields['LINE_NAME'];
				$sendUpdate = true;
			}
			if (isset($fields['QUEUE_TYPE']) && $config['QUEUE_TYPE'] != $fields['QUEUE_TYPE'])
			{
				$sendUpdate = true;
				$queueType = $fields['QUEUE_TYPE'];
			}
		}

		if ($sendUpdate)
		{
			self::sendUpdateForQueueList(Array(
				'ID' => $id,
				'NAME' => $lineName,
				'SESSION_PRIORITY' => isset($fields['SESSION_PRIORITY'])? $fields['SESSION_PRIORITY']: $config['SESSION_PRIORITY'],
				'QUEUE_TYPE' => $queueType
			));
		}
		else if ($sendDelete)
		{
			self::sendUpdateForQueueList(Array(
				'ID' => $id,
				'ACTION' => 'DELETE',
				'SESSION_PRIORITY' => 0
			));
		}

		return true;
	}

	/**
	 * @param $id
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function delete($id)
	{
		$id = (int)$id;
		if (!$id)
			return false;

		$orm = Model\ConfigTable::getById($id);
		if (!($config = $orm->fetch()))
			return false;

		Model\ConfigTable::delete($id);
		ConfigStatistic::delete($id);

		if($config['QUICK_ANSWERS_IBLOCK_ID'] > 0)
		{
			ListsDataManager::updateIblockRights($config['QUICK_ANSWERS_IBLOCK_ID']);
		}

		$orm = Model\QueueTable::getList([
			'select' => ['ID'],
			'filter' => ['=CONFIG_ID' => $id]
		]);
		while ($row = $orm->fetch())
		{
			Model\QueueTable::delete($row['ID']);
		}

		$raw = Model\ConfigQueueTable::getList([
			'select' => ['ID'],
			'filter' => ['=CONFIG_ID' => $id]
		]);
		while ($row = $raw->fetch())
		{
			Model\ConfigQueueTable::delete($row['ID']);
		}

		$this->deleteAllAutomaticMessage($id);

		$orm = Model\SessionTable::getList([
			'select' => ['ID'],
			'filter' => ['=CONFIG_ID' => $id]
		]);
		while ($row = $orm->fetch())
		{
			Session::deleteSession($row['ID']);
		}

		try
		{
			if (Loader::includeModule('im'))
			{
				$aliases = \Bitrix\Im\Model\AliasTable::getList(
					Array(
						'filter' => Array(
							'=ALIAS' => \Bitrix\Im\Alias::ENTITY_TYPE_LIVECHAT,
							'=ENTITY_ID' => $id
						)
					)
				);
				while ($alias = $aliases->fetch())
				{
					\Bitrix\Im\Alias::delete($alias['ID']);
				}
			}

			if (Loader::includeModule('imconnector'))
			{
				\Bitrix\ImConnector\Output::deleteLine($id);
			}
		}
		catch (\Exception $e)
		{}

		self::sendUpdateForQueueList(Array(
			'ID' => $id,
			'ACTION' => 'DELETE',
			'SESSION_PRIORITY' => 0
		));

		$eventData = array(
			'line' => $id
		);
		$event = new Main\Event('imopenlines', self::EVENT_IMOPENLINE_DELETE, $eventData);
		$event->send();

		return true;
	}

	/**
	 * @param $id
	 * @param bool $status
	 * @return bool
	 */
	public function setActive($id, $status = true)
	{
		return $this->update($id, ['ACTIVE' => $status? 'Y': 'N']);
	}

	public static function canActivateLine()
	{
		if(!\Bitrix\Main\Loader::includeModule("bitrix24"))
		{
			return true;
		}

		$maxLines = Limit::getLinesLimit();
		if ($maxLines == 0)
		{
			return true;
		}
		return $maxLines > Model\ConfigTable::getCount(array('=ACTIVE' => 'Y', '=TEMPORARY' => 'N'));
	}

	private static function canDoOperation($id, $entity, $action)
	{
		if (isset(self::$cacheOperation[$id][$entity][$action]))
		{
			return self::$cacheOperation[$id][$entity][$action];
		}

		$userId = Security\Helper::getCurrentUserId();
		if (isset(self::$cachePermission[$userId][$entity][$action]))
		{
			$allowedUserIds = self::$cachePermission[$userId][$entity][$action];
		}
		else
		{
			$permission = Security\Permissions::createWithCurrentUser();
			$allowedUserIds = Security\Helper::getAllowedUserIds(
				$userId,
				$permission->getPermission($entity, $action)
			);

			self::$cachePermission[$userId][$entity][$action] = $allowedUserIds;
		}

		if (!is_array($allowedUserIds))
		{
			self::$cacheOperation[$id][$entity][$action] = true;
			return true;
		}
		else if (empty($allowedUserIds))
		{
			self::$cacheOperation[$id][$entity][$action] = false;
			return false;
		}

		$canEdit = false;
		$orm = \Bitrix\ImOpenlines\Model\QueueTable::getList([
			'filter' => [
				'=USER_ID' => $allowedUserIds,
				'=CONFIG_ID' => $id
			],
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'ASC'
			]
		]);
		if ($row = $orm->fetch())
		{
			$canEdit = true;
		}
		if (!$canEdit)
		{
			$configManager = new self();
			$config = $configManager->get($id, false);

			if ($config['MODIFY_USER_ID'] == $userId)
			{
				$canEdit = true;
			}
		}

		self::$cacheOperation[$id][$entity][$action] = $canEdit;

		return $canEdit;
	}

	public static function canViewLine($id)
	{
		return self::canDoOperation($id, Security\Permissions::ENTITY_LINES, Security\Permissions::ACTION_VIEW);
	}

	public static function canEditLine($id)
	{
		return self::canDoOperation($id, Security\Permissions::ENTITY_LINES, Security\Permissions::ACTION_MODIFY);
	}

	public static function canEditConnector($id)
	{
		return self::canDoOperation($id, Security\Permissions::ENTITY_CONNECTORS, Security\Permissions::ACTION_MODIFY);
	}

	/**
	 * @param $id
	 * @param null $crmEntityType
	 * @param null $crmEntityId
	 * @return bool|mixed
	 * @throws Main\LoaderException
	 */
	public static function canJoin($id, $crmEntityType = null, $crmEntityId = null)
	{
		if(
			!empty($crmEntityType) &&
			!empty($crmEntityId)
		)
		{
			return self::canDoOperation($id, Security\Permissions::ENTITY_JOIN, Security\Permissions::ACTION_PERFORM) || \Bitrix\ImOpenLines\Crm\Common::hasAccessToEntity($crmEntityType, $crmEntityId);
		}

		return self::canDoOperation($id, Security\Permissions::ENTITY_JOIN, Security\Permissions::ACTION_PERFORM);
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getIdConfigCanJoin(): array
	{
		$result = [];

		$configs = Model\ConfigTable::getList([
			'select' => ['ID'],
			'cache' => ['ttl' => 84600]
		]);

		while ($config = $configs->fetch())
		{
			if(self::canJoin($config['ID']))
			{
				$result[] = $config['ID'];
			}
		}

		return $result;
	}

	/**
	 * @param $id
	 * @param bool $checkLimit
	 * @return bool|mixed
	 */
	public static function canVoteAsHead($id, $checkLimit = true)
	{
		$result = false;

		if (
			$checkLimit === false ||
			Limit::canUseVoteHead()
		)
		{
			$result =  self::canDoOperation($id, Security\Permissions::ENTITY_VOTE_HEAD, Security\Permissions::ACTION_PERFORM);
		}

		return $result;
	}

	/**
	 * @param $id
	 * @param bool $withQueue
	 * @param bool $showOffline
	 * @param bool $withConfigQueue
	 * @return array|bool|false
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function get($id, $withQueue = true, $showOffline = true, $withConfigQueue = false)
	{
		$config = false;

		$id = (int)$id;
		if (
			!empty($id) ||
			$id > 0
		)
		{
			$orm = Model\ConfigTable::getById($id);
			if ($config = $orm->fetch())
			{
				$config['WORKTIME_DAYOFF'] = explode(',', $config['WORKTIME_DAYOFF']);
				$config['WORKTIME_HOLIDAYS'] = explode(',', $config['WORKTIME_HOLIDAYS']);

				$config['QUEUE'] = [];
				$config['QUEUE_FULL'] = [];
				$config['QUEUE_USERS_FIELDS'] = [];
				$config['QUEUE_ONLINE'] = 'N';
				if ($withQueue)
				{
					$selectQueue = ['ID', 'SORT', 'USER_ID', 'DEPARTMENT_ID', 'USER_NAME', 'USER_WORK_POSITION', 'USER_AVATAR', 'USER_AVATAR_ID'];
					$filterQueue = ['=CONFIG_ID' => $id, '=USER.ACTIVE' => 'Y'];

					if ($showOffline === true)
					{
						$selectQueue[] = 'IS_ONLINE_CUSTOM';
					}
					else
					{
						$filterQueue['=IS_ONLINE_CUSTOM'] = 'Y';
					}

					$orm = Queue::getList([
						'select' => $selectQueue,
						'filter' => $filterQueue,
						'order' => [
							'SORT' => 'ASC',
							'ID' => 'ASC'
						],
					]);

					while ($row = $orm->fetch())
					{
						$config['QUEUE'][] = $row['USER_ID'];
						if (
							(
								$showOffline === true &&
								(string)$row['IS_ONLINE_CUSTOM'] === 'Y'
							) ||
							$showOffline !== true
						)
						{
							$config['QUEUE_ONLINE'] = 'Y';
						}
						$config['QUEUE_USERS_FIELDS'][$row['USER_ID']] = [
							'USER_NAME' => $row['USER_NAME'],
							'USER_WORK_POSITION' => $row['USER_WORK_POSITION'],
							'USER_AVATAR' => $row['USER_AVATAR'],
							'USER_AVATAR_ID' => $row['USER_AVATAR_ID']
						];

						$config['QUEUE_FULL'][$row['USER_ID']] = [
							'ID' => $row['ID'],
							'SORT' => $row['SORT'],
							'USER_ID' => $row['USER_ID'],
							'DEPARTMENT_ID' => $row['DEPARTMENT_ID'],
							'USER_NAME' => $row['USER_NAME'],
							'USER_WORK_POSITION' => $row['USER_WORK_POSITION'],
							'USER_AVATAR' => $row['USER_AVATAR'],
							'USER_AVATAR_ID' => $row['USER_AVATAR_ID']
						];
					}
				}

				if($withConfigQueue === true)
				{
					$queueManager = new QueueManager($id);

					$config['configQueue'] = $queueManager->getConfigQueue();
				}

				if (!\Bitrix\Imopenlines\Limit::canUseVoteClient())
				{
					$config['VOTE_MESSAGE'] = 'N';
				}

				if (!\Bitrix\Imopenlines\Limit::canWorkHourSettings())
				{
					$config['WORKTIME_ENABLE'] = 'N';
				}

				$textFieldsWithEmoji = [
					'WELCOME_MESSAGE_TEXT',
					'VOTE_MESSAGE_1_TEXT', 'VOTE_MESSAGE_1_LIKE', 'VOTE_MESSAGE_1_DISLIKE',
					'VOTE_MESSAGE_2_TEXT', 'VOTE_MESSAGE_2_LIKE', 'VOTE_MESSAGE_2_DISLIKE',
					'NO_ANSWER_TEXT', 'WORKTIME_DAYOFF_TEXT', 'CLOSE_TEXT', 'AUTO_CLOSE_TEXT'
				];

				foreach ($textFieldsWithEmoji as $textFieldName)
				{
					$config[$textFieldName] = Emoji::decode($config[$textFieldName]);
				}
			}
		}

		return $config;
	}

	/**
	 * Returns all automatic message tasks for a specific open line.
	 *
	 * @param $idConfig
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getAutomaticMessage($idConfig): array
	{
		$result = [];
		$idConfig = (int)$idConfig;

		if (!empty($idConfig))
		{
			$configTasks = ConfigAutomaticMessagesTable::getList([
				'select' => ['*'],
				'filter' => ['=CONFIG_ID' => $idConfig],
				'order' => ['ID'],
			]);

			while ($configTask = $configTasks->fetch())
			{
				$result[] = $configTask;
			}
		}

		return $result;
	}

	/**
	 * @param $idConfig
	 * @param $configs
	 * @return Result
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function updateAllAutomaticMessage($idConfig, $configs): Result
	{
		$result = new Result();
		$resultData = [
			'add' => [],
			'update' => [],
			'delete' => []
		];
		$currentIdConfigTasks = [];

		$addConfigTasks = [];
		$updateConfigTasks = [];
		$deleteIdConfigTasks = [];

		$configTasks = ConfigAutomaticMessagesTable::getList([
			'select' => ['ID'],
			'filter' => ['=CONFIG_ID' => $idConfig],
			'order' => ['ID'],
		]);

		while ($configTask = $configTasks->fetch())
		{
			$deleteIdConfigTasks[$configTask['ID']] = $currentIdConfigTasks[$configTask['ID']] = $configTask['ID'];
		}

		foreach ($configs as $config)
		{
			if(empty($config['ID']))
			{
				$addConfigTasks[] = [
					'CONFIG_ID' => $idConfig,
					'TIME_TASK' => $config['TIME_TASK'],
					'MESSAGE' => $config['MESSAGE'],
					'TEXT_BUTTON_CLOSE' => $config['TEXT_BUTTON_CLOSE'],
					'LONG_TEXT_BUTTON_CLOSE' => $config['LONG_TEXT_BUTTON_CLOSE'],
					'AUTOMATIC_TEXT_CLOSE' => $config['AUTOMATIC_TEXT_CLOSE'],
					'TEXT_BUTTON_CONTINUE' => $config['TEXT_BUTTON_CONTINUE'],
					'LONG_TEXT_BUTTON_CONTINUE' => $config['LONG_TEXT_BUTTON_CONTINUE'],
					'AUTOMATIC_TEXT_CONTINUE' => $config['AUTOMATIC_TEXT_CONTINUE'],
					'TEXT_BUTTON_NEW' => $config['TEXT_BUTTON_NEW'],
					'LONG_TEXT_BUTTON_NEW' => $config['LONG_TEXT_BUTTON_NEW'],
					'AUTOMATIC_TEXT_NEW' => $config['AUTOMATIC_TEXT_NEW'],
				];
			}
			elseif(isset($currentIdConfigTasks[$config['ID']]))
			{
				if(empty($updateConfigTasks[$config['ID']]))
				{
					$updateConfigTasks[$config['ID']] = [
						'ID' => $config['ID'],
						'TIME_TASK' => $config['TIME_TASK'],
						'MESSAGE' => $config['MESSAGE'],
						'TEXT_BUTTON_CLOSE' => $config['TEXT_BUTTON_CLOSE'],
						'LONG_TEXT_BUTTON_CLOSE' => $config['LONG_TEXT_BUTTON_CLOSE'],
						'AUTOMATIC_TEXT_CLOSE' => $config['AUTOMATIC_TEXT_CLOSE'],
						'TEXT_BUTTON_CONTINUE' => $config['TEXT_BUTTON_CONTINUE'],
						'LONG_TEXT_BUTTON_CONTINUE' => $config['LONG_TEXT_BUTTON_CONTINUE'],
						'AUTOMATIC_TEXT_CONTINUE' => $config['AUTOMATIC_TEXT_CONTINUE'],
						'TEXT_BUTTON_NEW' => $config['TEXT_BUTTON_NEW'],
						'LONG_TEXT_BUTTON_NEW' => $config['LONG_TEXT_BUTTON_NEW'],
						'AUTOMATIC_TEXT_NEW' => $config['AUTOMATIC_TEXT_NEW'],
					];
					unset($deleteIdConfigTasks[$config['ID']]);
				}
				else
				{
					$result->addError(new Error('The input parameters contain tasks with the same ID twice', 'IMOL_CONFIG_ERROR_IDS_MATCH', __METHOD__, ['idConfig' => $idConfig, 'config' => $config]));
				}
			}
		}

		foreach ($addConfigTasks as $configTask)
		{
			$resultAdd = ConfigAutomaticMessagesTable::add($configTask);

			if($resultAdd->isSuccess())
			{
				$resultData['update'][] = $resultAdd->getId();
			}
			else
			{
				$result->addErrors($resultAdd->getErrors());
			}
		}

		foreach ($updateConfigTasks as $configTask)
		{
			$idTask = $configTask['ID'];
			unset($configTask['ID']);

			$resultUpdate = ConfigAutomaticMessagesTable::update($idTask, $configTask);

			if($resultUpdate->isSuccess())
			{
				$resultData['update'][] = $idTask;
			}
			else
			{
				$result->addErrors($resultUpdate->getErrors());
			}
		}

		foreach ($deleteIdConfigTasks as $idTask)
		{
			if($this->deleteAutomaticMessage($idConfig, $idTask))
			{
				$resultData['delete'][] = $idTask;
			}
			else
			{
				$result->addError(new Error('Couldn\'t delete task', 'IMOL_CONFIG_ERROR_DELETE_TASK', __METHOD__, ['idConfig' => $idConfig, 'idTask' => $idTask]));
			}
		}

		$result->setData($resultData);

		return $result;
	}

	/**
	 * @param $idConfig
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function deleteAllAutomaticMessage($idConfig): bool
	{
		$result = true;

		$configTasks = ConfigAutomaticMessagesTable::getList([
			'select' => ['ID'],
			'filter' => ['=CONFIG_ID' => $idConfig],
			'order' => ['ID'],
		]);

		while ($configTask = $configTasks->fetch())
		{
			$resultDelete = ConfigAutomaticMessagesTable::delete($configTask['ID']);

			if(!$resultDelete->isSuccess())
			{
				$result = false;
			}
			else
			{
				$tasks = SessionAutomaticTasksTable::getList([
					'select' => ['ID'],
					'filter' => ['=CONFIG_AUTOMATIC_MESSAGE_ID' => $configTask['ID']]
				]);

				foreach ($tasks as $task)
				{
					SessionAutomaticTasksTable::delete($task['ID']);
				}
			}
		}

		return $result;
	}

	/**
	 * @param $idConfig
	 * @param $idTask
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function deleteAutomaticMessage($idConfig, $idTask): bool
	{
		$result = false;

		$configTasks = ConfigAutomaticMessagesTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=CONFIG_ID' => $idConfig,
				'=ID' => $idTask
			]
		]);

		while ($configTask = $configTasks->fetch())
		{
			$resultDelete = ConfigAutomaticMessagesTable::delete($idTask);

			if($resultDelete->isSuccess())
			{
				$tasks = SessionAutomaticTasksTable::getList([
					'select' => ['ID'],
					'filter' => ['=CONFIG_AUTOMATIC_MESSAGE_ID' => $idTask]
				]);

				foreach ($tasks as $task)
				{
					SessionAutomaticTasksTable::delete($task['ID']);
				}

				$result = true;
			}
		}

		return $result;
	}

	public function getList(array $params, $options = [])
	{
		if(
			isset($options['QUEUE']) &&
			(string)$options['QUEUE'] === 'Y'
		)
		{
			$withQueue = true;
		}
		else
		{
			$withQueue = false;
		}

		if(
			isset($options['CONFIG_QUEUE']) &&
			(string)$options['CONFIG_QUEUE'] === 'Y'
		)
		{
			$withConfigQueue = true;
		}
		else
		{
			$withConfigQueue = false;
		}

		$configs = [];
		$orm = Model\ConfigTable::getList($params);
		while ($config = $orm->fetch())
		{
			if (isset($config['WORKTIME_DAYOFF']))
			{
				$config['WORKTIME_DAYOFF'] = explode(',', $config['WORKTIME_DAYOFF']);
			}
			if (isset($config['WORKTIME_HOLIDAYS']))
			{
				$config['WORKTIME_HOLIDAYS'] = explode(',', $config['WORKTIME_HOLIDAYS']);
			}

			if ($withQueue === true)
			{
				$config['QUEUE'] = [];
				$config['QUEUE_USERS_FIELDS'] = [];
				$ormQueue = Model\QueueTable::getList([
					'filter' => ['=CONFIG_ID' => $config['ID']],
					'order' => [
						'SORT' => 'ASC',
						'ID' => 'ASC'
					]
				]);
				while ($row = $ormQueue->fetch())
				{
					$config['QUEUE'][] = $row['USER_ID'];
					$config['QUEUE_USERS_FIELDS'][$row['USER_ID']] = [
						'USER_NAME' => $row['USER_NAME'],
						'USER_WORK_POSITION' => $row['USER_WORK_POSITION'],
						'USER_AVATAR' => $row['USER_AVATAR'],
						'USER_AVATAR_ID' => $row['USER_AVATAR_ID']
					];
				}
			}

			if ($withConfigQueue === true)
			{
				$config['CONFIG_QUEUE'] = [];
				$ormConfigQueue = Model\ConfigQueueTable::getList([
					'filter' => ['=CONFIG_ID' => $config['ID']],
					'order' => [
						'SORT' => 'ASC',
						'ID' => 'ASC'
					]
				]);
				while ($row = $ormConfigQueue->fetch())
				{
					$config['CONFIG_QUEUE'][] = [
						'ENTITY_ID' => $row['ENTITY_ID'],
						'ENTITY_TYPE' => $row['ENTITY_TYPE'],
					];
				}
			}

			$configs[] = $config;
		}

		return $configs;
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getOptionList()
	{
		$list = [];
		$orm = Model\ConfigTable::getList([
			'select' => ['ID', 'NAME' => 'LINE_NAME'],
			'filter' => ['=ACTIVE' => 'Y'],
			'cache' => ['ttl' => 86400],
			'order' => ['LINE_NAME' => 'ASC'],
		]);
		while ($config = $orm->fetch())
		{
			$list[] = $config;
		}

		return $list;
	}

	public static function getQueueList($userId = 0, $emptyIsNotOperator = true)
	{
		// TODO add self cache

		$select = ['ID', 'NAME' => 'LINE_NAME', 'PRIORITY' => 'SESSION_PRIORITY', 'QUEUE_TYPE'];
		$runtime = [];
		$order = [];

		$userId = (int)$userId;
		if ($userId > 0)
		{
			$select['USER_ID'] = 'QUEUE.USER_ID';
			$order = ['QUEUE.USER_ID' => 'DESC', 'ID' => 'ASC'];
			$runtime[] = new \Bitrix\Main\Entity\ReferenceField(
				'QUEUE',
				'\Bitrix\ImOpenlines\Model\QueueTable',
				[
					'=ref.CONFIG_ID' => 'this.ID',
					'=ref.USER_ID' => new \Bitrix\Main\DB\SqlExpression('?', $userId)
				],
				['join_type'=>'LEFT']
			);
		}

		$list = [];
		$needSkip = true;
		$orm = Model\ConfigTable::getList([
			'select' => $select,
			'filter' => ['=ACTIVE' => 'Y'],
			'order' => $order,
			'runtime' => $runtime,
			'cache' => ['ttl' => 86400, 'cache_joins' => true]
		]);
		while ($config = $orm->fetch())
		{
			if ($config['USER_ID'] > 0)
			{
				$needSkip = false;
			}
			unset($config['USER_ID']);

			$list[] = $config;
		}

		if ($emptyIsNotOperator && $needSkip)
		{
			$list = [];
		}

		foreach ($list as $key => $value)
		{
			$list[$key]['TRANSFER_COUNT'] = \CUserCounter::GetValue($userId, 'imopenlines_transfer_count_'.$value['ID']);
		}

		return $list;
	}

	public static function sendUpdateForQueueList($data)
	{
		$isDelete = isset($data['ACTION']) && $data['ACTION'] == 'DELETE';

		if (intval($data['ID']) <= 0)
			return false;

		if (!$isDelete && empty($data['NAME']))
			return false;

		if (!\Bitrix\Main\Loader::includeModule('pull'))
			return false;

		$channelId = [];
		$orm = \Bitrix\ImOpenlines\Model\QueueTable::getList([
			'select' => [
				'USER_ID',
				'CHANNEL_ID' =>	'CHANNEL.CHANNEL_ID'
			 ],
			'runtime' => [
				new \Bitrix\Main\Entity\ReferenceField(
					'CHANNEL',
					'\Bitrix\Pull\Model\ChannelTable',
					[
						'=ref.USER_ID' => 'this.USER_ID',
						'=ref.CHANNEL_TYPE' => new Main\DB\SqlExpression('?s', 'private')
					],
					['join_type' => 'LEFT']
				)
			],
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'ASC'
			]
		]);
		while ($row = $orm->fetch())
		{
			if (!$row['CHANNEL_ID'])
				continue;

			$channelId[$row['USER_ID']] = $row['CHANNEL_ID'];
		}

		\Bitrix\Pull\Event::add(array_values($channelId), Array(
			'module_id' => 'imopenlines',
			'command' => $isDelete? 'queueItemDelete': 'queueItemUpdate',
			'expiry' => 3600,
			'params' => Array(
				'id' => $data['ID'],
				'name' => $isDelete? '': $data['NAME'],
				'PRIORITY' => $data['SESSION_PRIORITY'],
				'queue_type' => $data['QUEUE_TYPE']
			),
		));

		return true;
	}

	public function getFormForAuth()
	{
		return 0;
	}

	public function getFormForRating()
	{
		return 0;
	}

	public function getFormValues()
	{
		$array = Array();
		return $array;
	}

	public static function getInstance()
	{
		return new self();
	}

	public static function deleteTemporaryConfigAgent($id)
	{
		$orm = Model\ConfigTable::getList(Array(
			'filter'=>Array(
				'=ID' => $id,
			)
		));
		if ($config = $orm->fetch())
		{
			if ($config['TEMPORARY'] == 'Y')
			{
				$configManager = new self();
				$configManager->delete($config['ID']);
			}
		}
		return "";
	}

	public static function checkLinesLimit()
	{
		$maxLines = Limit::getLinesLimit();
		if ($maxLines == 0)
		{
			return true;
		}
		if ($maxLines >= Model\ConfigTable::getCount(array('=ACTIVE' => 'Y', '=TEMPORARY' => 'N')))
		{
			return true;
		}
		$orm = Model\ConfigTable::getList(Array(
			'select' => Array('ID'),
			'filter' => Array(
				'=ACTIVE' => 'Y',
				'=TEMPORARY' => 'N'
			),
			'order' => Array(
				'ID' => 'ASC'
			)
		));

		$configManager = new self();
		while($row = $orm->fetch())
		{
			if ($maxLines != 0)
			{
				$maxLines--;
				continue;
			}
			$configManager->setActive($row['ID'], false);
		}

		return true;
	}

	public static function available()
	{
		$orm = \Bitrix\ImOpenLines\Model\ConfigTable::getList(Array(
			'select' => Array('CNT'),
			'runtime' => array(
				new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')
			),
		));
		$row = $orm->fetch();
		return ($row['CNT'] > 0);
	}

	private static function getSla($configId)
	{
		// Temporary hack :(
		return function_exists('customImopenlinesConfigSla')? customImopenlinesConfigSla($configId): 0;
	}

	public function getError()
	{
		return $this->error;
	}

	/**
	 * Return queue operator data config type
	 *
	 * @param $configId
	 * @return mixed|string
	 */
	public static function operatorDataConfig($configId)
	{
		$result = '';

		$configId = intval($configId);

		if ($configId > 0)
		{
			$config = Model\ConfigTable::getByPrimary(
				$configId,
				array(
					'cache' => array('ttl' => self::CONFIG_CACHE_TIME)
				)
			)->fetch();

			if (!empty($config) && is_array($config))
			{
				$result = $config['OPERATOR_DATA'];
			}
		}

		return $result;
	}

	/**
	 * Return bool param to show or not to show operator data in chat
	 *
	 * @param $configId
	 *
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function isShowOperatorData($configId)
	{
		$operatorDataConfig = self::operatorDataConfig($configId);

		return $operatorDataConfig !== self::OPERATOR_DATA_HIDE;
	}

	/**
	 * Return config default operator data for case operator data hide
	 *
	 * @param $configId
	 *
	 * @return array|mixed
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getDefaultOperatorData($configId)
	{
		$result = array();

		$configId = intval($configId);

		if ($configId > 0)
		{
			$config = Model\ConfigTable::getByPrimary(
				$configId,
				array(
					'select' => array('DEFAULT_OPERATOR_DATA'),
					'cache' => array('ttl' => self::CONFIG_CACHE_TIME)
				)
			)->fetch();

			if (!empty($config) && is_array($config))
			{
				$result = $config['DEFAULT_OPERATOR_DATA'];
			}
		}

		return $result;
	}


	/**
	 * Check config is active
	 *
	 * @param $configId
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function isConfigActive($configId): bool
	{
		$config = ConfigTable::getList(
			[
				'select' => ['ACTIVE'],
				'filter' => [
					'=ID' => $configId
				]
			]
		)->fetch();

		return $config['ACTIVE'] == 'Y';
	}

	/**
	 * Check whether the time tracking functionality is available for this portal.
	 *
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function isTimeManActive(): bool
	{
		$result = false;

		if(Loader::includeModule('timeman'))
		{
			if(Loader::includeModule('bitrix24'))
			{
				if(Feature::isFeatureEnabled("timeman"))
				{
					$result = true;
				}
			}
			else
			{
				if(class_exists('CBXFeatures'))
				{
					if(\CBXFeatures::IsFeatureEnabled('timeman'))
					{
						$result = true;
					}
				}
				else
				{
					$result = true;
				}
			}
		}

		return $result;
	}
}
