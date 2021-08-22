<?php
namespace Bitrix\ImConnector\Connectors;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Im\User;

use Bitrix\ImConnector\Error;
use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\InteractiveMessage;

/**
 * Class Yandex
 * @package Bitrix\ImConnector\Connectors
 */
class Network extends Base
{
	protected const MODULE_ID_IMOPENLINES = 'imopenlines';
	protected const EXTERNAL_AUTH_ID = 'imconnector';

	//region Input

	/**
	 * @param $message
	 * @param $line
	 * @return Result
	 */
	public function processingInputNewMessage($message, $line): Result
	{
		$result = new Result();
		$userId = 0;

		if (!isset($message['USER']))
		{
			$result->addError(new Error(
				'User data not transmitted',
				'ERROR_IMCONNECTOR_NOT_TRANSMITTED_USER_DATA',
				__METHOD__,
				$message
			));
		}

		if ($message['MESSAGE_TYPE'] !== 'P')
		{
			$result->addError(new Error(
				'Invalid message type',
				'ERROR_IMCONNECTOR_INVALID_MESSAGE_TYPE',
				__METHOD__,
				$message
			));
		}

		if ($result->isSuccess())
		{
			$userId = $this->getUserId($message['USER']);

			if (empty($userId))
			{
				$result->addError(new Error(
					'Failed to create or update user',
					'ERROR_IMCONNECTOR_FAILED_USER',
					__METHOD__,
					$message
				));
			}
		}

		if ($result->isSuccess())
		{
			$messageData = [
				'id' => $message['MESSAGE_ID'],
				'date' => '',
				'text' => $message['MESSAGE_TEXT'],
				'fileLinks' => $message['FILES'],
				'attach' => $message['ATTACH'],
				'params' => $message['PARAMS'],
			];

			$message['USER']['FULL_NAME'] = \CUser::FormatName(
				\CSite::GetNameFormat(false),
				$message['USER'],
				true,
				false
			);

			$extraFields = [];
			$description =
				'[B]'
				. Loc::getMessage('IMCONNECTOR_CONNECTOR_NETWORK_NAME')
				. '[/B]: '
				. $message['USER']['FULL_NAME']
				. '[BR]';
			if (
				isset($message['USER']['WORK_POSITION'])
				&& !empty($message['USER']['WORK_POSITION'])
			)
			{
				$description .=
					'[B]'
					. Loc::getMessage('IMCONNECTOR_CONNECTOR_NETWORK_POST')
					. '[/B]: '
					. $message['USER']['WORK_POSITION']
					. '[BR]';
			}
			if (
				isset($message['USER']['EMAIL'])
				&& !empty($message['USER']['EMAIL'])
			)
			{
				$description .= '[B]' . Loc::getMessage('IMCONNECTOR_CONNECTOR_NETWORK_EMAIL') . '[/B]: '.$message['USER']['EMAIL'] . '[BR]';
			}
			if (
				isset($message['USER']['TARIFF_LEVEL'])
				&& !empty($message['USER']['TARIFF_LEVEL'])
			)
			{
				$description .=
					'[BR][B]'
					. Loc::getMessage('IMCONNECTOR_CONNECTOR_NETWORK_TARIFF_LEVEL')
					. '[/B]: '
					. Loc::getMessage(
						'IMCONNECTOR_CONNECTOR_NETWORK_TARIFF_LEVEL_'
						. mb_strtoupper($message['USER']['TARIFF_LEVEL'])
					)
					. '[BR]';
			}
			if (
				isset($message['USER']['TARIFF'])
				&& !empty($message['USER']['TARIFF'])
			)
			{
				if (!empty($message['USER']['TARIFF_NAME']))
				{
					$description .=
						'[B]'
						. Loc::getMessage('IMCONNECTOR_CONNECTOR_NETWORK_TARIFF')
						. '[/B]: '
						. $message['USER']['TARIFF_NAME']
						. ' ('
						. $message['USER']['TARIFF']
						. ')[BR]';
				}
				else
				{
					$description .=
						'[B]'
						. Loc::getMessage('IMCONNECTOR_CONNECTOR_NETWORK_TARIFF')
						. '[/B]: '
						. $message['USER']['TARIFF']
						. '[BR]';
				}

				$extraFields['EXTRA_TARIFF'] = $message['USER']['TARIFF'];
			}
			if (
				isset($message['USER']['USER_LEVEL'])
				&& in_array($message['USER']['USER_LEVEL'], ['ADMIN', 'INTEGRATOR'])
			)
			{
				$description .=
					'[B]'
					. Loc::getMessage('IMCONNECTOR_CONNECTOR_NETWORK_USER_LEVEL')
					. '[/B]: '
					. Loc::getMessage('IMCONNECTOR_CONNECTOR_NETWORK_USER_LEVEL_' . $message['USER']['USER_LEVEL'])
					. '[BR]';
				$extraFields['EXTRA_USER_LEVEL'] = $message['USER']['USER_LEVEL'];
			}
			if (
				isset($message['USER']['PORTAL_TYPE'])
				&& in_array($message['USER']['PORTAL_TYPE'], ['PRODUCTION', 'STAGE', 'ETALON'])
			)
			{
				$description .=
					'[B]'
					. Loc::getMessage('IMCONNECTOR_CONNECTOR_NETWORK_PORTAL_TYPE')
					. '[/B]: '
					. Loc::getMessage('IMCONNECTOR_CONNECTOR_NETWORK_PORTAL_TYPE_' . $message['USER']['PORTAL_TYPE'])
					. '[BR]';
				$extraFields['EXTRA_PORTAL_TYPE'] = $message['USER']['PORTAL_TYPE'];
			}
			if (
				isset($message['USER']['REGISTER'])
				&& !empty($message['USER']['REGISTER'])
			)
			{
				$daysAgo = (int)((time() - $message['USER']['REGISTER']) / 60 / 60 / 24);
				$daysAgo = ($daysAgo > 0? $daysAgo: 1);
				$description .=
					'[B]'
					. Loc::getMessage('IMCONNECTOR_CONNECTOR_NETWORK_REGISTER')
					. '[/B]: '
					. $daysAgo
					. '[BR]';
				$extraFields['EXTRA_REGISTER'] = $daysAgo;
			}
			if (
				isset($message['USER']['DEMO'])
				&& !empty($message['USER']['DEMO'])
			)
			{
				$daysAgo = (int)((time() - $message['USER']['DEMO']) / 60 / 60 / 24);
				$daysAgo = ($daysAgo > 0? $daysAgo: 1);
				$description .=
					'[B]'
					. Loc::getMessage('IMCONNECTOR_CONNECTOR_NETWORK_DEMO')
					. '[/B]: '
					. $daysAgo
					. '[BR]';
			}
			if (
				isset($message['USER']['GEO_DATA'])
				&& !empty($message['USER']['GEO_DATA'])
			)
			{
				$description .=
					'[B]'
					. Loc::getMessage('IMCONNECTOR_CONNECTOR_NETWORK_GEO_DATA')
					. '[/B]: '
					. $message['USER']['GEO_DATA']
					. '[BR]';
			}
			$description .=
				'[B]'
				. Loc::getMessage('IMCONNECTOR_CONNECTOR_NETWORK_WWW')
				. '[/B]: '
				. $message['USER']['PERSONAL_WWW'];
			$extraFields['EXTRA_URL'] = $message['USER']['PERSONAL_WWW'];

			$result->setResult([
				'user' => $userId,
				'chat' => [
					'id' => $message['GUID'],
					'description' => $description
				],
				'message' => $messageData,
				'extra' => $extraFields
			]);
		}

		return $result;
	}

