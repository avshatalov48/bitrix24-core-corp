<?php
namespace Bitrix\ImConnector\Input;

use \Bitrix\Main\Event,
	\Bitrix\Main\IO\File,
	\Bitrix\Main\UserTable,
	\Bitrix\Main\Text\Emoji,
	\Bitrix\Main\Type\DateTime,
	\Bitrix\Main\Web\HttpClient,
	\Bitrix\Main\Localization\Loc;
use \Bitrix\ImConnector\Chat,
	\Bitrix\ImConnector\Error,
	\Bitrix\ImConnector\Output,
	\Bitrix\ImConnector\Result,
	\Bitrix\ImConnector\Library,
	\Bitrix\ImConnector\Connector,
	\Bitrix\ImConnector\Connectors\Olx,
	\Bitrix\ImConnector\InteractiveMessage,
	\Bitrix\ImConnector\Connectors\BotFramework;

Loc::loadMessages(__FILE__);
Library::loadMessages();

/**
 * The class receiving the message.
 *
 * @package Bitrix\ImConnector\Input
 */
class ReceivingMessage
{
	private $connector;
	private $line;
	private $data;
	private $connectorOutput;

	/**
	 * ReceivingMessage constructor.
	 * @param string $connector ID Connector.
	 * @param string|null $line ID line.
	 * @param array $data An array of data.
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	function __construct($connector, $line = null, $data = array())
	{
		$this->connector = $connector;
		$this->line = $line;
		$this->data = $data;
		$this->connectorOutput = new Output($this->connector, $this->line);
	}

	/**
	 * A method that generates a design quote.
	 *
	 * @param array $attachment An array describing quotes.
	 * @return string
	 */
	private static function formationQuotedText($attachment)
	{
		$returnText = "";

		if(!Library::isEmpty($attachment['user']))
		{
			if(!Library::isEmpty($attachment['user']['last_name']) || !Library::isEmpty($attachment['user']['name']))
			{
				//TODO: it does not work correctly, if the URL for the user
				/*if(!empty($attachment['user']['url']))
					$returnText .= "[URL=" . $attachment['user']['url'] . "]";*/

				if(!Library::isEmpty($attachment['user']['last_name']))
					$returnText .= $attachment['user']['last_name'];
				if(!Library::isEmpty($attachment['user']['last_name']) && !empty($attachment['user']['name']))
					$returnText .= ' ';
				if(!Library::isEmpty($attachment['user']['name']))
					$returnText .= $attachment['user']['name'];

				/*if(!empty($attachment['user']['url']))
					$returnText .= "[/URL]  ";*/
			}

			if(!Library::isEmpty($attachment['date']))
			{
				$returnText .= "[" . DateTime::createFromTimestamp($attachment['date'])->toString() . "]";
			}
		}

		return $returnText;
	}

