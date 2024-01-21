<?php
namespace Bitrix\ImConnector\Connectors;

use Bitrix\Disk\File;
use Bitrix\ImOpenLines\Chat;
use Bitrix\ImOpenLines\Connector;
use Bitrix\Imopenlines\MessageParameter;
use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Config;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Im;
use Bitrix\Im\User;

use Bitrix\ImConnector\Error;
use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\InteractiveMessage;

/**
 * Class Network
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

		if (empty($message['USER']) || empty($message['USER']['UUID']))
		{
			$result->addError(new Error(
				'User data not transmitted',
				'ERROR_IMCONNECTOR_NOT_TRANSMITTED_USER_DATA',
				__METHOD__,
				$message
			));
		}

		if ($result->isSuccess())
		{
			$userId = $this->getUserId($message['USER'], true);

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
				'extraData' => $message['EXTRA_DATA'] ?? null
			];

			if (isset($message['FILES_RAW']) && is_array($message['FILES_RAW']))
			{
				$filesIds = $this->createReceivedRawFiles($message['FILES_RAW']);
				if (count($filesIds) && Loader::includeModule('imopenlines'))
				{
					$chatParams = [
						'connector_id' => $this->idConnector,
						'line_id' => $message['LINE_ID'],
						'chat_id' => $message['GUID'],
						'user_id' => $userId
					];

					$chat = new Chat();
					$isLoaded = $chat->load([
						'USER_CODE' => Connector::getUserCode($chatParams),
						'ONLY_LOAD' => 'Y',
					]);

					if ($isLoaded && Loader::includeModule('disk'))
					{
						$diskFiles = \CIMDisk::UploadFileFromMain(
							$chat->getData('ID'),
							$filesIds
						);

						if (!is_array($messageData['fileLinks']))
						{
							$messageData['fileLinks'] = [];
						}

						foreach ($diskFiles as $fileId)
						{
							$fileModel = File::loadById($fileId);
							if ($fileModel)
							{
								$messageData['fileLinks'][] = [
									'name' => $fileModel->getOriginalName(),
									'link' => \CIMDisk::GetFileLink($fileModel),
									'size' => $fileModel->getSize(),
								];
							}
						}
					}
				}
			}

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
				isset($message['USER']['BOT_VERSION'])
				&& !empty($message['USER']['BOT_VERSION'])
			)
			{
				$description .=
					'[B]'
					. Loc::getMessage('IMCONNECTOR_CONNECTOR_NETWORK_BOT_VERSION')
					. '[/B]: '
					. $message['USER']['BOT_VERSION']
					. '[BR]';
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

	private function createReceivedRawFiles(array $rawFiles): array
	{
		$fileIds = [];
		foreach ($rawFiles as $file)
		{
			$fileData = [
				'name' => $file['NAME'],
				'type' => $file['TYPE'],
				'content' => $file['DATA'],
				'MODULE_ID' => self::MODULE_ID_IMOPENLINES
			];

			$fileIds[] = \CFile::saveFile($fileData, $fileData['MODULE_ID']);
		}

		return $fileIds;
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
		$userId = $this->getUserId($message['USER'], false);

		if (empty($userId))
		{
			$result->addError(new Error(
				'Failed to find user',
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

		if (empty($params['USER']) || empty($params['USER']['UUID']))
		{
			$result->addError(new Error(
				'User data not transmitted',
				'ERROR_IMCONNECTOR_NOT_TRANSMITTED_USER_DATA',
				__METHOD__,
				$params
			));
		}

		$userId = 0;
		if ($result->isSuccess())
		{
			$userId = $this->getUserId($params['USER'], false);

			if (empty($userId))
			{
				$result->addError(new Error(
					'Failed to find user',
					'ERROR_IMCONNECTOR_FAILED_USER',
					__METHOD__,
					$params
				));
			}
		}

		// Interactive Message
		if (
			$result->isSuccess()
			&& isset($params['COMMAND'])
			&& isset($params['COMMAND_PARAMS'])
		)
		{
			/** @var InteractiveMessage\Connectors\Network\Input $interactiveMessage */
			$interactiveMessage = InteractiveMessage\Input::init('network');

			$resultProcessing = $interactiveMessage->processingCommandKeyboard($params['COMMAND'], $params['COMMAND_PARAMS']);

			if (!$resultProcessing->isSuccess())
			{
				$result->addErrors($resultProcessing->getErrors());
			}
		}
		else
		{
			$result->addError(new Error(
				'Invalid data was transmitted',
				'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_NOT_LOAD_CORRECT_DATA',
				__METHOD__,
				['command' => $params['COMMAND'] ?? '-empty-', 'data' => $params['COMMAND_PARAMS'] ?? '-empty-']
			));
		}

		// IM commands
		if (
			$result->isSuccess()
			&& isset($params['MESSAGE_ID'])
			&& (int)$params['MESSAGE_ID'] > 0
		)
		{
			$messageId = (int)$params['MESSAGE_ID'];

			$message = Im\Model\MessageTable::getById($messageId)->fetch();
			if ($message)
			{
				$relations = \CIMChat::getRelationById($message['CHAT_ID'], false, false, false);
				if (isset($relations[$userId]))
				{
					$chat = Im\Model\ChatTable::getById($message['CHAT_ID'])->fetch();

					$messageFields = $params;

					$messageFields['FROM_USER_ID'] = $userId;
					$messageFields['TO_CHAT_ID'] = $message['CHAT_ID'];
					$messageFields['MESSAGE'] =  '/'.$params['COMMAND'].' '.$params['COMMAND_PARAMS'];

					$messageFields['MESSAGE_TYPE'] = $relations[$userId]['MESSAGE_TYPE'];
					$messageFields['AUTHOR_ID'] = $userId;

					$messageFields['COMMAND_CONTEXT'] = $params['COMMAND_CONTEXT'] ?? 'KEYBOARD';
					$messageFields['CHAT_ENTITY_TYPE'] = $chat['ENTITY_TYPE'];
					$messageFields['CHAT_ENTITY_ID'] = $chat['ENTITY_ID'];

					Im\Command::onCommandAdd($messageId, $messageFields);
				}
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

		if (!Loader::includeModule('im') || !Loader::includeModule('imopenlines'))
		{
			$result->addError(new Error(
				'Failed to load the im module',
				'ERROR_IMCONNECTOR_FAILED_LOAD_IM',
				__METHOD__
			));
		}

		if (empty($params['USER']) || empty($params['USER']['UUID']))
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
			$userId = $this->getUserId($params['USER'], false);

			if (empty($userId))
			{
				$result->addError(new Error(
					'Failed to find user',
					'ERROR_IMCONNECTOR_FAILED_USER',
					__METHOD__,
					$params
				));
			}
		}

		$messageParams = [MessageParameter::IMOL_VOTE_SID => 0];

		if ($result->isSuccess())
		{
			$messageParamService = ServiceLocator::getInstance()->get('Im.Services.MessageParam');
			if ($messageParamService instanceof \Bitrix\Im\Services\MessageParam)
			{
				$messageParams = $messageParamService->getParams((int)$params['MESSAGE_ID']);
			}

			if (
				!isset($messageParams[MessageParameter::IMOL_VOTE_SID])
				|| $messageParams[MessageParameter::IMOL_VOTE_SID] != $params['SESSION_ID']
			)
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
		$result = [
			'LINE_ID' => $line,
			'GUID' => $message['chat']['id'],
			'MESSAGE_ID' => $message['im']['message_id'],
			'MESSAGE_TEXT' => $message['message']['text'],
			'FILES' => $message['message']['files'],
			'ATTACH' => $message['message']['attachments'],
			'PARAMS' => $message['message']['params']
		];

		if (
			!empty($message['im']['chat_id']) &&
			$message['im']['chat_id'] > 0
		)
		{
			$interactiveMessage = InteractiveMessage\Output::getInstance($message['im']['chat_id'], ['connectorId' => 'network']);
			$message['message'] = $interactiveMessage->nativeMessageProcessing($message['message']);

			if ($interactiveMessage->isLoadedKeyboard())
			{
				$result['KEYBOARD'] = $message['message']['keyboardData'];
			}
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
            "FILES" => $message['message']['files'] ?? null,
            "ATTACH" => $message['message']['attachments'] ?? null,
            "PARAMS" => $message['message']['params'] ?? null,
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
		if (empty($params['UUID']))
		{
			return $userId;
		}

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
						$connection->query('UPDATE b_user SET PERSONAL_PHOTO = ' . (int)$userAvatar . ' WHERE ID = ' . (int)$userId);
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
				$fields = [];
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
					$connection->query('UPDATE b_user SET PERSONAL_PHOTO = ' . (int)$userAvatar . ' WHERE ID = ' . (int)$userId);
				}
			}
		}

		return $userId;
	}

	//endregion

	/**
	 * @return bool
	 */
	public static function isSearchEnabled(): bool
	{
		return Config\Option::get('imconnector', 'allow_search_network', 'Y') === 'Y';
	}

	/**
	 * @param bool $enabled
	 * @return void
	 */
	public static function setIsSearchEnabled(bool $enabled): void
	{
		Config\Option::set(
			'imconnector',
			'allow_search_network',
			$enabled ? 'Y' : 'N'
		);
	}
}