	/**
	 * @param $message
	 * @param $line
	 * @return Result
	 */
	public function processingInputUpdateMessage($message, $line): Result
	{
		$result = $this->processingInputBase($message, $line);

		if ($result->isSuccess())
		{
			$resultData = $result->getResult();

			$resultData['message'] = [
				'id' => $message['MESSAGE_ID'],
				'date' => '',
				'text' => $message['MESSAGE_TEXT']
			];

			$result->setResult($resultData);
		}

		return $result;
	}

	/**
	 * @param $message
	 * @param $line
	 * @return Result
	 */
	public function processingInputDelMessage($message, $line): Result
	{
		$result = $this->processingInputBase($message, $line);

		if ($result->isSuccess())
		{
			$resultData = $result->getResult();

			$resultData['message'] = [
				'id' => $message['MESSAGE_ID'],
			];

			$result->setResult($resultData);
		}

		return $result;
	}

	/**
	 * @param $message
	 * @param $line
	 * @return Result
	 */
	public function processingInputTypingStatus($message, $line): Result
	{
		return $this->processingInputBase($message, $line);
	}

	/**
	 * @param $message
	 * @param $line
	 * @return Result
	 */
	protected function processingInputBase($message, $line): Result
	{
		$result = new Result();
		$userId = $this->getUserId($message['USER']);

		if (empty($userId))
		{
			$result->addError(new Error(
				'Failed to create or update user',
				'ERROR_IMCONNECTOR_FAILED_USER',
				__METHOD__,
				$message
			));
		}

		if ($result->isSuccess())
		{
			$result->setResult([
				'user' => $userId,
				'chat' => [
					'id' => $message['GUID']
				]
			]);
		}

		return $result;
	}