	/**
	 * Receive data.
	 *
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function receiving(): Result
	{
		$result = new Result();
		$statusDelivered = [];

		//Message about the delivery
		foreach ($this->data as $message)
		{
			$statusDelivered[] = [
				'chat' => [
					'id' => $message['chat']['id']
				],
				'message' => [
					'id' => $message['message']['id']
				]
			];

			if (!empty($message['extra']['last_message_id']))
			{
				Chat::setLastMessage(
					[
						'EXTERNAL_CHAT_ID' => $message['chat']['id'],
						'CONNECTOR' => $this->connector,
						'EXTERNAL_MESSAGE_ID' => $message['extra']['last_message_id']
					]
				);
			}
		}
		//Sending a message about the delivery
		if(!empty($statusDelivered))
		{
			$this->connectorOutput->setStatusDelivered($statusDelivered);
		}

		$interactiveMessage = InteractiveMessage\Input::initialization($this->connector);

		foreach ($this->data as $cell => $message)
		{
			$resultMessage = new Result();
			unset($event);

			$message = $interactiveMessage->setMessage($message)
				->processing();

			$isSendMessage = $interactiveMessage->isSendMessage();

			if($isSendMessage)
			{
				//parse full name into first name and surname
				if(Library::isEmpty($message['user']['last_name']))
				{
					$fullName = explode(' ', $message['user']['name']);
					if(count($fullName) === 2)
					{
						$message['user']['name'] = $fullName[0];
						$message['user']['last_name'] = $fullName[1];
					}
				}

				//Hack is designed for the Microsoft Bot Framework
				$userSourceData = $message['user'];

				//Getting user id
				$user = $this->processingUser($message['user']);
				if($user->isSuccess())
					$message['user'] = $user->getResult();
				else
					$resultMessage->addErrors($user->getErrors());

				//Handling attachments
				if($resultMessage->isSuccess() && !empty($message['message']['attachments']))
				{
					foreach ($message['message']['attachments'] as $attachment)
					{
						//Forwarded message
						if(!Library::isEmpty($attachment['forward']))
						{
							$text = self::formationQuotedText($attachment['forward']);

							$message['message']['text'] = "------------------------------------------------------\n" .
								$text .  "\n[b]" . Loc::getMessage("IMCONNECTOR_FORWARDED_MESSAGE") . "[/B]\n" .
								$message['message']['text'] .
								"\n------------------------------------------------------\n";
						}
						//Answered message
						if(!Library::isEmpty($attachment['reply']))
						{
							$text = self::formationQuotedText($attachment['reply']);

							$message['message']['text'] = "------------------------------------------------------\n" .
								$text . "\n" . $message['message']['text'] . "\n------------------------------------------------------\n";
						}
						//Geolocation
						if(!empty($attachment['location']))
						{
							$text = Loc::getMessage("IMCONNECTOR_MAPS_NAME");
							if(!Library::isEmpty($attachment['location']['title']) && !Library::isEmpty($attachment['location']['text']))
								$text = $attachment['location']['title'] . "\n" . $attachment['location']['text'];
							elseif(!Library::isEmpty($attachment['location']['title']))
								$text = $attachment['location']['title'];
							elseif(!Library::isEmpty($attachment['location']['text']))
								$text = $attachment['location']['text'];

							$message['message']['text'] = $message['message']['text'] . "\n" . $text . "\n" . "https://yandex.ru/maps/?ll=" . $attachment['location']['coordinates']['longitude'] . "," . $attachment['location']['coordinates']['latitude'] . "&z=14&pt=" . $attachment['location']['coordinates']['longitude'] . "," . $attachment['location']['coordinates']['latitude'] . ",comma";
						}
						//Contact
						if(!empty($attachment['contact']))
						{
							if(!Library::isEmpty($attachment['contact']['name']))
								$message['message']['text'] .= "\n" . Loc::getMessage("IMCONNECTOR_CONTACT_NAME") . $attachment['contact']['name'];
							if(!Library::isEmpty($attachment['contact']['phone']))
								$message['message']['text'] .= "\n" . Loc::getMessage("IMCONNECTOR_CONTACT_PHONE") . $attachment['contact']['phone'];
						}
						//Wall
						if(!empty($attachment['wall']))
						{
							$message['message']['text'] .= "\n[URL=" . $attachment['wall']['url'] . "]" . Loc::getMessage("IMCONNECTOR_WALL_TEXT");
							if(!Library::isEmpty($attachment['wall']['name']))
								$message['message']['text'] .= " " . $attachment['wall']['name'];
							if(!empty($attachment['wall']['date']))
								$message['message']['text'] .= " " . Loc::getMessage("IMCONNECTOR_WALL_DATE_TEXT") . " " . DateTime::createFromTimestamp($attachment['wall']['date'])->toString();
							$message['message']['text'] .= "[/URL]";

							if(!Library::isEmpty($attachment['wall']['text']))
								$message['message']['text'] .= "\n" . $attachment['wall']['text'];
						}
					}
				}

				/*if($resultMessage->isSuccess() && !empty($message['message']['url']))
				{
					$message['message']['keyboard'] = Array(
						Array(
							"TEXT" => Loc::getMessage("IMCONNECTOR_COMMENT_IN_FACEBOOK"),
							"LINK" => $message['message']['url'],
							"BG_COLOR" => "#29619b",
							"TEXT_COLOR" => "#fff",
							"DISPLAY" => "LINE",
						)
					);
					unset($message['message']['url']);
				}*/

				if($resultMessage->isSuccess() && !empty($message['chat']['url']))
				{
					if ($this->connector === 'facebookcomments')
					{
						$message['chat']['description'] = Loc::getMessage('IMCONNECTOR_LINK_TO_ORIGINAL_POST_IN_FACEBOOK', ['#LINK#' => $message['chat']['url']]);
					}
					elseif ($this->connector === Library::ID_FBINSTAGRAM_CONNECTOR)
					{
						$message['chat']['description'] = Loc::getMessage('IMCONNECTOR_LINK_TO_ORIGINAL_POST_IN_INSTAGRAM', ['#LINK#' => $message['chat']['url']]);
					}
					elseif ($this->connector === 'avito')
					{
						$message['chat']['description'] = Loc::getMessage('IMCONNECTOR_LINK_TO_AVITO_AD', ['#LINK#' => $message['chat']['url']]);
					}
					else
					{
						$message['chat']['description'] = Loc::getMessage("IMCONNECTOR_LINK_TO_ORIGINAL_POST", array('#LINK#' => $message['chat']['url']));
					}

					unset($message['chat']['url']);
				}

