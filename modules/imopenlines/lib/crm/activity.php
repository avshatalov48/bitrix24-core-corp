<?php

namespace Bitrix\ImOpenLines\Crm;

use Bitrix\ImOpenLines\Crm;
use Bitrix\ImOpenLines\Log;
use Bitrix\ImOpenLines\Error;
use Bitrix\ImOpenLines\Result;
use Bitrix\ImOpenLines\Session;

use Bitrix\Crm\Activity\StatisticsStatus;
use Bitrix\Crm\Integration\Channel\IMOpenLineTracker;

use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
Crm::loadMessages();

class Activity
{
	public const ERROR_IMOL_ACTIVITY_NO_REQUIRED_PARAMETERS = 'ERROR IMOPENLINES ACTIVITY NO REQUIRED PARAMETERS';

	/**
	 * @param array $params
	 * @return Result
	 */
	public static function add(array $params = []): Result
	{
		$result = new Result;

		if (Loader::includeModule('crm'))
		{
			if(!empty($params))
			{
				$addFields = [
					'TYPE_ID' => \CCrmActivityType::Provider,
					'PROVIDER_ID' => \Bitrix\Crm\Activity\Provider\OpenLine::getId(),
					'NOTIFY_TYPE' => \CCrmActivityNotifyType::None,
					'RESULT_MARK' => \Bitrix\Crm\Activity\StatisticsMark::None,
				];

				if (!empty($params['LINE_ID']))
				{
					$addFields['PROVIDER_TYPE_ID'] = $params['LINE_ID'];
				}
				if (!empty($params['NAME']))
				{
					$addFields['SUBJECT'] = $params['NAME'];
				}
				if (!empty($params['SESSION_ID']))
				{
					$addFields['ASSOCIATED_ENTITY_ID'] = $params['SESSION_ID'];
					$addFields['ORIGIN_ID'] = 'IMOL_' . $params['SESSION_ID'];
				}
				if (empty($params['START_TIME']))
				{
					$addFields['START_TIME'] = new \Bitrix\Main\Type\DateTime();
				}
				else
				{
					$addFields['START_TIME'] = $params['START_TIME'];
				}
				if (empty($params['COMPLETED']))
				{
					$addFields['COMPLETED'] = 'N';
				}
				else
				{
					$addFields['COMPLETED'] = $params['COMPLETED'] === 'Y' ? 'Y' : 'N';
				}
				if (empty($params['MODE']))
				{
					$addFields['DIRECTION'] = \CCrmActivityDirection::Incoming;
					$addFields['RESULT_STATUS'] = \Bitrix\Crm\Activity\StatisticsStatus::Unanswered;
				}
				else
				{
					$addFields['DIRECTION'] = $params['MODE'] === Session::MODE_INPUT ? \CCrmActivityDirection::Incoming : \CCrmActivityDirection::Outgoing;
					$addFields['RESULT_STATUS'] = $params['MODE'] === Session::MODE_OUTPUT ? \Bitrix\Crm\Activity\StatisticsStatus::Answered : \Bitrix\Crm\Activity\StatisticsStatus::Unanswered;
				}
				if (!empty($params['BINDINGS']))
				{
					$addFields['BINDINGS'] = $params['BINDINGS'];
				}
				if (!empty($params['SETTINGS']))
				{
					$addFields['SETTINGS'] = $params['SETTINGS'];
				}
				if (!empty($params['OPERATOR_ID']))
				{
					$addFields['AUTHOR_ID'] = $params['OPERATOR_ID'];
					$addFields['RESPONSIBLE_ID'] = $params['OPERATOR_ID'];
				}
				if (!empty($params['USER_CODE']))
				{
					$addFields['PROVIDER_PARAMS'] = ['USER_CODE' => $params['USER_CODE']];
				}
				if (!empty($params['CONNECTOR_ID']))
				{
					$addFields['RESULT_SOURCE_ID'] = $params['CONNECTOR_ID'];
				}
				if (
					!empty($params['ENTITES'])
					&& is_array($params['ENTITES'])
					&& !empty($params['USER_CODE'])
				)
				{
					foreach ($params['ENTITES'] as $entity)
					{
						$addFields['COMMUNICATIONS'][] = [
							'ID' => 0,
							'TYPE' => 'IM',
							'VALUE' => 'imol|' . $params['USER_CODE'],
							'ENTITY_ID' => $entity['ENTITY_ID'],
							'ENTITY_TYPE_ID' => $entity['ENTITY_TYPE_ID']
						];
					}
				}

				if ($addFields['DIRECTION'] === \CCrmActivityDirection::Incoming)
				{
					$addFields['IS_INCOMING_CHANNEL'] = 'Y';

					(new Event('imopenlines', 'OnImOpenLineRegisteredInCrm', $addFields))->send();
				}

				$id = \CCrmActivity::Add($addFields, false, true, ['REGISTER_SONET_EVENT' => true]);

				if ($id)
				{
					$result->setResult($id);

					IMOpenLineTracker::getInstance()->registerActivity($id, ['ORIGIN_ID' => $params['LINE_ID'], 'COMPONENT_ID' => $params['CONNECTOR_ID']]);

					Log::write($id, 'CRM ACTIVITY CREATED');
				}
				else
				{
					if(\CAllCrmActivity::GetErrorCount() > 0)
					{
						$errorMessage = \CAllCrmActivity::GetLastErrorMessage();
						$result->addError(new Error($errorMessage, Crm::ERROR_IMOL_CRM_ACTIVITY, __METHOD__));

						Log::write($errorMessage, 'CRM ACTIVITY ERROR');
					}
				}
			}
			else
			{
				$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_ACTIVITY_NO_REQUIRED_PARAMETERS'), self::ERROR_IMOL_ACTIVITY_NO_REQUIRED_PARAMETERS, __METHOD__));
			}
		}
		else
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NOT_LOAD_CRM'), Crm::ERROR_IMOL_NOT_LOAD_CRM, __METHOD__));
		}