	/**
	 * @param $params
	 * @param $line
	 * @return Result
	 */
	public function processingInputCommandKeyboard($params, $line): Result
	{
		$result = new Result();
		$userId = 0;

		if (
			!isset($params['USER'])
			&& $result->isSuccess()
		)
		{
			$result->addError(new Error(
				'User data not transmitted',
				'ERROR_IMCONNECTOR_NOT_TRANSMITTED_USER_DATA',
				__METHOD__,
				$params
			));
		}

		if ($result->isSuccess())
		{
			$userId = $this->getUserId($params['USER']);

			if (empty($userId))
			{
				$result->addError(new Error(
					'Failed to create or update user',
					'ERROR_IMCONNECTOR_FAILED_USER',
					__METHOD__,
					$params
				));
			}
		}

		if ($result->isSuccess())
		{
			$interactiveMessage = InteractiveMessage\Input::init('network');
			$resultProcessing = $interactiveMessage->processingCommandKeyboard($params['COMMAND'], $params['COMMAND_PARAMS']);

			if (!$resultProcessing->isSuccess())
			{
				$result->addErrors($resultProcessing->getErrors());
			}
		}

		$result->setResult([
			'PARAMS' => $params,
			'USER_ID' => $userId,
		]);

		return $result;
	}

	/**
	 * @param $params
	 * @param $line
	 * @return Result
	 */
	public function processingInputSessionVote($params, $line): Result
	{
		$result = new Result();

		if (!Loader::includeModule('im'))
		{
			$result->addError(new Error(
				'Failed to load the im module',
				'ERROR_IMCONNECTOR_FAILED_LOAD_IM',
				__METHOD__
			));
		}

		if (!isset($params['USER']))
		{
			$result->addError(new Error(
				'User data not transmitted',
				'ERROR_IMCONNECTOR_NOT_TRANSMITTED_USER_DATA',
				__METHOD__,
				$params
			));
		}

		if ($result->isSuccess())
		{
			$userId = $this->getUserId($params['USER']);

			if (empty($userId))
			{
				$result->addError(new Error(
					'Failed to create or update user',
					'ERROR_IMCONNECTOR_FAILED_USER',
					__METHOD__,
					$params
				));
			}
		}

		$messageParams['IMOL_VOTE'] = 0;

		if ($result->isSuccess())
		{
			$messageParamService = ServiceLocator::getInstance()->get('Im.Services.MessageParam');
			if ($messageParamService instanceof \Bitrix\Im\Services\MessageParam)
			{
				$messageParams = $messageParamService->getParams((int)$params['MESSAGE_ID']);
			}

			if ($messageParams['IMOL_VOTE'] != $params['SESSION_ID'])
			{
				$result->addError(new Error(
					'Voting for the wrong session',
					'ERROR_IMCONNECTOR_VOTING_FOR_WRONG_SESSION',
					__METHOD__,
					$params
				));
			}
		}

		$result->setResult(
			[
				'PARAMS' => $params,
				'MESSAGE_PARAMS' => $messageParams,
			]
		);

		return $result;
	}

