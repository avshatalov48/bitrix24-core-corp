<?php
namespace Bitrix\ImConnector\Connectors;

use Bitrix\ImConnector\DeliveryMark;
use Bitrix\Main;
use Bitrix\Main\Web;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\UserTable;
use Bitrix\Main\Text\Emoji;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;

use Bitrix\Im;
use Bitrix\ImConnector\Chat;
use Bitrix\ImConnector\Error;
use Bitrix\ImConnector\Status;
use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Connector;
use Bitrix\ImConnector\InteractiveMessage;

use Bitrix\ImOpenLines\Session;
use Bitrix\ImOpenLines\Im\Messages;

Loc::loadMessages(__FILE__);
Library::loadMessages();

/**
 * Class Base
 * @package Bitrix\ImConnector\Connectors
 */
class Base
{
	/** The prefix for start parameter. */
	public const REF_PREFIX = 'btrx';

	/**
	 * @var string Full (or virtual) connector id (for example "botframework.skype", NOT "botframework").
	 */
	protected $idConnector = '';
	protected $userPrefix = '';

	/**
	 * Connector constructor.
	 * @param $idConnector
	 */
	public function __construct($idConnector)
	{
		$this->idConnector = $idConnector;
		$this->userPrefix = $idConnector;
	}

	//region Input