				if($resultMessage->isSuccess() && !Library::isEmpty($message['message']['date']))
					$message['message']['date'] = DateTime::createFromTimestamp($message['message']['date']);

				if($resultMessage->isSuccess() && !empty($message['message']['files']))
				{
					$files = $this->saveFiles($message['message']['files']);
					if(!$files->isSuccess())
						$resultMessage->addErrors($files->getErrors());
					$message['message']['files'] = $files->getData();
				}

				if($resultMessage->isSuccess() && !empty($message['message']['failed_big_file']))
					$message['message']['text'] = Loc::getMessage("IMCONNECTOR_WARNING_LARGE_FILE") . $message['message']['text'];

				if($resultMessage->isSuccess() &&
					(empty($message['user']) || empty($message['chat']) ||
						(Library::isEmpty($message['message']['text']) &&
							empty($message['message']['files']) &&
							$message['type_message'] !== 'message_del' &&
							$message['type_message'] !== 'typing_start'
						)
					))
					$resultMessage->addError(new Error(Loc::getMessage('IMCONNECTOR_NOT_ALL_THE_REQUIRED_DATA'), Library::ERROR_IMCONNECTOR_NOT_ALL_THE_REQUIRED_DATA, __METHOD__, $message));

				if($resultMessage->isSuccess() && !Library::isEmpty($message['message']['text']))
				{
					if (\Bitrix\Main\Application::isUtfMode())
					{
						$message['message']['text'] = Emoji::decode($message['message']['text']);
					}
					else
					{
						$message['message']['text'] = preg_replace('/:([A-F0-9]{8}):/i', '(emoji)', $message['message']['text']);
					}
				}

				if ($message['message']['disable_crm'] === 'Y' && $resultMessage->isSuccess())
				{
					$message['extra']['disable_tracker'] = 'Y';
					unset($message['message']['disable_crm']);
				}

				if($resultMessage->isSuccess())
				{
					unset($typeMessage);

					if(!empty($message['type_message']))
					{
						$typeMessage = $message['type_message'];
						unset($message['type_message']);
					}

					$connectorReal = Connector::getConnectorRealId($this->connector);

					if(empty($typeMessage) || $typeMessage == 'message' || $typeMessage == 'message_update')
					{
						//Hack is designed for the Microsoft Bot Framework
						BotFramework::furtherMessageProcessing($message, $userSourceData, $this->connector, $connectorReal);

						if(empty($typeMessage) || $typeMessage == 'message')
						{
							//OLX hack
							$message = Olx::newMessageProcessing($message, $this->connector, $this->line);

							$event = $this->sendEvent($message, Library::EVENT_RECEIVED_MESSAGE);
						}
						else
						{
							$event = $this->sendEvent($message, Library::EVENT_RECEIVED_MESSAGE_UPDATE);
						}
					}
					elseif($typeMessage == 'post')
					{
						$event = $this->sendEvent($message, Library::EVENT_RECEIVED_POST);
					}
					elseif($typeMessage == 'post_update')
					{
						$event = $this->sendEvent($message, Library::EVENT_RECEIVED_POST_UPDATE);
					}
					elseif($typeMessage == 'message_del')
					{
						$event = $this->sendEvent($message, Library::EVENT_RECEIVED_MESSAGE_DEL);
					}
					elseif($typeMessage === 'typing_start')
					{
						$event = $this->sendEvent($message, Library::EVENT_RECEIVED_TYPING_STATUS);
					}

					if(!empty($event) && !$event->isSuccess())
						$resultMessage->addErrors($event->getErrors());
				}

				if($resultMessage->isSuccess() && (!isset($event) || $event->isSuccess()))
				{
					$this->data[$cell]['SUCCESS'] = true;
				}
				else
				{
					$this->data[$cell]['SUCCESS'] = false;

					if(isset($event) && $event->isSuccess())
					{
						$this->data[$cell]['ERRORS'] = $event->getErrorMessages();
					}
					elseif(!$resultMessage->isSuccess())
					{
						$this->data[$cell]['ERRORS'] = $resultMessage->getErrorMessages();
					}
				}