	//endregion

	//region Output

	/**
	 * @param array $message
	 * @param $line
	 * @return array
	 */
	public function sendMessageProcessing(array $message, $line): array
	{
		$isActiveKeyboard = false;

		if (
			!empty($message['im']['chat_id']) &&
			$message['im']['chat_id'] > 0
		)
		{
			//Processing for native messages
			$interactiveMessage = InteractiveMessage\Output::getInstance($message['im']['chat_id'], ['connectorId' => 'network']);
			$message['message'] = $interactiveMessage->nativeMessageProcessing($message['message']);

			$isActiveKeyboard = $interactiveMessage->isLoadedKeyboard();
		}

		$result = [
			'LINE_ID' => $line,
			'GUID' => $message['chat']['id'],
			'MESSAGE_ID' => $message['im']['message_id'],
			'MESSAGE_TEXT' => $message['message']['text'],
			'FILES' => $message['message']['files'],
			'ATTACH' => $message['message']['attachments'],
			'PARAMS' => $message['message']['params']
		];

		if ($isActiveKeyboard === true)
		{
			$result['KEYBOARD'] = $message['message']['keyboardData'];
		}

		if (!empty($message['user']))
		{
			$result['USER'] = [
				'ID' => $message['user']['ID'],
				'NAME' => $message['user']['FIRST_NAME'],
				'LAST_NAME' => $message['user']['LAST_NAME'],
				'PERSONAL_GENDER' => $message['user']['GENDER'],
				'PERSONAL_PHOTO' => $message['user']['AVATAR']
			];
		}

		return $result;
	}

	/**
	 * @param array $message
	 * @param $line
	 * @return array
	 */
	public function updateMessageProcessing(array $message, $line): array
	{
		return [
            "LINE_ID" => $line,
            "GUID" => $message['chat']['id'],
            "MESSAGE_ID" => $message['im']['message_id'],
            "MESSAGE_TEXT" => $message['message']['text'],
            "CONNECTOR_MID" => $message['message']['id'][0],
            "FILES" => $message['message']['files'],
            "ATTACH" => $message['message']['attachments'],
            "PARAMS" => $message['message']['params'],
        ];
	}

	/**
	 * @param array $message
	 * @param $line
	 * @return array
	 */
	public function deleteMessageProcessing(array $message, $line): array
	{
		return [
            "LINE_ID" => $line,
            "GUID" => $message['chat']['id'],
            "MESSAGE_ID" => $message['im']['message_id'],
            "CONNECTOR_MID" => is_array($message['message']['id'])? $message['message']['id'][0]: $message['message']['id']
        ];
	}

	//endregion

	//region Tools