	/**
	 * @param $message
	 * @param $line
	 * @return Result
	 */
	public function processingInputNewMessage($message, $line): Result
	{
		$result = new Result();

		$interactiveMessage = InteractiveMessage\Input::init($this->idConnector);
		$message = $interactiveMessage
			->setMessage($message)
			->processing();

		if ($interactiveMessage->isSendMessage())
		{
			$result = $this->processingInputNewAndUpdateMessage($message);
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
		return $this->processingInputNewAndUpdateMessage($message);
	}

	/**
	 * @param $message
	 * @param $line
	 * @return Result
	 */
	public function processingInputDelMessage($message, $line): Result
	{
		return $this->processingUserAndChat($message);
	}

	/**
	 * @param $message
	 * @param $line
	 * @return Result
	 */
	public function processingInputTypingStatus($message, $line): Result
	{
		$result = $this->processingUserAndChat($message);
		if ($result->isSuccess())
		{
			$message = $result->getResult();
			$message = [
				'user' => $message['user'],
				'chat' => $message['chat'],
			];
			$result->setResult($message);
		}

		return $result;
	}

	/**
	 * @param $message
	 * @param $line
	 * @return Result
	 */
	public function processingInputNewPost($message, $line): Result
	{
		return $this->processingInputNewMessage($message, $line);
	}

	/**
	 * @param $message
	 * @param $line
	 * @return Result
	 */
	public function processingInputUpdatePost($message, $line): Result
	{
		return $this->processingInputUpdateMessage($message, $line);
	}

	/**
	 * @param string $command
	 * @param array $message
	 * @param int $line
	 * @return Result
	 */
	public function processingInputCommand(string $command, array $message, int $line): Result
	{
		$result = new Result();

		$result->addError(new Error(
			'Does not support this method call',
			'ERROR_IMCONNECTOR_DOES_NOT_SUPPORT_THIS_METHOD_CALL',
			__METHOD__
		));

		return $result;
	}

	/**
	 * @param array $message
	 * @param int $line
	 * @return Result
	 */
	public function processingInputWelcomeMessage(array $message, int $line): Result
	{
		$result = new Result();

		$result->addError(new Error(
			'Does not support this method call',
			'ERROR_IMCONNECTOR_DOES_NOT_SUPPORT_THIS_METHOD_CALL',
			__METHOD__
		));

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

		$result->addError(new Error(
			'Does not support this method call',
			'ERROR_IMCONNECTOR_DOES_NOT_SUPPORT_THIS_METHOD_CALL',
			__METHOD__
		));

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

		$result->addError(new Error(
			'Does not support this method call',
			'ERROR_IMCONNECTOR_DOES_NOT_SUPPORT_THIS_METHOD_CALL',
			__METHOD__
		));

		return $result;
	}

	/**
	 * Temporary method for processing incoming messages.
	 *
	 * @param $message
	 * @return Result
	 */
	protected function processingInputNewAndUpdateMessage($message): Result
	{
		$result = new Result();

		if (
			isset($message['user'], $message['user']['skip_phone_validate'])
			&& $message['user']['skip_phone_validate'] === 'Y'
		)
		{
			$message['extra']['skip_phone_validate'] = 'Y';
			unset($message['user']['skip_phone_validate']);
		}


		$resultProcessingUserAndChat = $this->processingUserAndChat($message);
		if ($resultProcessingUserAndChat->isSuccess())
		{
			$message = $resultProcessingUserAndChat->getResult();
		}
		else
		{
			$result->addErrors($resultProcessingUserAndChat->getErrors());
		}

		//Handling attachments
		if (
			$result->isSuccess()
			&& !empty($message['message']['attachments'])
		)
		{
			foreach ($message['message']['attachments'] as $attachment)
			{
				//Forwarded message
				if (!Library::isEmpty($attachment['forward']))
				{
					$text = $this->formationQuotedText($attachment['forward']);

					$message['message']['text'] =
						"------------------------------------------------------\n"
						. $text
						. "\n[b]" . Loc::getMessage("IMCONNECTOR_FORWARDED_MESSAGE") . "[/B]\n"
						. $message['message']['text']
						. "\n------------------------------------------------------\n";
				}
				//Answered message
				if (!Library::isEmpty($attachment['reply']))
				{
					$text = $this->formationQuotedText($attachment['reply']);

					$message['message']['text'] =
						"------------------------------------------------------\n"
						. $text
						. "\n"
						. $message['message']['text']
						. "\n------------------------------------------------------\n";
				}
				//Geolocation
				if (!empty($attachment['location']))
				{
					$text = Loc::getMessage('IMCONNECTOR_MAPS_NAME');
					if (
						!Library::isEmpty($attachment['location']['title'])
						&& !Library::isEmpty($attachment['location']['text'])
					)
					{
						$text = $attachment['location']['title'] . "\n" . $attachment['location']['text'];
					} elseif (!Library::isEmpty($attachment['location']['title']))
					{
						$text = $attachment['location']['title'];
					} elseif (!Library::isEmpty($attachment['location']['text']))
					{
						$text = $attachment['location']['text'];
					}

					$message['message']['text'] =
						$message['message']['text']
						. "\n"
						. $text
						. "\n"
						. "https://yandex.ru/maps/?ll="
						. $attachment['location']['coordinates']['longitude']
						. ","
						. $attachment['location']['coordinates']['latitude']
						. "&z=14&pt="
						. $attachment['location']['coordinates']['longitude']
						. ","
						. $attachment['location']['coordinates']['latitude']
						. ",comma";
				}
				//Contact
				if (!empty($attachment['contact']))
				{
					if (!Library::isEmpty($attachment['contact']['name']))
					{
						$message['message']['text'] .= "\n" . Loc::getMessage('IMCONNECTOR_CONTACT_NAME') . $attachment['contact']['name'];
					}
					if (!Library::isEmpty($attachment['contact']['phone']))
					{
						$message['message']['text'] .= "\n" . Loc::getMessage('IMCONNECTOR_CONTACT_PHONE') . $attachment['contact']['phone'];
					}
				}
				//Wall
				if (!empty($attachment['wall']))
				{
					$message['message']['text'] .=
						"\n[URL="
						. $attachment['wall']['url']
						. "]"
						. Loc::getMessage('IMCONNECTOR_WALL_TEXT');

					if (!Library::isEmpty($attachment['wall']['name']))
					{
						$message['message']['text'] .= " " . $attachment['wall']['name'];
					}
					if (!empty($attachment['wall']['date']))
					{
						$message['message']['text'] .=
							" "
							. Loc::getMessage('IMCONNECTOR_WALL_DATE_TEXT')
							. " "
							. DateTime::createFromTimestamp((int)$attachment['wall']['date'])->toString();
					}
					$message['message']['text'] .= "[/URL]";

					if (!Library::isEmpty($attachment['wall']['text']))
					{
						$message['message']['text'] .= "\n" . $attachment['wall']['text'];
					}
				}
			}
		}

		if (
			$result->isSuccess()
			&& !Library::isEmpty($message['message']['date'])
		)
		{
			$message['message']['date'] = DateTime::createFromTimestamp((int)$message['message']['date']);
		}

		if (
			$result->isSuccess()
			&& !empty($message['message']['files'])
		)
		{
			$files = $this->saveFiles($message['message']['files']);
			if (!$files->isSuccess())
			{
				$result->addErrors($files->getErrors());
			}
			$message['message']['files'] = $files->getData();
		}

		if (
			$result->isSuccess()
			&& !empty($message['message']['failed_big_file'])
		)
		{
			$message['message']['text'] = Loc::getMessage("IMCONNECTOR_WARNING_LARGE_FILE") . $message['message']['text'];
		}

		if (
			$result->isSuccess()
			&& !Library::isEmpty($message['message']['text'])
		)
		{
			$message['message']['text'] = Emoji::decode($message['message']['text']);
		}

		if (
			isset($message['message']['disable_crm'])
			&& $message['message']['disable_crm'] === 'Y'
			&& $result->isSuccess()
		)
		{
			$message['extra']['disable_tracker'] = 'Y';
			unset($message['message']['disable_crm']);
		}

		if (
			$result->isSuccess()
			&& Library::isEmpty($message['message']['text'])
			&& empty($message['message']['files'])
		)
		{
			$result->addError(new Error(Loc::getMessage('IMCONNECTOR_NOT_ALL_THE_REQUIRED_DATA'),
				Library::ERROR_IMCONNECTOR_NOT_ALL_THE_REQUIRED_DATA,
				__METHOD__,
				$message
			));
		}

		if ($result->isSuccess())
		{
			$result->setResult($message);
		}

		return $result;
	}

	/**
	 * Message about the delivery.
	 *
	 * @param array $message
	 * @return array
	 */
	protected function processingLastMessage(array $message): array
	{
		if (
			!empty($message['extra']['last_message_id'])
			&& !empty($message['chat']['id'])
		)
		{
			Chat::setLastMessage(
				externalChatId: $message['chat']['id'],
				externalMessageId: $message['extra']['last_message_id'],
				connector: $this->idConnector
			);

			unset($message['extra']['last_message_id']);
		}

		return $message;
	}

	/**
	 * A method that generates a design quote.
	 *
	 * @param array $attachment An array describing quotes.
	 * @return string
	 */
	protected function formationQuotedText($attachment)
	{
		$returnText = '';

		if (!Library::isEmpty($attachment['user']))
		{
			if (
				!Library::isEmpty($attachment['user']['last_name'])
				|| !Library::isEmpty($attachment['user']['name'])
			)
			{
				//TODO: it does not work correctly, if the URL for the user
				/*if (!empty($attachment['user']['url']))
				{
					$returnText .= "[URL=" . $attachment['user']['url'] . "]";
				}*/

				if (!Library::isEmpty($attachment['user']['last_name']))
				{
					$returnText .= $attachment['user']['last_name'];
				}
				if (
					!Library::isEmpty($attachment['user']['last_name'])
					&& !empty($attachment['user']['name'])
				)
				{
					$returnText .= ' ';
				}

				if (!Library::isEmpty($attachment['user']['name']))
				{
					$returnText .= $attachment['user']['name'];
				}

				/*if (!empty($attachment['user']['url']))
				{
					$returnText .= "[/URL]  ";
				}*/
			}

			if (!Library::isEmpty($attachment['date']))
			{
				$returnText .= "[" . DateTime::createFromTimestamp((int)$attachment['date'])->toString() . "]";
			}
		}

		return $returnText;
	}

	/**
	 * @param array $message
	 * @return Result
	 */
	protected function processingUserAndChat(array $message): Result
	{
		$result = new Result();

		if (
			!empty($message['user'])
			&& !empty($message['chat'])
		)
		{
			//Getting user id
			$userResult = $this->processingUser($message['user']);
			if ($userResult->isSuccess())
			{
				$message['user'] = $userResult->getResult();
			}
			else
			{
				$result->addErrors($userResult->getErrors());
			}

			if ($result->isSuccess())
			{
				$message['chat'] = $this->processingChat($message['chat']);
			}

			if ($result->isSuccess())
			{
				$result->setResult($message);
			}
		}
		else
		{
			$result->addError(new Error(Loc::getMessage(
				'IMCONNECTOR_NOT_ALL_THE_REQUIRED_DATA'),
				Library::ERROR_IMCONNECTOR_NOT_ALL_THE_REQUIRED_DATA,
				__METHOD__,
				$message
			));
		}

		return $result;
	}

	/**
	 * @param array $chat
	 * @return array
	 */
	protected function processingChat(array $chat): array
	{
		if (!empty($chat['url']))
		{
			$chat['description'] = Loc::getMessage(
				'IMCONNECTOR_LINK_TO_ORIGINAL_POST',
				[
					'#LINK#' => $chat['url']
				]
			);

			unset($chat['url']);
		}

		return $chat;
	}

	//endregion

	//region User

	/**
	 * Parse full name into first name and surname.
	 *
	 * @param array $user An array describing the user.
	 */
	protected function getFullName(array $user): array
	{
		if (Library::isEmpty($user['last_name']))
		{
			$fullName = explode(' ', $user['name']);
			if (count($fullName) === 2)
			{
				$user['name'] = $fullName[0];
				$user['last_name'] = $fullName[1];
			}
		}

		return $user;
	}

	/**
	 * @param array $user
	 * @return Result
	 */
	public function getUserByUserCode(array $user): Result
	{
		$result = new Result();

		if (Library::isEmpty($user['id']))
		{
			$result->addError(new Error(Loc::getMessage(
				'IMCONNECTOR_PROXY_NO_USER_IM'),
				Library::ERROR_CONNECTOR_PROXY_NO_USER_IM,
				__METHOD__,
				$user
			));
		}
		else
		{
			$raw = UserTable::getList([
					'select' => [
						'ID',
						'MD5' => 'UF_CONNECTOR_MD5'
					],
					'filter' => [
						'=EXTERNAL_AUTH_ID' => Library::NAME_EXTERNAL_USER,
						'=XML_ID' => $this->userPrefix . '|' . $user['id']
					],
					'limit' => 1
				]
			);

			if ($userFields = $raw->fetch())
			{
				$result->setResult($userFields);
			}
			else
			{
				//user record does not yet exist, it will be created on next step.
				$result->addError(new Main\Error('User does not yet exist'));
			}
		}

		return $result;
	}

	/**
	 * @param array $userFields
	 * @return Result
	 */
	protected function addUser(array $userFields): Result
	{
		$result = new Result();
		$user = new \CUser;

		$fields = $this->preparationNewUserFields($userFields);

		static::getApplication()->resetException();

		$userId = $user->add($fields);
		if ($userId > 0)
		{
			$result->setResult($userId);
		}
		else
		{
			$error = static::getApplication()->getException();
			if ($error instanceof \CApplicationException)
			{
				$result->addError(new Error($error->getString()));
			}
			elseif (!empty($user->LAST_ERROR))
			{
				$result->addError(new Error($user->LAST_ERROR));
			}
		}

		return $result;
	}

	/**
	 * @param array $oldUserFields
	 * @param array $userFields
	 * @return Result
	 */
	protected function updateUser(array $oldUserFields, array $userFields): Result
	{
		$result = new Result();
		$user = new \CUser;

		$userId = $userFields['ID'];
		$result->setResult($userId);

		if ($userFields['MD5'] !== md5(serialize($oldUserFields)))
		{
			$fields = $this->preparationUserFields($oldUserFields, $userId);
			if (!empty($fields))
			{
				static::getApplication()->resetException();

				if (!$user->update($userId, $fields))
				{
					$error = static::getApplication()->getException();
					if ($error instanceof \CApplicationException)
					{
						$result->addError(new Error($error->getString()));
					}
					elseif (!empty($user->LAST_ERROR))
					{
						$result->addError(new Error($user->LAST_ERROR));
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Data processing user and returns the id of the registered user of the system.
	 *
	 * @param array $user An array describing the user.
	 * @return Result
	 */
	protected function processingUser(array $user): Result
	{
		$result = new Result();
		$userId = 0;

		$user = $this->getFullName($user);

		$userFieldsResult = $this->getUserByUserCode($user);

		if ($userFieldsResult->isSuccess())
		{
			$userFields = $userFieldsResult->getResult();

			if (is_array($userFields))
			{
				$updateResult = $this->updateUser($user, $userFields);
				$userId = $updateResult->getResult();
				if (empty($userId) && !$updateResult->isSuccess())
				{
					$result->addErrors($updateResult->getErrors());
				}
			}
		}
		else
		{
			$addResult = $this->addUser($user);
			if ($addResult->isSuccess())
			{
				$userId = $addResult->getResult();
			}
			else
			{
				$result->addErrors($addResult->getErrors());
			}
		}

		if (empty($userId))
		{
			$result->addError(new Error(
				Loc::getMessage('IMCONNECTOR_PROXY_NO_ADD_USER'),
				Library::ERROR_CONNECTOR_PROXY_NO_ADD_USER,
				__METHOD__
			));
		}
		else
		{
			$result->setResult($userId);
		}

		return $result;
	}

	/**
	 * Preparation of new user fields before saving or adding.
	 *
	 * @param array $user An array describing the user.
	 * @return array Given the right format array description user.
	 */
	public function preparationNewUserFields($user): array
	{
		return array_merge($this->preparationUserFields($user), $this->getBasicFieldsNewUser($user));
	}

	/**
	 * Returns the base fields of the new user of open lines.
	 *
	 * @param $user
	 * @return array
	 */
	protected function getBasicFieldsNewUser($user): array
	{
		$fields = [];
		$fields['LOGIN'] = Library::MODULE_ID . '_' . md5($user['id'] . '_' . randString(5));
		$fields['PASSWORD'] = md5($fields['LOGIN'] . '|' . rand(1000,9999) . '|' . time());
		$fields['CONFIRM_PASSWORD'] = $fields['PASSWORD'];
		$fields['EXTERNAL_AUTH_ID'] = Library::NAME_EXTERNAL_USER;
		$fields['XML_ID'] =  $this->userPrefix . '|' . $user['id'];
		$fields['ACTIVE'] = 'Y';

		return $fields;
	}

	/**
	 * Preparation of user fields before saving or adding.
	 *
	 * @param array $user An array describing the user.
	 * @param $userId
	 * @return array Given the right format array description user.
	 */
	public function preparationUserFields(array $user, $userId = 0): array
	{
		//The hash of the data
		$fields = [
			'UF_CONNECTOR_MD5' => md5(serialize($user))
		];

		//TODO: Hack to bypass the option of deleting the comment
		if (isset($user['name']))
		{
			//Name
			if (Library::isEmpty($user['name']))
			{
				$fields['NAME'] = '';
			}
			else
			{
				$fields['NAME'] = $user['name'];
			}
		}
		//Surname
		if (Library::isEmpty($user['last_name']))
		{
			$fields['LAST_NAME'] = '';
		}
		else
		{
			$fields['LAST_NAME'] = $user['last_name'];
		}

		if (
			Library::isEmpty($fields['NAME'])
			&& Library::isEmpty($fields['LAST_NAME'])
		)
		{
			if (Library::isEmpty($user['title']))
			{
				$fields['NAME'] = Loc::getMessage("IMCONNECTOR_GUEST_USER");
			}
			else
			{
				$fields['NAME'] = $user['title'];
			}
		}

		//The link to the profile
		if (empty($user['url']))
		{
			$fields['PERSONAL_WWW'] = '';
		}
		else
		{
			$fields['PERSONAL_WWW'] = $user['url'];
		}

		//Sex
		if (empty($user['gender']))
		{
			$fields['PERSONAL_GENDER'] = '';
		}
		else
		{
			if ($user['gender'] == 'male')
			{
				$fields['PERSONAL_GENDER'] = 'M';
			}
			elseif($user['gender'] == 'female')
			{
				$fields['PERSONAL_GENDER'] = 'F';
			}
		}
		//Personal photo
		if (
			!empty($user['picture'])
			&& is_array($user['picture'])
		)
		{
			$fields['PERSONAL_PHOTO'] = Library::downloadFile($user['picture']);

			if (
				!empty($fields['PERSONAL_PHOTO'])
				&& !empty($userId)
			)
			{
				$rowUser = UserTable::getList(
					[
						'select' => ['PERSONAL_PHOTO'],
						'filter' => ['ID' => $userId]
					]
				)->fetch();

				if (!empty($rowUser['PERSONAL_PHOTO']))
				{
					$fields['PERSONAL_PHOTO']['del'] = 'Y';
					$fields['PERSONAL_PHOTO']['old_file'] = $rowUser['PERSONAL_PHOTO'];
				}
			}
		}

		if (
			isset($user['title'])
			&& !Library::isEmpty($user['title'])
		)
		{
			$fields['TITLE'] = $user['title'];
		}

		if (
			isset($user['email'])
			&& !Library::isEmpty($user['email'])
		)
		{
			$fields['EMAIL'] = $user['email'];
		}

		if (
			isset($user['phone'])
			&& !Library::isEmpty($user['phone'])
		)
		{
			$fields['PERSONAL_MOBILE'] = $user['phone'];
		}

		return $fields;
	}

	//endregion

	//region File

	/**
	 * Saving files.
	 *
	 * @param $files
	 * @return Result
	 */
	protected function saveFiles($files): Result
	{
		$result = new Result();
		$resultSaveFiles = [];

		foreach ($files as $cell => $file)
		{
			if (!empty($file))
			{
				$resultSaveFile = $this->saveFile($file);
				if (!empty($resultSaveFile))
				{
					$resultSaveFiles[$cell] = $resultSaveFile;
				}
			}
		}

		$result->setData($resultSaveFiles);

		return $result;
	}

	/**
	 * Save file
	 *
	 * @param $file
	 * @return false|int|string
	 */
	public function saveFile($file)
	{
		$result = false;

		if (
			!empty($file)
			&& is_array($file)
			&& !empty($file['url'])
		)
		{
			$file = Library::downloadFile($file);
		}
		else
		{
			$file = false;
		}

		if ($file)
		{
			$result = \CFile::SaveFile(
				$file,
				Library::MODULE_ID
			);
		}

		return $result;
	}

	//endregion

	//region Error

	/**
	 * @see \Bitrix\ImConnector\Provider\Base\Input::receivingError
	 * @param $paramsError
	 * @return bool
	 */
	public function receivedError($paramsError): bool
	{
		$result = false;

		switch ($paramsError['code'])
		{
			case Library::ERROR_CONNECTOR_NOT_SEND_MESSAGE_CHAT:
				$result = $this->receivedErrorNotSendMessageChat($paramsError);
				break;

			case Library::ERROR_CONNECTOR_DELETE_MESSAGE:
				$result = $this->receivedErrorNotDeleteMessageChat($paramsError);
				break;

			default:
				if (!empty($paramsError['messageId']) && !empty($paramsError['chatId']))
				{
					$this->markMessageUndelivered((int)$paramsError['messageId']);
					$this->removeMessageDeliveryMark((int)$paramsError['messageId'], (int)$paramsError['chatId']);
				}
		}

		return $result;
	}

	/**
	 * @param $paramsError
	 * @param string $message
	 * @return bool
	 */
	protected function receivedErrorNotDeleteMessageChat($paramsError, string $message = ''): bool
	{
		$result = false;

		if (
			!empty($paramsError['chatId'])
			&& $paramsError['chatId'] > 0
			&& Loader::includeModule('imopenlines')
		)
		{
			$messageExternalError = '';
			if (!empty($paramsError['messageConnector']))
			{
				$messageExternalError = $paramsError['messageConnector'];
			}

			if (empty($message))
			{
				$message = Loc::getMessage('IMCONNECTOR_MESSAGE_ERROR_NOT_DELETE_CHAT');
			}

			Messages\Error::addErrorNotDeleteChat((int)$paramsError['chatId'], $message, $messageExternalError);

			$result = true;
		}

		return $result;
	}

	/**
	 * @param $paramsError
	 * @param string $message
	 * @return bool
	 */
	protected function receivedErrorNotSendMessageChat($paramsError, string $message = ''): bool
	{
		$result = false;

		if (
			!empty($paramsError['chatId'])
			&& (int)$paramsError['chatId'] > 0
			&& Loader::includeModule('imopenlines')
		)
		{
			$messageExternalError = '';
			if (!empty($paramsError['messageConnector']))
			{
				$messageExternalError = $paramsError['messageConnector'];
			}

			if (
				!empty($paramsError['messageId'])
				&& $paramsError['messageId'] > 0
			)
			{
				$this->markMessageUndelivered((int)$paramsError['messageId']);
				$this->removeMessageDeliveryMark((int)$paramsError['messageId'], (int)$paramsError['chatId']);
			}

			if (empty($message))
			{
				$message = Loc::getMessage('IMCONNECTOR_MESSAGE_ERROR_NOT_SEND_CHAT');
			}

			Messages\Error::addErrorNotSendChat((int)$paramsError['chatId'], $message, $messageExternalError);

			$result = true;
		}

		return $result;
	}

	/**
	 * Updates message with undelivered mark.
	 * @param int $messageId Message Id.
	 * @return void
	 */
	public function markMessageUndelivered(int $messageId): void
	{
		if (!Loader::includeModule('im'))
		{
			return;
		}
		if ($messageId)
		{
			$message = new Im\V2\Message($messageId);
			if ($message->getMessageId() !== $messageId)
			{
				return;
			}

			$messageParams = $message->getParams();

			$pullParams = [
				Im\V2\Message\Params::IS_DELIVERED => false,
				Im\V2\Message\Params::SENDING => false,
				Im\V2\Message\Params::SENDING_TS => 0
			];
			$messageParams->fill($pullParams);
			$messageParams->save();

			\CIMMessageParam::sendPull($message->getMessageId(), array_keys($pullParams));
			if (Loader::includeModule('pull'))
			{
				\Bitrix\Pull\Event::send();
			}
		}
	}

	/**
	 * Unsets waiting delidery mark.
	 * @param int $messageId
	 * @param int $chatId
	 * @return void
	 */
	public function removeMessageDeliveryMark(int $messageId, int $chatId): void
	{
		DeliveryMark::unsetDeliveryMark($messageId, $chatId);
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
		//Processing for native messages
		$message = InteractiveMessage\Output::processSendingMessage($message, $this->idConnector);
		//Processing rich links
		$message = $this->processMessageForRich($message);
		//Delete data user
		unset($message['user']);

		return $message;
	}

	/**
	 * @param array $message
	 * @param $line
	 * @return array
	 */
	public function updateMessageProcessing(array $message, $line): array
	{
		return $message;
	}

	/**
	 * @param array $message
	 * @param $line
	 * @return array
	 */
	public function deleteMessageProcessing(array $message, $line): array
	{
		return $message;
	}

	/**
	 * @param Session $session
	 * @return bool
	 */
	public function isEnableSendSystemMessage(Session $session): bool
	{
		return true;
	}

	//endregion

	//region Tools

	/**
	 * Prepares attachments data to send.
	 *
	 * @param array $message
	 * @return array
	 */
	protected function processMessageForRich(array $message): array
	{
		$richData = [];
		if (
			!empty($message['message']['attachments'])
			&& is_array($message['message']['attachments'])
		)
		{
			foreach ($message['message']['attachments'] as $attachment)
			{
				try
				{
					$attachment = Web\Json::decode($attachment);

					if (
						isset($attachment['BLOCKS'])
						&& is_array($attachment['BLOCKS'])
					)
					{
						foreach ($attachment['BLOCKS'] as $block)
						{
							if (
								isset($block['RICH_LINK'])
								&& is_array($block['RICH_LINK'])
							)
							{
								foreach ($block['RICH_LINK'] as $richData)
								{
									if (!empty($richData))
									{
										if ($richData['LINK'])
										{
											$richData['richData']['url'] = $richData['LINK'];
										}

										if ($richData['NAME'])
										{
											$richData['richData']['title'] = $richData['NAME'];
										}

										if ($richData['DESC'])
										{
											$richData['richData']['description'] = $richData['DESC'];
										}

										if ($richData['PREVIEW'])
										{
											$uri = new Uri($richData['PREVIEW']);
											if ($uri->getHost())
											{
												$richData['richData']['image'] = $richData['PREVIEW'];
											}
											else
											{
												$richData['richData']['image'] = Connector::getDomainDefault() .'/'. $richData['PREVIEW'];
											}
										}
										elseif($richData['EXTRA_IMAGE'])
										{
											$richData['richData']['image'] = $richData['EXTRA_IMAGE'];
										}
									}
								}
							}
						}
					}
				}
				catch (\Bitrix\Main\SystemException $e)
				{
				}
			}
		}

		$message['message']['attachments'] = $richData;

		return $message;
	}

	/**
	 * @param array $message
	 * @return array
	 */
	protected function processingMessageForOperatorData(array $message): array
	{
		if (!empty($message['user']))
		{
			$oldUserData = $message['user'];
			unset($message['user']);

			$message['user']['name'] = $oldUserData['NAME'];
			$message['user']['picture']['url'] = '';
			if (!empty($oldUserData['AVATAR']))
			{
				$message['user']['picture']['url'] = $oldUserData['AVATAR'];
			}
		}

		return $message;
	}

	/**
	 * @param int $lineId
	 * @return bool
	 */
	protected function isHumanAgent($lineId): bool
	{
		$statusData = Status::getInstance($this->idConnector, (int)$lineId)->getData();

		return !empty($statusData) && $statusData['HUMAN_AGENT'] === true;
	}

	/**
	 * @return \CMain
	 */
	protected static function getApplication(): \CMain
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;
		return $APPLICATION;
	}

	//endregion
}