				$this->data[$cell] = array_merge($this->data[$cell], $message);
			}
			//end foreach

			$result->setResult($this->data);
		}
		return $result;
	}

	/**
	 * Data processing user and returns the id of the registered user of the system.
	 *
	 * @param array $user An array describing the user.
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function processingUser($user): Result
	{
		$result = new Result();

		$userFieldsResult = Connector::getUserByUserCode($user, $this->connector);
		$cUser = new \CUser;

		if ($userFieldsResult->isSuccess())
		{
			$userFields = $userFieldsResult->getResult();

			if (is_array($userFields))
			{
				$userId = $userFields['ID'];

				if ($userFields['MD5'] !== md5(serialize($user)))
				{
					$fields = Connector::initializationConnectorHandler($this->connector)->preparationUserFields($user, $userId);
					if(!empty($fields))
					{
						$cUser->Update($userId, $fields);
					}
				}
			}
		}
		else
		{
			$fields = Connector::initializationConnectorHandler($this->connector)->preparationNewUserFields($user);

			$userId = $cUser->Add($fields);
		}

		if (empty($userId))
		{
			$result->addError(new Error(Loc::getMessage('IMCONNECTOR_PROXY_NO_ADD_USER'), Library::ERROR_CONNECTOR_PROXY_NO_ADD_USER, __METHOD__));
		}
		else
		{
			$result->setResult($userId);
		}

		return $result;
	}

	/**
	 * File download.
	 *
	 * @param array $file Description array file.
	 * @return array|bool
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	public static function downloadFile($file)
	{
		if(empty($file['url']))
		{
			$file = false;
		}
		else
		{
			$httpClient = new HttpClient(
				[
					'redirect' => true,
					'disableSslVerification' => true
				]
			);
			$httpClient->setHeader('User-Agent', 'Bitrix Connector Client');

			if(!empty($file['headers']) && is_array($file['headers']))
			{
				foreach ($file['headers'] as $header)
				{
					$httpClient->setHeader($header['name'], $header['value']);
				}
			}

			if(Library::isEmpty($file['name']))
			{
				$fileName = Library::getNameFile($file['url']);
			}
			else
			{
				$fileName = $file['name'];
			}

			$tempFilePath = \CFile::GetTempName('', $fileName);

			if($httpClient->download($file['url'], $tempFilePath) && $httpClient->getStatus() == '200')
			{
				if(!empty($file['type']))
					$type = $file['type'];
				else
					$type = $httpClient->getHeaders()->get('Content-Type');

				//Correct handling of links with redirect
				$effectiveUrl = $httpClient->getEffectiveUrl();
				if(
					Library::isEmpty($file['name']) &&
					$effectiveUrl != $file['url']
				)
				{
					$fileName = Library::getNameFile($effectiveUrl);
				}

				if(empty($type) || $type == 'application/octet-stream')
				{
					$fileTemp = new File($tempFilePath);
					$type = $fileTemp->getContentType();
				}

				//The definition of the file extension, it is not specified.
				if(strpos($fileName, '.') === false)
				{
					if(Library::$mimeTypeAssociationExtension[$type])
						$fileName = $fileName . Library::$mimeTypeAssociationExtension[$type];
				}

				if(empty($type))
					$type = 'application/octet-stream';

				if(Library::isEmpty($file['description']))
					$description = '';
				else
					$description = $file['description'];

				$file = array(
					"name" => $fileName,
					"tmp_name" => $tempFilePath,
					"type" => $type,
					"description" => $description,
					"MODULE_ID" => Library::MODULE_ID
				);
			}
			else
			{
				$file = false;
			}
		}

		return $file;
	}

	/**
	 * Saving files.
	 *
	 * @param array $files Array with list of files.
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	private function saveFiles($files): Result
	{
		$result = new Result();
		$resultSaveFiles = [];

		foreach ($files as $cell => $file)
		{
			$resultSaveFile = Connector::initializationConnectorHandler($this->connector)->saveFile($file);
			if(!empty($resultSaveFile))
			{
				$resultSaveFiles[$cell] = $resultSaveFile;
			}
		}

		$result->setData($resultSaveFiles);

		return $result;
	}

	/**
	 * Generation of an event message is received.
	 *
	 * @param array $data An array describing the message.
	 * @param string $eventName The name of the event.
	 * @return Result
	 */
	private function sendEvent($data, $eventName = Library::EVENT_RECEIVED_MESSAGE): Result
	{
		$result = new Result();
		$data["connector"] = $this->connector;
		$data["line"] = $this->line;

		$event = new Event(Library::MODULE_ID, $eventName, $data);
		$event->send();

		return $result;
	}
}