	/**
	 * @param $params
	 * @param bool $createUser
	 * @return false|int|mixed|string
	 */
	public function getUserId($params, bool $createUser = true)
	{
		$userId = 0;

		if (Loader::includeModule('im'))
		{
			$orm = UserTable::getList([
				'select' => [
					'ID',
					'NAME',
					'LAST_NAME',
					'PERSONAL_GENDER',
					'PERSONAL_PHOTO',
					'PERSONAL_WWW',
					'EMAIL'
				],
				'filter' => [
					'=EXTERNAL_AUTH_ID' => self::EXTERNAL_AUTH_ID,
					'=XML_ID' => 'network|' . $params['UUID']
				],
				'limit' => 1
			]);

			if ($userFields = $orm->fetch())
			{
				$userId = $userFields['ID'];

				$updateFields = [];
				if (
					!empty($params['NAME'])
					&& $params['NAME'] !== $userFields['NAME']
				)
				{
					$updateFields['NAME'] = $params['NAME'];
				}
				if (
					isset($params['LAST_NAME'])
					&& $params['LAST_NAME'] !== $userFields['LAST_NAME']
				)
				{
					$updateFields['LAST_NAME'] = $params['LAST_NAME'];
				}
				if (
					isset($params['PERSONAL_GENDER'])
					&& $params['PERSONAL_GENDER'] !== $userFields['PERSONAL_GENDER']
				)
				{
					$updateFields['PERSONAL_GENDER'] = $params['PERSONAL_GENDER'];
				}
				if (
					isset($params['PERSONAL_WWW'])
					&& $params['PERSONAL_WWW'] !== $userFields['PERSONAL_WWW']
				)
				{
					$updateFields['PERSONAL_WWW'] = $params['PERSONAL_WWW'];
				}
				if (
					isset($params['EMAIL'])
					&& $params['EMAIL'] !== $userFields['EMAIL']
				)
				{
					$updateFields['EMAIL'] = $params['EMAIL'];
				}

				if (
					isset($params['PERSONAL_PHOTO'])
					&& !empty($params['PERSONAL_PHOTO'])
				)
				{
					$userAvatar = User::uploadAvatar($params['PERSONAL_PHOTO'], $userId);
					if (
						$userAvatar
						&& $userFields['PERSONAL_PHOTO'] != $userAvatar
					)
					{
						$connection = Application::getConnection();
						$connection->query(
							'UPDATE b_user SET PERSONAL_PHOTO = '
							. (int)$userAvatar
							. ' WHERE ID = '
							. (int)$userId
						);
						$updateFields['ID'] = $userId;
					}
				}

				if (!empty($updateFields))
				{
					$cUser = new \CUser;
					$cUser->Update($userId, $updateFields);
				}
			}
			elseif ($createUser)
			{
				$userName = $params['NAME'] ?: Loc::getMessage('IMCONNECTOR_CONNECTOR_NETWORK_GUEST_NAME');
				$userLastName = $params['LAST_NAME'];
				$userGender = $params['PERSONAL_GENDER'];
				$userWww = $params['PERSONAL_WWW'];
				$userEmail = $params['EMAIL'];

				$cUser = new \CUser;
				$fields['LOGIN'] = self::MODULE_ID_IMOPENLINES . '_' . rand(1000,9999) . randString(5);
				$fields['NAME'] = $userName;
				$fields['LAST_NAME'] = $userLastName;

				if ($userEmail)
				{
					$fields['EMAIL'] = $userEmail;
				}

				$fields['PERSONAL_GENDER'] = $userGender;
				$fields['PERSONAL_WWW'] = $userWww;
				$fields['PASSWORD'] = md5($fields['LOGIN'] . '|' . rand(1000,9999) . '|' . time());
				$fields['CONFIRM_PASSWORD'] = $fields['PASSWORD'];
				$fields['EXTERNAL_AUTH_ID'] = self::EXTERNAL_AUTH_ID;
				$fields['XML_ID'] =  'network|'.$params['UUID'];
				$fields['ACTIVE'] = 'Y';

				$userId = $cUser->Add($fields);

				if ($userId && $params['PERSONAL_PHOTO'])
				{
					$userAvatar = User::uploadAvatar($params['PERSONAL_PHOTO'], $userId);

					$connection = Application::getConnection();
					$connection->query(
						'UPDATE b_user SET PERSONAL_PHOTO = '
						. (int)$userAvatar
						. ' WHERE ID = '
						. (int)$userId
					);
				}
			}
		}

		return $userId;
	}
	//endregion
}