		return $result;
	}

	/**
	 * @param $id
	 * @param array $params
	 * @return Result
	 */
	public static function update($id, array $params = []): Result
	{
		$result = new Result;

		if (Loader::includeModule('crm'))
		{
			if (
				!empty($id)
				&& $id > 0
				&& !empty($params)
			)
			{
				$updateDate = [];

				if (!empty($params['ANSWERED']))
				{
					$updateDate['RESULT_STATUS'] = $params['ANSWERED'] === 'Y' ? StatisticsStatus::Answered : StatisticsStatus::Unanswered;
				}

				if (!empty($params['COMPLETED']))
				{
					$updateDate['COMPLETED'] = $params['COMPLETED'] === 'N' ? 'N' : 'Y';
				}

				if (!empty($params['DATE_CLOSE']))
				{
					$updateDate['END_TIME'] = $params['DATE_CLOSE'];
				}

				if(
					!empty($params['OPERATOR_ID'])
					&& $params['OPERATOR_ID'] > 0
				)
				{
					$updateDate['RESPONSIBLE_ID'] = $params['OPERATOR_ID'];
				}

				if (!empty($params['EDITOR_ID']))
				{
					$updateDate['EDITOR_ID'] = $params['EDITOR_ID'];
				}

				if (isset($params['USER_CODE']))
				{
					$updateDate['PROVIDER_PARAMS'] = ['USER_CODE' => $params['USER_CODE']];
				}

				if (!empty($updateDate))
				{
					$updateOptions = ['REGISTER_SONET_EVENT' => true];

					$activityFields = \CCrmActivity::GetByID($id, false);
					if (isset($activityFields['RESPONSIBLE_ID']) && $activityFields['RESPONSIBLE_ID'] > 0)
					{
						$updateOptions['CURRENT_USER'] = $activityFields['RESPONSIBLE_ID'];
					}

					$resultUpdate = \CCrmActivity::Update($id, $updateDate, false, true, $updateOptions);
					if ($resultUpdate == false)
					{
						if (\CAllCrmActivity::GetErrorCount() > 0)
						{
							$errorMessage = \CAllCrmActivity::GetLastErrorMessage();
							$result->addError(new Error($errorMessage, Crm::ERROR_IMOL_CRM_ACTIVITY, __METHOD__));

							Log::write($errorMessage, 'CRM ACTIVITY ERROR');
						}
					}
				}
			}
			else
			{
				$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_ACTIVITY_NO_REQUIRED_PARAMETERS'), self::ERROR_IMOL_ACTIVITY_NO_REQUIRED_PARAMETERS, __METHOD__));
			}
		}
		else
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NOT_LOAD_CRM'), Crm::ERROR_IMOL_NOT_LOAD_CRM, __METHOD__));
		}

		return $result;
	}
}
