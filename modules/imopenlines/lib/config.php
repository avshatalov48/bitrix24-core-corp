<?php
namespace Bitrix\ImOpenLines;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Text\Emoji;
use Bitrix\ImOpenLines\Queue;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;

use Bitrix\Bitrix24\Feature;

use Bitrix\ImOpenLines\Model\ConfigTable;
use Bitrix\ImOpenLines\Model\ConfigQueueTable;
use Bitrix\ImOpenlines\QuickAnswers\ListsDataManager;
use Bitrix\ImOpenLines\Model\SessionAutomaticTasksTable;
use Bitrix\Imopenlines\Model\ConfigAutomaticMessagesTable;

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
	private $userId = 0;

	static $cacheOperation = [];
	static $cachePermission = [];

	public function __construct($userId = null)
	{
		$this->error = new BasicError(null, '', '');
		$this->userId = $userId ?? Security\Helper::getCurrentUserId();
	}

	/**
	 * Checks any open line configuration existence and creates new one in other case.
	 * @return bool
	 */
	public function createPreset(): bool
	{
		$anyActiveLine = ConfigTable::getRow([
			'select' => ['ID'],
			'filter' => ['=ACTIVE' => 'Y'],
		]);
		if (!$anyActiveLine)
		{
			if (!$this->userId)
			{
				$users = Security\Helper::getAdministrators();
				$this->userId = \reset($users);
			}

			$lineId = $this->create([
				'QUEUE' => [
					$this->userId
				]
			]);
			if (!$lineId)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @param $params
	 * @param $mode
	 * @return array
	 */
	private function prepareFields($params, $mode = self::MODE_ADD)
	{
		$companyName = \Bitrix\Main\Config\Option::get("main", "site_name", "");

		$fields = [];
		if (isset($params['LINE_NAME']) && !empty($params['LINE_NAME']))
		{
			$fields['LINE_NAME'] = $params['LINE_NAME'];
		}
		elseif ($mode == self::MODE_ADD)
		{
			$configCount = Model\ConfigTable::getList(array(
				'select' => array('CNT'),
				'runtime' => array(new ExpressionField('CNT', 'COUNT(*)'))
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

		if (\Bitrix\Main\ModuleManager::isModuleInstalled('crm'))
		{
			if (isset($params['CRM']))
			{
				$fields['CRM'] = $params['CRM'] == 'N'? 'N': 'Y';
			}
			elseif ($mode == self::MODE_ADD)
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
		elseif ($mode == self::MODE_ADD)
		{
			$fields['CRM_CREATE'] = self::CRM_CREATE_LEAD;
		}

		if (isset($params['CRM_CREATE_SECOND']))
		{
			$fields['CRM_CREATE_SECOND'] = $params['CRM_CREATE_SECOND'];
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['CRM_CREATE_SECOND'] = '';
		}

		if (isset($params['CRM_CREATE_THIRD']))
		{
			$fields['CRM_CREATE_THIRD'] = $params['CRM_CREATE_THIRD'] === 'N'? 'N': 'Y';
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['CRM_CREATE_THIRD'] = 'Y';
		}

		if (isset($params['CRM_FORWARD']))
		{
			$fields['CRM_FORWARD'] = $params['CRM_FORWARD'] == 'N'? 'N': 'Y';
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['CRM_FORWARD'] = 'Y';
		}

		if (isset($params['CRM_CHAT_TRACKER']))
		{
			$fields['CRM_CHAT_TRACKER'] = $params['CRM_CHAT_TRACKER'] == 'N'? 'N': 'Y';
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['CRM_CHAT_TRACKER'] = 'Y';
		}

		if (isset($params['CRM_TRANSFER_CHANGE']))
		{
			$fields['CRM_TRANSFER_CHANGE'] = $params['CRM_TRANSFER_CHANGE'] == 'N'? 'N': 'Y';
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['CRM_TRANSFER_CHANGE'] = 'Y';
		}

		if (isset($params['CRM_SOURCE']))
		{
			$fields['CRM_SOURCE'] = $params['CRM_SOURCE'];
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['CRM_SOURCE'] = self::CRM_SOURCE_AUTO_CREATE;
		}

		if (isset($params['QUEUE_TIME']))
		{
			$fields['QUEUE_TIME'] = intval($params['QUEUE_TIME']);
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['QUEUE_TIME'] = 60;
		}

		if (isset($params['NO_ANSWER_TIME']))
		{
			$fields['NO_ANSWER_TIME'] = intval($params['NO_ANSWER_TIME']);
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['NO_ANSWER_TIME'] = 60;
		}

		if (isset($params['MAX_CHAT']))
		{
			if ((int)$params['MAX_CHAT'] >= 0)
			{
				$fields['MAX_CHAT'] = (int)$params['MAX_CHAT'];
			}
			else
			{
				$fields['MAX_CHAT'] = 0;
			}
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['MAX_CHAT'] = 0;
		}

		if (isset($params['TYPE_MAX_CHAT']))
		{
			if (in_array($params['TYPE_MAX_CHAT'], [self::TYPE_MAX_CHAT_ANSWERED_NEW, self::TYPE_MAX_CHAT_ANSWERED, self::TYPE_MAX_CHAT_CLOSED], true))
			{
				$fields['TYPE_MAX_CHAT'] = $params['TYPE_MAX_CHAT'];
			}
			else
			{
				$fields['TYPE_MAX_CHAT'] = self::TYPE_MAX_CHAT_ANSWERED_NEW;
			}
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['TYPE_MAX_CHAT'] = self::TYPE_MAX_CHAT_ANSWERED;
		}

		if (isset($params['CATEGORY_ENABLE']))
		{
			$fields['CATEGORY_ENABLE'] = $params['CATEGORY_ENABLE'] == 'Y'? 'Y': 'N';
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields["CATEGORY_ENABLE"] = 'N';
		}

		if (isset($params['CATEGORY_ID']))
		{
			$fields['CATEGORY_ID'] = $fields['CATEGORY_ENABLE'] == 'Y'? intval($params['CATEGORY_ID']): 0;
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields["CATEGORY_ID"] = 0;
		}

		if (isset($params['SESSION_PRIORITY']))
		{
			$params['SESSION_PRIORITY'] = intval($params['SESSION_PRIORITY']);
			$fields['SESSION_PRIORITY'] = $params['SESSION_PRIORITY'] >= 0 && $params['SESSION_PRIORITY'] <= 86400? $params['SESSION_PRIORITY']: ($params['SESSION_PRIORITY'] > 0? 86400: 0);
		}
		elseif ($mode == self::MODE_ADD)
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
		elseif ($mode == self::MODE_ADD)
		{
			$fields["AGREEMENT_MESSAGE"] = 'N';
		}

		if (isset($params['AGREEMENT_ID']))
		{
			$fields['AGREEMENT_ID'] = intval($params['AGREEMENT_ID']);
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields["AGREEMENT_ID"] = 0;
		}

		if (isset($params['WELCOME_BOT_ENABLE']))
		{
			$fields['WELCOME_BOT_ENABLE'] = $params['WELCOME_BOT_ENABLE'] == 'Y'? 'Y': 'N';
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields["WELCOME_BOT_ENABLE"] = 'N';
		}

		if (isset($params['WELCOME_BOT_JOIN']))
		{
			$fields['WELCOME_BOT_JOIN'] = $params['WELCOME_BOT_JOIN'] == self::BOT_JOIN_FIRST? self::BOT_JOIN_FIRST: self::BOT_JOIN_ALWAYS;
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['WELCOME_BOT_JOIN'] = self::BOT_JOIN_ALWAYS;
		}

		if (isset($params['WELCOME_BOT_ID']))
		{
			$fields['WELCOME_BOT_ID'] = $fields["WELCOME_BOT_ENABLE"] == 'Y'? intval($params['WELCOME_BOT_ID']): 0;
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['WELCOME_BOT_ID'] = 0;
		}

		if (isset($params['WELCOME_BOT_TIME']))
		{
			$fields['WELCOME_BOT_TIME'] = $fields["WELCOME_BOT_ENABLE"] == 'Y'? intval($params['WELCOME_BOT_TIME']): 600;
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['WELCOME_BOT_TIME'] = 600;
		}

		if (isset($params['WELCOME_BOT_LEFT']))
		{
			$fields['WELCOME_BOT_LEFT'] = $fields["WELCOME_BOT_ENABLE"] == 'Y' && $params['WELCOME_BOT_LEFT'] == self::BOT_LEFT_CLOSE? self::BOT_LEFT_CLOSE: self::BOT_LEFT_QUEUE;
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['WELCOME_BOT_LEFT'] = self::BOT_LEFT_QUEUE;
		}

		if (isset($params['QUEUE_TYPE']))
		{
			$fields['QUEUE_TYPE'] = in_array($params['QUEUE_TYPE'], Array(self::QUEUE_TYPE_STRICTLY, self::QUEUE_TYPE_ALL, self::QUEUE_TYPE_EVENLY))? $params['QUEUE_TYPE']: self::QUEUE_TYPE_ALL;
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['QUEUE_TYPE'] = self::QUEUE_TYPE_ALL;
		}
		if (
			isset($params['QUEUE_TYPE'])
			&& $fields['QUEUE_TYPE'] == self::QUEUE_TYPE_ALL
			&& !Limit::canUseQueueAll()
		)
		{
			$fields['QUEUE_TYPE'] = self::QUEUE_TYPE_EVENLY;
		}

		if (isset($params['CHECK_AVAILABLE']))
		{
			$fields['CHECK_AVAILABLE'] = $params['CHECK_AVAILABLE'] == 'N'? 'N': 'Y';
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields["CHECK_AVAILABLE"] = 'N';
		}

		if (isset($params['WATCH_TYPING']))
		{
			$fields['WATCH_TYPING'] = $params['WATCH_TYPING'] == 'Y'? 'Y': 'N';
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields["WATCH_TYPING"] = 'Y';
		}

		if (isset($params['WELCOME_MESSAGE']))
		{
			$fields['WELCOME_MESSAGE'] = $params['WELCOME_MESSAGE'] == 'N'? 'N': 'Y';
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['WELCOME_MESSAGE'] = 'Y';
		}

		if (isset($params['SEND_WELCOME_EACH_SESSION']))
		{
			$fields['SEND_WELCOME_EACH_SESSION'] = $params['SEND_WELCOME_EACH_SESSION'] == 'Y'? 'Y': 'N';
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['SEND_WELCOME_EACH_SESSION'] = 'N';
		}

		if (isset($params['WELCOME_MESSAGE_TEXT']))
		{
			$fields['WELCOME_MESSAGE_TEXT'] = Emoji::encode($params['WELCOME_MESSAGE_TEXT']);
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['WELCOME_MESSAGE_TEXT'] = Loc::getMessage('IMOL_CONFIG_WELCOME_MESSAGE', Array('#COMPANY_NAME#' => $companyName));
		}

		if (isset($params['OPERATOR_DATA']))
		{
			$fields['OPERATOR_DATA'] = in_array($params['OPERATOR_DATA'], Array(self::OPERATOR_DATA_PROFILE, self::OPERATOR_DATA_QUEUE, self::OPERATOR_DATA_HIDE))? $params['OPERATOR_DATA']: self::OPERATOR_DATA_PROFILE;
		}
		elseif ($mode == self::MODE_ADD)
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
		elseif ($mode == self::MODE_ADD)
		{
			$fields['NO_ANSWER_RULE'] = self::RULE_NONE;
		}

		if (isset($params['NO_ANSWER_FORM_ID']))
		{
			$fields['NO_ANSWER_FORM_ID'] = isset($formValues[$params['NO_ANSWER_FORM_ID']])? $params['NO_ANSWER_FORM_ID']: $defaultAuthFormId;
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['NO_ANSWER_FORM_ID'] = $defaultAuthFormId;
		}

		if (isset($params['NO_ANSWER_BOT_ID']))
		{
			$fields['NO_ANSWER_BOT_ID'] = intval($params['NO_ANSWER_BOT_ID']);
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['NO_ANSWER_BOT_ID'] = 0;
		}

		if (isset($params['NO_ANSWER_TEXT']))
		{
			$fields['NO_ANSWER_TEXT'] = $params['NO_ANSWER_TEXT'];
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['NO_ANSWER_TEXT'] = Loc::getMessage('IMOL_CONFIG_NO_ANSWER_NEW', Array('#COMPANY_NAME#' => $companyName));
		}

		if (isset($params['WORKTIME_ENABLE']))
		{
			$fields['WORKTIME_ENABLE'] = $params['WORKTIME_ENABLE'] == 'Y'? 'Y': 'N';
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields["WORKTIME_ENABLE"] = 'N';
		}
		if (
			isset($fields['WORKTIME_ENABLE'])
			&& $fields['WORKTIME_ENABLE'] == 'Y'
			&& !Limit::canWorkHourSettings()
		)
		{
			$fields['WORKTIME_ENABLE'] = 'N';
		}

		if (isset($params['WORKTIME_TIMEZONE']))
		{
			$fields['WORKTIME_TIMEZONE'] = $params['WORKTIME_TIMEZONE'];
		}
		elseif ($mode == self::MODE_ADD)
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
		elseif ($mode == self::MODE_ADD)
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
		elseif ($mode == self::MODE_ADD)
		{
			$fields['WORKTIME_FROM'] = "9";
			$fields['WORKTIME_TO'] = "18.30";
		}

		if (isset($params['WORKTIME_HOLIDAYS']))
		{
			$params['WORKTIME_HOLIDAYS'] = str_replace(' ', '', $params['WORKTIME_HOLIDAYS']);
			if (is_array($params['WORKTIME_HOLIDAYS']))
			{
				$params['WORKTIME_HOLIDAYS'] = implode(',', $params['WORKTIME_HOLIDAYS']);
			}
			if (preg_match("/^(\d{1,2}\.\d{1,2},?)+$/i", $params['WORKTIME_HOLIDAYS']))
			{
				$fields['WORKTIME_HOLIDAYS'] = $params['WORKTIME_HOLIDAYS'];
			}
			else
			{
				$fields['WORKTIME_HOLIDAYS'] = '';
			}
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['WORKTIME_HOLIDAYS'] = '';
		}

		if (isset($params['WORKTIME_DAYOFF_RULE']))
		{
			$fields['WORKTIME_DAYOFF_RULE'] = in_array($params["WORKTIME_DAYOFF_RULE"], Array( self::RULE_TEXT, self::RULE_NONE))? $params["WORKTIME_DAYOFF_RULE"]: self::RULE_NONE;
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['WORKTIME_DAYOFF_RULE'] = self::RULE_NONE;
		}

		if (isset($params['WORKTIME_DAYOFF_FORM_ID']))
		{
			$fields['WORKTIME_DAYOFF_FORM_ID'] = isset($formValues[$params['WORKTIME_DAYOFF_FORM_ID']])? $params['WORKTIME_DAYOFF_FORM_ID']: $defaultAuthFormId;
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['WORKTIME_DAYOFF_FORM_ID'] = $defaultAuthFormId;
		}

		if (isset($params['WORKTIME_DAYOFF_BOT_ID']))
		{
			$fields['WORKTIME_DAYOFF_BOT_ID'] = intval($params['WORKTIME_DAYOFF_BOT_ID']);
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['WORKTIME_DAYOFF_BOT_ID'] = 0;
		}

		if (isset($params['WORKTIME_DAYOFF_TEXT']))
		{
			$fields['WORKTIME_DAYOFF_TEXT'] = Emoji::encode($params['WORKTIME_DAYOFF_TEXT']);
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['WORKTIME_DAYOFF_TEXT'] = Loc::getMessage('IMOL_CONFIG_WORKTIME_DAYOFF_3', Array('#COMPANY_NAME#' => $companyName));
		}

		if (isset($params['CLOSE_RULE']))
		{
			$fields['CLOSE_RULE'] = in_array($params["CLOSE_RULE"], Array(self::RULE_TEXT, self::RULE_QUALITY, self::RULE_NONE))? $params["CLOSE_RULE"]: self::RULE_NONE;
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['CLOSE_RULE'] = self::RULE_QUALITY;
		}

		if (isset($params['CLOSE_FORM_ID']))
		{
			$fields['CLOSE_FORM_ID'] = isset($formValues[$params['CLOSE_FORM_ID']])? $params['CLOSE_FORM_ID']: $defaultRatingFormId;
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['CLOSE_FORM_ID'] = $defaultRatingFormId;
		}

		if (isset($params['CLOSE_BOT_ID']))
		{
			$fields['CLOSE_BOT_ID'] = intval($params['CLOSE_BOT_ID']);
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['CLOSE_BOT_ID'] = 0;
		}

		if (isset($params['CLOSE_TEXT']))
		{
			$fields['CLOSE_TEXT'] = Emoji::encode($params['CLOSE_TEXT']);
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['CLOSE_TEXT'] = Loc::getMessage('IMOL_CONFIG_CLOSE_TEXT_2');
		}

		if (isset($params['VOTE_MESSAGE']))
		{
			$fields['VOTE_MESSAGE'] = $params['VOTE_MESSAGE'] == 'N'? 'N': 'Y';
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['VOTE_MESSAGE'] = 'Y';
		}
		if (
			isset($params['VOTE_MESSAGE'])
			&& $fields['VOTE_MESSAGE'] === 'Y'
			&& !Limit::canUseVoteClient()
		)
		{
			$fields['VOTE_MESSAGE'] = 'N';
		}
		if (isset($params['VOTE_TIME_LIMIT']))
		{
			$fields['VOTE_TIME_LIMIT'] = (int)$params['VOTE_TIME_LIMIT'] > 0 ? (int)$params['VOTE_TIME_LIMIT']: 0;
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['VOTE_TIME_LIMIT'] = 0;
		}

		if (isset($params['VOTE_CLOSING_DELAY']))
		{
			$fields['VOTE_CLOSING_DELAY'] = $params['VOTE_CLOSING_DELAY'] == 'Y'? 'Y': 'N';
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields["VOTE_CLOSING_DELAY"] = 'N';
		}

		if (isset($params['VOTE_BEFORE_FINISH']))
		{
			$fields['VOTE_BEFORE_FINISH'] = $params['VOTE_BEFORE_FINISH'] == 'Y'? 'Y': 'N';
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields["VOTE_BEFORE_FINISH"] = 'Y';
		}

		if (isset($params['VOTE_MESSAGE_1_TEXT']))
		{
			$params['VOTE_MESSAGE_1_TEXT'] = Emoji::encode($params['VOTE_MESSAGE_1_TEXT']);
			$fields['VOTE_MESSAGE_1_TEXT'] = mb_substr($params['VOTE_MESSAGE_1_TEXT'], 0, 100);
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['VOTE_MESSAGE_1_TEXT'] = Loc::getMessage('IMOL_CONFIG_VOTE_MESSAGE_1_TEXT');
		}
		if (isset($params['VOTE_MESSAGE_1_LIKE']))
		{
			$params['VOTE_MESSAGE_1_LIKE'] = Emoji::encode($params['VOTE_MESSAGE_1_LIKE']);
			$fields['VOTE_MESSAGE_1_LIKE'] = mb_substr($params['VOTE_MESSAGE_1_LIKE'], 0, 100);
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['VOTE_MESSAGE_1_LIKE'] = Loc::getMessage('IMOL_CONFIG_VOTE_MESSAGE_1_LIKE');
		}
		if (isset($params['VOTE_MESSAGE_1_DISLIKE']))
		{
			$params['VOTE_MESSAGE_1_DISLIKE'] = Emoji::encode($params['VOTE_MESSAGE_1_DISLIKE']);
			$fields['VOTE_MESSAGE_1_DISLIKE'] = mb_substr($params['VOTE_MESSAGE_1_DISLIKE'], 0, 100);
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['VOTE_MESSAGE_1_DISLIKE'] = Loc::getMessage('IMOL_CONFIG_VOTE_MESSAGE_1_DISLIKE');
		}
		if (isset($params['VOTE_MESSAGE_2_TEXT']))
		{
			$params['VOTE_MESSAGE_2_TEXT'] = Emoji::encode($params['VOTE_MESSAGE_2_TEXT']);
			$fields['VOTE_MESSAGE_2_TEXT'] = $params['VOTE_MESSAGE_2_TEXT'];
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['VOTE_MESSAGE_2_TEXT'] = Loc::getMessage('IMOL_CONFIG_VOTE_MESSAGE_2_TEXT');
		}
		if (isset($params['VOTE_MESSAGE_2_LIKE']))
		{
			$params['VOTE_MESSAGE_2_LIKE'] = Emoji::encode($params['VOTE_MESSAGE_2_LIKE']);
			$fields['VOTE_MESSAGE_2_LIKE'] = $params['VOTE_MESSAGE_2_LIKE'];
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['VOTE_MESSAGE_2_LIKE'] = Loc::getMessage('IMOL_CONFIG_VOTE_MESSAGE_2_LIKE');
		}
		if (isset($params['VOTE_MESSAGE_2_DISLIKE']))
		{
			$params['VOTE_MESSAGE_2_DISLIKE'] = Emoji::encode($params['VOTE_MESSAGE_2_DISLIKE']);
			$fields['VOTE_MESSAGE_2_DISLIKE'] = $params['VOTE_MESSAGE_2_DISLIKE'];
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['VOTE_MESSAGE_2_DISLIKE'] = Loc::getMessage('IMOL_CONFIG_VOTE_MESSAGE_2_DISLIKE');
		}

		if (isset($params['AUTO_CLOSE_RULE']))
		{
			$fields['AUTO_CLOSE_RULE'] = in_array($params["AUTO_CLOSE_RULE"], Array(self::RULE_TEXT, self::RULE_NONE))? $params["AUTO_CLOSE_RULE"]: self::RULE_NONE;
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['AUTO_CLOSE_RULE'] = self::RULE_NONE;
		}

		if (isset($params['FULL_CLOSE_TIME']) && $params['FULL_CLOSE_TIME'] >=0)
		{
			$fields['FULL_CLOSE_TIME'] = intval($params['FULL_CLOSE_TIME']);
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['FULL_CLOSE_TIME'] = 10;
		}

		if (isset($params['AUTO_CLOSE_TIME']) && $params['AUTO_CLOSE_TIME'] >=0)
		{
			$fields['AUTO_CLOSE_TIME'] = intval($params['AUTO_CLOSE_TIME']);
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['AUTO_CLOSE_TIME'] = 14400;
		}

		if (isset($params['AUTO_CLOSE_FORM_ID']))
		{
			$fields['AUTO_CLOSE_FORM_ID'] = isset($formValues[$params['AUTO_CLOSE_FORM_ID']])? $params['AUTO_CLOSE_FORM_ID']: $defaultRatingFormId;
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['AUTO_CLOSE_FORM_ID'] = $defaultRatingFormId;
		}

		if (isset($params['AUTO_CLOSE_BOT_ID']))
		{
			$fields['AUTO_CLOSE_BOT_ID'] = intval($params['AUTO_CLOSE_BOT_ID']);
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['AUTO_CLOSE_BOT_ID'] = 0;
		}

		if (isset($params['AUTO_CLOSE_TEXT']))
		{
			$fields['AUTO_CLOSE_TEXT'] = $params['AUTO_CLOSE_TEXT'];
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['AUTO_CLOSE_TEXT'] = Loc::getMessage('IMOL_CONFIG_AUTO_CLOSE_TEXT');
		}

		if (isset($params['AUTO_EXPIRE_TIME']))
		{
			$fields['AUTO_EXPIRE_TIME'] = intval($params['AUTO_EXPIRE_TIME']);
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['AUTO_EXPIRE_TIME'] = 86400;
		}

		if (isset($params['TEMPORARY']))
		{
			$fields['TEMPORARY'] = $params['TEMPORARY'] == 'N'? 'N': 'Y';
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['TEMPORARY'] = 'N';
		}

		if (isset($params['ACTIVE']))
		{
			$fields['ACTIVE'] = $params['ACTIVE'] == 'N'? 'N': 'Y';
		}
		elseif ($mode == self::MODE_ADD)
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
		elseif ($mode == self::MODE_ADD)
		{
			$fields['KPI_FIRST_ANSWER_TIME'] = 0;
		}

		if (isset($params['KPI_FIRST_ANSWER_ALERT']))
		{
			$fields['KPI_FIRST_ANSWER_ALERT'] = $params['KPI_FIRST_ANSWER_ALERT'] == 'Y' ? 'Y' : 'N';
		}
		elseif ($mode == self::MODE_ADD)
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
		elseif ($mode == self::MODE_ADD)
		{
			$fields['KPI_FIRST_ANSWER_TEXT'] = Loc::getMessage('IMOL_CONFIG_KPI_FIRST_ANSWER_TEXT');
		}

		if (isset($params['KPI_FURTHER_ANSWER_TIME']))
		{
			$fields['KPI_FURTHER_ANSWER_TIME'] = intval($params['KPI_FURTHER_ANSWER_TIME']) > 0 ? intval($params['KPI_FURTHER_ANSWER_TIME']) : 0;
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['KPI_FURTHER_ANSWER_TIME'] = 0;
		}

		if (isset($params['KPI_FURTHER_ANSWER_ALERT']))
		{
			$fields['KPI_FURTHER_ANSWER_ALERT'] = $params['KPI_FURTHER_ANSWER_ALERT'] == 'Y' ? 'Y' : 'N';
		}
		elseif ($mode == self::MODE_ADD)
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
		elseif ($mode == self::MODE_ADD)
		{
			$fields['KPI_FURTHER_ANSWER_TEXT'] = Loc::getMessage('IMOL_CONFIG_KPI_FURTHER_ANSWER_TEXT');
		}

		if (isset($params['KPI_CHECK_OPERATOR_ACTIVITY']))
		{
			$fields['KPI_CHECK_OPERATOR_ACTIVITY'] = $params['KPI_CHECK_OPERATOR_ACTIVITY'] == 'Y' ? 'Y' : 'N';
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['KPI_CHECK_OPERATOR_ACTIVITY'] = 'N';
		}

		if (isset($params['USE_WELCOME_FORM']))
		{
			$fields['USE_WELCOME_FORM'] = $params['USE_WELCOME_FORM'] == 'Y' ? 'Y' : 'N';
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['USE_WELCOME_FORM'] = 'Y';
		}

		if (isset($params['WELCOME_FORM_ID']))
		{
			$fields['WELCOME_FORM_ID'] = (int)$params['WELCOME_FORM_ID'];
		}
		elseif ($mode == self::MODE_ADD)
		{
			if (Loader::includeModule('crm'))
			{
				$defaultWelcomeFormId = (new \Bitrix\Crm\WebForm\Preset)->getInstalledId('imol_reg');
				if ($defaultWelcomeFormId && (new \Bitrix\Crm\WebForm\Form($defaultWelcomeFormId))->isActive())
				{
					$fields['WELCOME_FORM_ID'] = (int)$defaultWelcomeFormId;
				}
				else
				{
					$fields['USE_WELCOME_FORM'] = 'N';
				}
			}
			else
			{
				$fields['USE_WELCOME_FORM'] = 'N';
			}
		}

		if (isset($params['WELCOME_FORM_DELAY']))
		{
			$fields['WELCOME_FORM_DELAY'] = $params['WELCOME_FORM_DELAY'] == 'Y' ? 'Y' : 'N';
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['WELCOME_FORM_DELAY'] = 'Y';
		}

		if (isset($params['CONFIRM_CLOSE']))
		{
			$fields['CONFIRM_CLOSE'] = $params['CONFIRM_CLOSE'] == 'Y' ? 'Y' : 'N';
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['CONFIRM_CLOSE'] = 'N';
		}

		if (isset($params['IGNORE_WELCOME_FORM_RESPONSIBLE']))
		{
			$fields['IGNORE_WELCOME_FORM_RESPONSIBLE'] = $params['IGNORE_WELCOME_FORM_RESPONSIBLE'] == 'Y' ? 'Y' : 'N';
		}
		elseif ($mode == self::MODE_ADD)
		{
			$fields['IGNORE_WELCOME_FORM_RESPONSIBLE'] = 'N';
		}

		return $fields;
	}

	/**
	 * @param array $params
	 * @return array|bool|int
	 */
	public function create($params = [])
	{
		$fields = $this->prepareFields($params);

		if ($this->userId)
		{
			$fields['MODIFY_USER_ID'] = $this->userId;
		}

		$result = Model\ConfigTable::add($fields);
		if(!$result->isSuccess())
		{
			$this->error = new BasicError(__METHOD__, 'ADD_ERROR', Loc::getMessage('IMOL_ADD_ERROR'));
			return false;
		}
		$id = (int)$result->getId();
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
			$date = new DateTime();
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
	 * @return Result
	 */
	public function update($id, array $params = []): Result
	{
		$result = new Result();

		$fields = $this->prepareFields($params, self::MODE_UPDATE);

		$orm = Model\ConfigTable::getById($id);

		if ($config = $orm->fetch())
		{
			if (isset($params['QUEUE']))
			{
				if(QueueManager::validateQueueFields($params['QUEUE']) === false)
				{
					$result->addError(new Error(
						Loc::getMessage('IMOL_ERROR_UPDATE_NO_VALID_QUEUE'),
						'IMOL_ERROR_UPDATE_NO_VALID_QUEUE',
						__METHOD__,
						['queue' => $params['QUEUE']]
					));
				}
				elseif(
					empty($params['QUEUE'])
					|| !is_array($params['QUEUE'])
				)
				{
					$result->addError(new Error(
						Loc::getMessage('IMOL_ERROR_UPDATE_EMPTY_QUEUE'),
						'IMOL_ERROR_UPDATE_EMPTY_QUEUE',
						__METHOD__
					));
				}
				elseif(QueueManager::isEmptyQueueFields($params['QUEUE']) === true)
				{
					$result->addError(new Error(
						Loc::getMessage('IMOL_ERROR_UPDATE_EMPTY_DEPARTMENT_QUEUE'),
						'IMOL_ERROR_UPDATE_EMPTY_DEPARTMENT_QUEUE',
						__METHOD__,
						['queue' => $params['QUEUE']]
					));
				}
			}

			if($result->isSuccess())
			{
				if (!isset($params['SKIP_MODIFY_MARK']))
				{
					$fields['DATE_MODIFY'] = new DateTime();
					if ($this->userId)
					{
						$fields['MODIFY_USER_ID'] = $this->userId;
					}
				}

				$resultConfigTableUpdate = Model\ConfigTable::update($id, $fields);

				if($resultConfigTableUpdate->isSuccess())
				{
					if (
						isset($fields['ACTIVE'])
						&& $fields['ACTIVE'] !== $config['ACTIVE']
					)
					{
						$eventData = [
							'line' => $id,
							'active' => $fields['ACTIVE']
						];
						$event = new Main\Event('imopenlines', self::EVENT_AFTER_IMOPENLINE_ACTIVE_CHANGE, $eventData);
						$event->send();
					}

					if (
						isset($fields['QUEUE_TYPE'])
						&& $fields['QUEUE_TYPE'] !== $config['QUEUE_TYPE']
					)
					{
						$eventData = [
							'line' => $id,
							'typeBefore' => $config['QUEUE_TYPE'],
							'typeAfter' => $fields['QUEUE_TYPE']
						];
						$event = new Main\Event('imopenlines', self::EVENT_IMOPENLINE_CHANGE_QUEUE_TYPE, $eventData);
						$event->send();
					}

					if(
						isset($params['DEFAULT_OPERATOR_DATA'])
						&& !empty($config['DEFAULT_OPERATOR_DATA']['AVATAR_ID'])
						&& (
							empty($params['DEFAULT_OPERATOR_DATA']['AVATAR_ID'])
							|| $config['DEFAULT_OPERATOR_DATA']['AVATAR_ID'] != $params['DEFAULT_OPERATOR_DATA']['AVATAR_ID']
						)
					)
					{
						\CFile::Delete($config['DEFAULT_OPERATOR_DATA']['AVATAR_ID']);
					}

					if (
						isset($params['QUEUE'])
						&& is_array($params['QUEUE'])
					)
					{
						if(!isset($params['QUEUE_USERS_FIELDS']))
						{
							$params['QUEUE_USERS_FIELDS'] = false;
						}
						$queueManager = new QueueManager($id);
						$queueManager->compatibleUpdate($params['QUEUE'], $params['QUEUE_USERS_FIELDS']);
					}

					if (isset($fields['QUICK_ANSWERS_IBLOCK_ID']))
					{
						if(
							$config['QUICK_ANSWERS_IBLOCK_ID'] != $fields['QUICK_ANSWERS_IBLOCK_ID']
							&& $config['QUICK_ANSWERS_IBLOCK_ID'] > 0
						)
						{
							ListsDataManager::updateIblockRights($config['QUICK_ANSWERS_IBLOCK_ID']);
						}

						if($fields['QUICK_ANSWERS_IBLOCK_ID'] > 0)
						{
							ListsDataManager::updateIblockRights($fields['QUICK_ANSWERS_IBLOCK_ID']);
						}
					}

					$sendUpdate = false;
					$sendDelete = false;
					$lineName = $config['LINE_NAME'];
					$queueType = $config['QUEUE_TYPE'];

					if (
						isset($fields['ACTIVE'])
						&& $config['ACTIVE'] !== $fields['ACTIVE']
					)
					{
						if ($fields['ACTIVE'] === 'Y')
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
						if (
							isset($fields['LINE_NAME'])
							&& $config['LINE_NAME'] !== $fields['LINE_NAME']
						)
						{
							$lineName = $fields['LINE_NAME'];
							$sendUpdate = true;
						}
						if (
							isset($fields['QUEUE_TYPE'])
							&& $config['QUEUE_TYPE'] !== $fields['QUEUE_TYPE']
						)
						{
							$sendUpdate = true;
							$queueType = $fields['QUEUE_TYPE'];
						}
					}

					if ($sendUpdate)
					{
						self::sendUpdateForQueueList([
							'ID' => $id,
							'NAME' => $lineName,
							'SESSION_PRIORITY' => $fields['SESSION_PRIORITY'] ?? $config['SESSION_PRIORITY'],
							'QUEUE_TYPE' => $queueType
						]);
					}
					elseif ($sendDelete)
					{
						self::sendUpdateForQueueList([
							'ID' => $id,
							'ACTION' => 'DELETE',
							'SESSION_PRIORITY' => 0
						]);
					}
				}
				else
				{
					$result->addError(new Error(
						Loc::getMessage('IMOL_UPDATE_ERROR'),
						'IMOL_ERROR_UPDATE_ERROR',
						__METHOD__,
						['id' => $id, 'fields' => $fields]
					));
				}
			}
		}
		else
		{
			$result->addError(new Error(
				Loc::getMessage('IMOL_ERROR_UPDATE_NO_LOAD_LINE'),
				'IMOL_ERROR_UPDATE_NO_LOAD_LINE',
				__METHOD__,
				['idLine' => $id]
			));
		}

		return $result;
	}

	/**
	 * @param $id
	 * @return bool
	 */
	public function delete($id)
	{
		$id = (int)$id;
		if (!$id)
		{
			return false;
		}

		$sessList = Model\ConfigTable::getById($id);
		if (!($config = $sessList->fetch()))
		{
			return false;
		}

		Model\ConfigTable::delete($id);
		ConfigStatistic::delete($id);

		if($config['QUICK_ANSWERS_IBLOCK_ID'] > 0)
		{
			ListsDataManager::updateIblockRights($config['QUICK_ANSWERS_IBLOCK_ID']);
		}

		$sessList = Model\QueueTable::getList([
			'select' => ['ID'],
			'filter' => ['=CONFIG_ID' => $id]
		]);
		while ($row = $sessList->fetch())
		{
			Model\QueueTable::delete($row['ID']);
		}

		$raw = ConfigQueueTable::getList([
			'select' => ['ID'],
			'filter' => ['=CONFIG_ID' => $id]
		]);
		while ($row = $raw->fetch())
		{
			ConfigQueueTable::delete($row['ID']);
		}

		$this->deleteAllAutomaticMessage($id);

		$sessList = Model\SessionTable::getList([
			'select' => ['ID', 'CHAT_ID', 'CLOSED'],
			'filter' => ['=CONFIG_ID' => $id]
		]);
		while ($session = $sessList->fetch())
		{
			if ($session['CLOSED'] != 'Y')
			{
				Im::chatHide($session['CHAT_ID']);
			}

			Session::deleteSession($session['ID']);
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
		catch (\Bitrix\Main\SystemException $e)
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
	public function setActive($id, $status = true): bool
	{
		$result = false;

		$resultUpdate = $this->update($id, ['ACTIVE' => $status? 'Y': 'N']);

		if($resultUpdate->isSuccess())
		{
			$result = true;
		}

		return $result;
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

	private static function canDoOperation($id, $entity, $action, $userId = null)
	{
		if (isset(self::$cacheOperation[$id][$entity][$action]))
		{
			return self::$cacheOperation[$id][$entity][$action];
		}

		$userId = $userId ?? Security\Helper::getCurrentUserId();
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
		elseif (empty($allowedUserIds))
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

	public static function canViewLine($id, $userId = null)
	{
		return self::canDoOperation($id, Security\Permissions::ENTITY_LINES, Security\Permissions::ACTION_VIEW, $userId);
	}

	public static function canEditLine($id, $userId = null)
	{
		return self::canDoOperation($id, Security\Permissions::ENTITY_LINES, Security\Permissions::ACTION_MODIFY, $userId);
	}

	public static function canEditConnector($id, $userId = null)
	{
		return self::canDoOperation($id, Security\Permissions::ENTITY_CONNECTORS, Security\Permissions::ACTION_MODIFY, $userId);
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
			return (
				self::canDoOperation($id, Security\Permissions::ENTITY_JOIN, Security\Permissions::ACTION_PERFORM)
				|| \Bitrix\ImOpenLines\Crm\Common::hasAccessToEntity($crmEntityType, $crmEntityId)
			);
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
	 * @param int $userId
	 * @return bool|mixed
	 */
	public static function canVoteAsHead($id, $checkLimit = true, $userId = null)
	{
		$result = false;

		if (
			$checkLimit === false ||
			Limit::canUseVoteHead()
		)
		{
			$result =  self::canDoOperation($id, Security\Permissions::ENTITY_VOTE_HEAD, Security\Permissions::ACTION_PERFORM, $userId);
		}

		return $result;
	}

	/**
	 * @param $id
	 * @param bool $withQueue
	 * @param bool $showOffline
	 * @param bool $withConfigQueue
	 * @return array|bool
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

				if (!Limit::canUseVoteClient())
				{
					$config['VOTE_MESSAGE'] = 'N';
				}

				if (!Limit::canWorkHourSettings())
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
					'ACTIVE' => $config['ACTIVE'],
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
						'ACTIVE' => $config['ACTIVE'],
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
				$resultData['add'][] = $resultAdd->getId();
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

		$checkPermission = false;
		$permissionAllowedUsers = [];
		if (isset($options['CHECK_PERMISSION']))
		{
			$permission = \Bitrix\ImOpenlines\Security\Permissions::createWithUserId($this->userId);

			$permissionAllowedUsers = \Bitrix\ImOpenlines\Security\Helper::getAllowedUserIds(
				$this->userId,
				$permission->getPermission(
					\Bitrix\ImOpenlines\Security\Permissions::ENTITY_LINES,
					$options['CHECK_PERMISSION']
				)
			);

			if (is_array($permissionAllowedUsers))
			{
				$checkPermission = true;
				$permissionAccessConfig = [];

				if (!empty($permissionAllowedUsers))
				{
					$orm = \Bitrix\ImOpenlines\Model\QueueTable::getList([
						'filter' => [
							'=USER_ID' => $permissionAllowedUsers
						]
					]);
					while ($row = $orm->fetch())
					{
						$permissionAccessConfig[$row['CONFIG_ID']] = true;
					}
				}
			}
		}


		$configs = [];
		$orm = Model\ConfigTable::getList($params);
		while ($config = $orm->fetch())
		{
			if (
				$checkPermission
				&& !isset($permissionAccessConfig[$config['ID']])
				&& !in_array($config['MODIFY_USER_ID'], $permissionAllowedUsers)
			)
			{
				continue;
			}

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
				$ormConfigQueue = ConfigQueueTable::getList([
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

	/**
	 * Determines whether the user is an operator in at least one open line.
	 *
	 * @param int $userId
	 * @return bool
	 */
	public static function isOperator(int $userId): bool
	{
		$result = false;

		$cache = new Queue\Cache();

		$cache->setUserId($userId);

		if ($cache->initCacheIsOperator())
		{
			$result = $cache->getVarsIsOperator();
		}
		else
		{
			$cache->startCacheIsOperator();

			$row = Model\QueueTable::getList([
				'select' => ['ID'],
				'filter' => ['USER_ID' => $userId],
				'limit' => 1
			]);

			if ($row->fetch())
			{
				$result = true;
			}

			$cache->endCacheIsOperator($result);
		}


		return $result;
	}

	/**
	 * @param int $userId
	 * @param bool $emptyIsNotOperator
	 * @return array
	 */
	public static function getQueueList(int $userId = 0, bool $emptyIsNotOperator = true): array
	{
		$result = [];
		$isOperator = 0;

		if($userId > 0)
		{
			$isOperator = self::isOperator($userId);
		}

		if(
			$isOperator === true
			|| $emptyIsNotOperator === false
		)
		{
			$rawConfigs = Model\ConfigTable::getList([
				'select' => [
					'ID',
					'NAME' => 'LINE_NAME',
					'PRIORITY' => 'SESSION_PRIORITY',
					'QUEUE_TYPE'
				],
				'filter' => ['=ACTIVE' => 'Y'],
				'order' => ['ID' => 'ASC'],
				'cache' => ['ttl' => 86400]
			]);
			while ($rowConfigs = $rawConfigs->fetch())
			{
				$rowConfigs['TRANSFER_COUNT'] = \CUserCounter::GetValue($userId, 'imopenlines_transfer_count_' . $rowConfigs['ID']);
				$result[] = $rowConfigs;
			}
		}

		return $result;
	}

	/**
	 * @param array $select
	 * @return array
	 */
	public static function getAllLinesSettings(array $select): array
	{
		$rawConfigs = Model\ConfigTable::getList([
			'select' => array_merge(['ID'], $select),
			'filter' => ['=ACTIVE' => 'Y'],
			'order' => ['ID' => 'ASC'],
			'cache' => ['ttl' => 86400]
		]);

		return $rawConfigs->fetchAll();
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

		$userList = [];
		$orm = \Bitrix\ImOpenlines\Model\QueueTable::getList([
			'select' => [
				'USER_ID',
			 ],
			'filter' => [
				'=USER.ACTIVE' => 'Y',
				'=USER.IS_ONLINE' => 'Y',
			],
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'ASC'
			]
		]);
		while ($row = $orm->fetch())
		{
			$userList[] = $row['USER_ID'];
		}

		\Bitrix\Pull\Event::add($userList, Array(
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
				new ExpressionField('CNT', 'COUNT(*)')
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
