<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Sender\Trigger;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\EventResult;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sender\ContactTable;
use Bitrix\Sender\Integration;
use Bitrix\Sender\Internals\Model;
use Bitrix\Sender\MailEventHandler;
use Bitrix\Sender\MailingChainTable;
use Bitrix\Sender\MailingTable;
use Bitrix\Sender\MailingTriggerTable;
use Bitrix\Sender\PostingRecipientTable;
use Bitrix\Sender\PostingTable;
use Bitrix\Sender\Recipient;
use Bitrix\Sender\Subscription;

class Manager
{
	public static $debug = false;
	public static $postingId = null;

	/**
	 * @param mixed
	 * @return void
	 */
	public static function handleEvent()
	{
		$args = func_get_args();
		if (isset($args[0]) && $args[0] instanceof Event)
		{
			$event = $args[0];
			/* @var Event $event */
			$moduleId = $event->getModuleId();
			$eventType = $event->getEventType();
			$eventData = $event->getParameters();
		}
		else
		{
			global $BX_MODULE_EVENT_LAST;
			$moduleId = $BX_MODULE_EVENT_LAST['FROM_MODULE_ID'];
			$eventType = $BX_MODULE_EVENT_LAST['MESSAGE_ID'];
			$eventData = $args;
		}

		static::processEvent(array(
			'MODULE_ID' => $moduleId,
			'EVENT_TYPE' => $eventType,
			'EVENT_DATA' => $eventData,
			'FILTER' => array(),
		));
	}

	/**
	 * @param array $params
	 * @return void
	 */
	protected static function processEvent($params)
	{
		$moduleId = $params['MODULE_ID'];
		$eventType = $params['EVENT_TYPE'];
		$eventData = $params['EVENT_DATA'];

		$filter = array(
			'=MAILING_CHAIN.MAILING.ACTIVE' => 'Y',
			'=MAILING_CHAIN.IS_TRIGGER' => 'Y',
			'=MAILING_CHAIN.STATUS' => array(MailingChainTable::STATUS_WAIT, MailingChainTable::STATUS_SEND),
			'=EVENT' => $moduleId.'/'.$eventType
		);
		if(isset($params['FILTER']) && is_array($params['FILTER']))
		{
			$filter = $filter + $params['FILTER'];
		}

		$chainDb = MailingTriggerTable::getList(array(
			'select' => array(
				'ENDPOINT',
				'SITE_ID' => 'MAILING_CHAIN.MAILING.SITE_ID',
				'ID' => 'MAILING_CHAIN.ID',
				'MAILING_ID' => 'MAILING_CHAIN.MAILING_ID',
				'PARENT_ID' => 'MAILING_CHAIN.PARENT_ID',
				'POSTING_ID' => 'MAILING_CHAIN.POSTING_ID',
				'TIME_SHIFT' => 'MAILING_CHAIN.TIME_SHIFT',
				'STATUS' => 'MAILING_CHAIN.STATUS',
				'AUTO_SEND_TIME' => 'MAILING_CHAIN.AUTO_SEND_TIME'
			),
			'filter' => $filter,
			'order' => array('MAILING_CHAIN_ID' => 'ASC', 'IS_TYPE_START' => 'ASC')
		));
		while($chain = $chainDb->fetch())
		{
			$settings = new Settings($chain['ENDPOINT']);
			$trigger = static::getOnce($settings->getEndpoint());
			if(!$trigger) continue;

			$trigger->setSiteId($chain['SITE_ID']);
			$trigger->setFields($settings->getFields());
			$trigger->setParams(array('CHAIN' => $chain, 'EVENT' => $eventData));

			// mark trigger as first run for process old data
			$runForOldData = ($trigger->canRunForOldData() && $settings->canRunForOldData() && !$settings->wasRunForOldData());
			$trigger->setRunForOldData($runForOldData);

			// run trigger filter
			if(!$trigger->filter()) continue;

			//add recipient to posting
			static::$postingId = null;
			$recipientDb = $trigger->getRecipientResult();
			while($recipient = $recipientDb->fetch())
			{
				if($settings->isTypeStart())
				{
					static::addRecipient($chain, $settings, $recipient);
				}
				else
				{
					static::stop($chain, $recipient, true);
				}
			}

			// mark mailing trigger fields as first run for process old data
			if($runForOldData)
			{
				MailingTable::setWasRunForOldData($chain['MAILING_ID'], $runForOldData);
			}

			if($settings->isTypeStart())
			{
				// prevent email event
				if($settings->isPreventEmail())
				{
					static::preventMailEvent($trigger->getMailEventToPrevent());
				}

				//start sending of mailing chain
				static::send($chain);
			}

		}

		//return $data;
	}

	/**
	 * @param array $chain
	 * @param array $data
	 * @param bool $setGoal
	 * @return void
	 */
	protected static function stop($chain, $data, $setGoal)
	{
		if(!$data || empty($data['EMAIL']))
		{
			return;
		}

		$code = $data['EMAIL'];
		$typeId = Recipient\Type::detect($data['EMAIL']);
		$code = Recipient\Normalizer::normalize($code, $typeId);

		// if mailing continue, then stop it
		$recipientDb = PostingRecipientTable::getList(array(
			'select' => array('ID', 'ROOT_ID', 'POSTING_ID', 'STATUS', 'POSTING_STATUS' => 'POSTING.STATUS'),
			'filter' => array(
				'=CONTACT.CODE' => $code,
				'=CONTACT.TYPE_ID' => $typeId,
				'=POSTING.MAILING_ID' => $chain['MAILING_ID'],
				'=STATUS' => array(
					PostingRecipientTable::SEND_RESULT_NONE,
					PostingRecipientTable::SEND_RESULT_WAIT,
				)
			),
			'limit' => 1
		));
		if($recipient = $recipientDb->fetch())
		{
			// if mailing continue, then stop it and the next was riched
			$updateFields = array('STATUS' => PostingRecipientTable::SEND_RESULT_DENY);
			Model\Posting\RecipientTable::update($recipient['ID'], $updateFields);

			// change status of posting if all emails sent
			if(!in_array($recipient['POSTING_STATUS'], array(PostingTable::STATUS_NEW, PostingTable::STATUS_PART)))
			{
				$recipientCountDb = PostingRecipientTable::getList(array(
					'select' => array('POSTING_ID'),
					'filter' => array(
						'=POSTING_ID' => $recipient['POSTING_ID'],
						'=STATUS' => array(
							PostingRecipientTable::SEND_RESULT_NONE,
							PostingRecipientTable::SEND_RESULT_WAIT,
						)
					),
					'limit' => 1
				));
				if(!$recipientCountDb->fetch())
				{
					Model\PostingTable::update($recipient['POSTING_ID'], ['STATUS' => PostingTable::STATUS_SENT]);
				}
			}
		}

		if(!$setGoal)
		{
			return;
		}

		// set flag of taking the goal to last success sending
		$recipientDb = PostingRecipientTable::getList(array(
			'select' => array('ID', 'DATE_DENY'),
			'filter' => array(
				'=CONTACT.CODE' => $code,
				'=CONTACT.TYPE_ID' => $typeId,
				'=POSTING.MAILING_ID' => $chain['MAILING_ID'],
				'=STATUS' => array(
					PostingRecipientTable::SEND_RESULT_SUCCESS
				)
			),
			'order' => array('DATE_SENT' => 'DESC', 'ID' => 'DESC'),
			'limit' => 1
		));
		if($recipient = $recipientDb->fetch())
		{
			if(empty($recipient['DATE_DENY']))
			{
				Model\Posting\RecipientTable::update($recipient['ID'], ['DATE_DENY' => new DateTime]);
			}
		}
	}

	/**
	 * @param array $chain
	 * @return void
	 */
	protected static function send($chain)
	{
		// set send status
		if(empty($chain['ID']))
			return;

		if(empty($chain['POSTING_ID']))
		{
			if(empty(static::$postingId))
			{
				return;
			}

			$updateFields['POSTING_ID'] = static::$postingId;
		}

		$updateFields = array();
		if($chain['STATUS'] == MailingChainTable::STATUS_WAIT)
		{
			$autoSendTime = new DateTime;
			$autoSendTime->add($chain['TIME_SHIFT'] . ' minutes');
			$updateFields['STATUS'] = MailingChainTable::STATUS_SEND;
			$updateFields['AUTO_SEND_TIME'] = $autoSendTime;
		}
		else
		{
			$updateFields['AUTO_SEND_TIME'] = $chain['AUTO_SEND_TIME'];
			$updateFields['STATUS'] = $chain['STATUS'];
		}

		if(count($updateFields) > 0)
		{
			Model\LetterTable::update($chain['ID'], $updateFields);
		}
	}

	/**
	 * @param array $emailEvent
	 * @return void
	 */
	protected static function preventMailEvent(array $emailEvent)
	{
		if(isset($emailEvent['EVENT_NAME']) && $emailEvent['EVENT_NAME'] <> '')
		{
			if(!empty($emailEvent['FILTER']) && is_array($emailEvent['FILTER']))
			{
				MailEventHandler::prevent($emailEvent['EVENT_NAME'], $emailEvent['FILTER']);
			}
		}
	}

	/**
	 * @param array $chain
	 * @param Settings $settings
	 * @param array $data
	 * @return void
	 */
	protected static function addRecipient($chain, $settings, $data)
	{
		if(!$data || empty($data['EMAIL']))
		{
			return;
		}

		$code = $data['EMAIL'];
		$typeId = Recipient\Type::detect($data['EMAIL']);
		$code = Recipient\Normalizer::normalize($code, $typeId);

		// check email to unsubscription
		if(Subscription::isUnsubscibed($chain['MAILING_ID'], $code))
		{
			return;
		}

		// if this is event for child
		if(!empty($chain['PARENT_ID']))
		{
			$recipientDb = PostingRecipientTable::getList(array(
				'select' => array('ID', 'STATUS'),
				'filter' => array(
					'=CONTACT.CODE' => $code,
					'=CONTACT.TYPE_ID' => $typeId,
					'=POSTING.MAILING_CHAIN_ID' => $chain['ID'],
					'=POSTING.STATUS' => array(PostingTable::STATUS_NEW, PostingTable::STATUS_PART)
				)
			));

			while($recipient = $recipientDb->fetch())
			{
				// check if event should came or didn't came
				$statusNew = null;
				if($settings->isEventOccur() && $recipient['STATUS'] == PostingRecipientTable::SEND_RESULT_WAIT)
				{
					$statusNew = PostingRecipientTable::SEND_RESULT_NONE;
				}
				elseif(!$settings->isEventOccur() && $recipient['STATUS'] == PostingRecipientTable::SEND_RESULT_NONE)
				{
					$statusNew = PostingRecipientTable::SEND_RESULT_WAIT;
				}

				if($statusNew !== null)
				{
					Model\Posting\RecipientTable::update(
						$recipient['ID'],
						['STATUS' => $statusNew]
					)->isSuccess();
				}
			}
		}
		else
		{
			// check email to have not finished mailing
			$recipientExistsDb = PostingRecipientTable::getList(array(
				'select' => array('ID'),
				'filter' => array(
					'=CONTACT.CODE' => $code,
					'=CONTACT.TYPE_ID' => $typeId,
					'=POSTING.MAILING_ID' => $chain['MAILING_ID'],
					'=STATUS' => array(
						PostingRecipientTable::SEND_RESULT_NONE,
						PostingRecipientTable::SEND_RESULT_WAIT,
					)
				),
				'limit' => 1
			));
			if($recipientExistsDb->fetch())
			{
				return;
			}

			if(static::$postingId)
			{
				$postingId = static::$postingId;
			}
			else
			{
				$postingAddDb = PostingTable::add(array(
					'MAILING_ID' => $chain['MAILING_ID'],
					'MAILING_CHAIN_ID' => $chain['ID'],
				));
				if(!$postingAddDb->isSuccess()) return;

				$postingId = $postingAddDb->getId();
				static::$postingId = $postingId;
			}

			$contact = ContactTable::getRow(['filter' => [
				'=CODE' => $code,
				'=TYPE_ID' => $typeId,
			]]);
			if (!$contact)
			{
				$contact = [
					'CODE' => $code,
					'TYPE_ID' => $typeId,
					'NAME' => !empty($data['NAME']) ? $data['NAME'] : null,
					'USER_ID' => !empty($data['USER_ID']) ? $data['USER_ID'] : null,
				];
				$contact['ID'] = ContactTable::add($contact)->getId();
			}

			PostingRecipientTable::add([
				'POSTING_ID' => $postingId,
				'CONTACT_ID' => $contact['ID'],
				'FIELDS' => !empty($data['FIELDS']) ? $data['FIELDS'] : null,
				'USER_ID' => !empty($data['USER_ID']) ? $data['USER_ID'] : null,
			])->isSuccess();
		}
	}

	/**
	 * @param bool $activate
	 * @return void
	 */
	public static function activateAllHandlers($activate = true)
	{
		static::actualizeHandlerForChild($activate);

		$itemDb = MailingTriggerTable::getList(array(
			'select' => array('ENDPOINT', 'MAILING_CHAIN_ID'),
			'filter' => array(
				'=MAILING_CHAIN.IS_TRIGGER' => 'Y',
				'=MAILING_CHAIN.MAILING.ACTIVE' => 'Y',
			)
		));
		while($item = $itemDb->fetch())
		{
			if(!is_array($item['ENDPOINT']))
			{
				continue;
			}

			if($activate)
			{
				MailingTriggerTable::actualizeHandlers($item['MAILING_CHAIN_ID'], $item['ENDPOINT'], null);
			}
			else
			{
				MailingTriggerTable::actualizeHandlers($item['MAILING_CHAIN_ID'], null, $item['ENDPOINT']);
			}

			$settings = new Settings($item['ENDPOINT']);
			if(!$settings->isClosedTrigger() && $settings->getEventModuleId() && $settings->getEventType())
			{
				static::actualizeHandler(
					array('MODULE_ID' => $settings->getEventModuleId(), 'EVENT_TYPE' => $settings->getEventType()),
					$activate
				);
			}
		}
	}

	/**
	 * @param array $params
	 * @param bool $activate
	 * @return void
	 */
	public static function actualizeHandler(array $params, $activate = null)
	{
		$moduleId = $params['MODULE_ID'];
		$eventType = $params['EVENT_TYPE'];
		$calledBeforeChange = $params['CALLED_BEFORE_CHANGE'];

		if($params['IS_CLOSED_TRIGGER'])
		{

			return;
		}

		if($activate === null)
		{
			// if actualizing will be called before deleting record (or updating record with clearing field),
			// query will select this record.
			// In this reason, it should be considered - check if more 1 or 0 selected rows.
			if($calledBeforeChange)
				$minRowsCount = 1;
			else
				$minRowsCount = 0;

			$existsDb = MailingTriggerTable::getList(array(
				'select' => array('MAILING_CHAIN_ID'),
				'filter' => array(
					'=EVENT' => $moduleId.'/'.$eventType,
					'=MAILING_CHAIN.IS_TRIGGER' => 'Y',
					'=MAILING_CHAIN.MAILING.ACTIVE' => 'Y',
					//'=STATUS' => array(MailingChainTable::STATUS_WAIT, MailingChainTable::STATUS_SEND)
				),
				'group' => array('MAILING_CHAIN_ID'),
				'limit' => 2
			));
			$rowsCount = 0;
			while($existsDb->fetch()) $rowsCount++;

			if($rowsCount > $minRowsCount)
			{
				$activate = true;
			}
			else
			{
				$activate = false;
			}
		}

		if($activate)
		{
			EventManager::getInstance()->registerEventHandler(
				$moduleId, $eventType, 'sender', __CLASS__, 'handleEvent'
			);
		}
		else
		{
			EventManager::getInstance()->unRegisterEventHandler(
				$moduleId, $eventType, 'sender', __CLASS__, 'handleEvent'
			);
		}
	}

	/**
	 * @param array $endpointList
	 * @return array
	 */
	public static function getFieldsFromEndpoint(array $endpointList)
	{
		$resultList = array();
		foreach($endpointList as $endpoint)
		{
			$resultList[$endpoint['MODULE_ID']][$endpoint['CODE']][] = $endpoint['FIELDS'];
		}
		
		return $resultList;
	}

	/**
	 * @param array $fields
	 * @return array|null
	 */
	public static function getEndpointFromFields(array $fields)
	{
		$endpointList = null;
		$fieldsTmp = array();

		foreach($fields as $moduleId => $connectorSettings)
		{
			if (is_numeric($moduleId))
			{
				$moduleId = '';
			}

			foreach($connectorSettings as $connectorCode => $connectorFields)
			{
				foreach($connectorFields as $k => $fields)
				{
					if (isset($fieldsTmp[$moduleId][$connectorCode][$k]) && is_array($fields))
						$fieldsTmp[$moduleId][$connectorCode][$k] = array_merge($fieldsTmp[$moduleId][$connectorCode][$k], $fields);
					else
						$fieldsTmp[$moduleId][$connectorCode][$k] = $fields;
				}
			}
		}

		foreach($fieldsTmp as $moduleId => $connectorSettings)
		{
			if(is_numeric($moduleId)) $moduleId = '';
			foreach($connectorSettings as $connectorCode => $connectorFields)
			{
				foreach($connectorFields as $fields)
				{
					$endpoint = array();
					$endpoint['MODULE_ID'] = $moduleId;
					$endpoint['CODE'] = $connectorCode;
					$endpoint['FIELDS'] = $fields;
					$endpointList[] = $endpoint;
				}
			}
		}

		return $endpointList;
	}

	/**
	 * Return array of instances of connector by endpoints array.
	 *
	 * @param array
	 * @return Base[]
	 */
	public static function getList(array $endpointList = null)
	{
		$triggerList = array();

		$classList = static::getClassList($endpointList);
		foreach($classList as $classDescription)
		{
			/** @var Base $trigger */
			$trigger = new $classDescription['CLASS_NAME'];
			$trigger->setModuleId($classDescription['MODULE_ID']);
			$triggerList[] = $trigger;
		}

		return $triggerList;
	}

	/**
	 * Return instance of trigger by endpoint array.
	 *
	 * @param array
	 * @return Base|null
	 */
	public static function getOnce(array $endpoint)
	{
		$trigger = null;
		$triggerList = static::getList(array($endpoint));
		/** @var Base $trigger */
		foreach($triggerList as $trigger)
		{
			break;
		}

		return $trigger;
	}

	/**
	 * Return array of triggers information by endpoints array.
	 *
	 * @param array $endpointList
	 * @return array
	 */
	public static function getClassList(array $endpointList = null)
	{
		$resultList = array();
		$moduleIdFilter = null;
		$moduleConnectorFilter = null;

		if($endpointList)
		{
			$moduleIdFilter = array();
			foreach($endpointList as $endpoint)
			{
				$moduleIdFilter[] = $endpoint['MODULE_ID'];
				$moduleConnectorFilter[$endpoint['MODULE_ID']][] = $endpoint['CODE'];
			}
		}

		$data = array();
		$event = new Event('sender', 'OnTriggerList', array($data), $moduleIdFilter);
		$event->send();

		foreach ($event->getResults() as $eventResult)
		{
			if ($eventResult->getType() == EventResult::ERROR)
			{
				continue;
			}

			$eventResultParameters = $eventResult->getParameters();

			if($eventResultParameters && array_key_exists('TRIGGER', $eventResultParameters))
			{
				$connectorClassNameList = $eventResultParameters['TRIGGER'];
				if(!is_array($connectorClassNameList))
					$connectorClassNameList = array($connectorClassNameList);

				foreach($connectorClassNameList as $connectorClassName)
				{
					if(!is_subclass_of($connectorClassName,  '\Bitrix\Sender\Trigger'))
					{
						continue;
					}

					$connectorCode = (new $connectorClassName)->getCode();
					if($moduleConnectorFilter && !in_array($connectorCode, $moduleConnectorFilter[$eventResult->getModuleId()]))
					{
						continue;
					}

					$isClosedTrigger = false;
					if(is_subclass_of($connectorClassName,  '\Bitrix\Sender\TriggerConnectorClosed'))
						$isClosedTrigger = true;

					$connectorName = (new $connectorClassName)->getName();
					$connectorRequireConfigure = (new $connectorClassName)->requireConfigure();
					$resultList[] = array(
						'MODULE_ID' => $eventResult->getModuleId(),
						'CLASS_NAME' => $connectorClassName,
						'CODE' => $connectorCode,
						'NAME' => $connectorName,
						'REQUIRE_CONFIGURE' => $connectorRequireConfigure,
						'IS_CLOSED' => $isClosedTrigger,
					);
				}
			}
		}

		if(!empty($resultList))
			usort($resultList, array(__CLASS__, 'sort'));

		return $resultList;
	}

	/**
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	public static function sort($a, $b)
	{
		if ($a['NAME'] == $b['NAME'])
			return 0;

		return ($a['NAME'] < $b['NAME']) ? -1 : 1;
	}

	/**
	 * @param string $mess
	 * @return void
	 */
	public static function debug($mess)
	{
		if(static::$debug)
		{
			Debug::writeToFile($mess, "", "__bx_sender_trigger.log");
		}
	}

	/**
	* @param string $moduleId
	* @param string $eventType
	* @param int $chainId
	* @return string
	*/
	public static function getClosedEventAgentName($moduleId, $eventType, $chainId)
	{
		return '\Bitrix\Sender\TriggerManager::fireClosedEventAgent("' . $moduleId . '","' . $eventType .'","' . $chainId .'");';
	}

	/**
	* @param string $moduleId
	* @param string $eventType
	* @param int $chainId
	* @return string
	*/
	public static function fireClosedEventAgent($moduleId, $eventType, $chainId)
	{
		if(!empty($moduleId) && !empty($eventType) && !empty($chainId))
		{
			static::processEvent(array(
				'MODULE_ID' => $moduleId,
				'EVENT_TYPE' => $eventType,
				'EVENT_DATA' => array(),
				'FILTER' => array(
					'=MAILING_CHAIN.ID' => $chainId
				),
			));

			return static::getClosedEventAgentName($moduleId, $eventType, $chainId);
		}
		else
		{
			return '';
		}
	}


	/**
	 * @param bool $activate
	 * @return void
	 */
	public static function actualizeHandlerForChild($activate = null)
	{
		$eventHandlerList = array(
			array(
				'sender',
				'OnAfterMailingChainSend',
				'sender',
				__CLASS__,
				'onAfterMailingChainSend'
			),
			array(
				'sender',
				'OnAfterPostingSendRecipient',
				'sender',
				__CLASS__,
				'onAfterPostingSendRecipient'
			)
		);

		if($activate === null)
		{
			$existsDb = MailingChainTable::getList(array(
				'select' => array('ID'),
				'filter' => array(
					'!PARENT_ID' => null,
					'=IS_TRIGGER' => 'Y',
					'=MAILING.ACTIVE' => 'Y',
					//'=STATUS' => array(MailingChainTable::STATUS_WAIT, MailingChainTable::STATUS_SEND)
				),
				'limit' => 1
			));
			if($existsDb->fetch())
			{
				$activate = true;
			}
			else
			{
				$activate = false;
			}
		}

		if($activate === true)
		{
			$eventManager = EventManager::getInstance();
			foreach($eventHandlerList as $h)
				$eventManager->registerEventHandler($h[0],$h[1],$h[2],$h[3],$h[4]);
		}
		elseif($activate === false)
		{
			$eventManager = EventManager::getInstance();
			foreach($eventHandlerList as $h)
				$eventManager->unRegisterEventHandler($h[0],$h[1],$h[2],$h[3],$h[4]);
		}
	}

	/**
	 * @param Event $event
	 * @return void
	 */
	public static function onAfterMailingChainSend(Event $event)
	{
		$data = $event->getParameter(0);

		if(!$data || empty($data['MAILING_CHAIN']['ID']))
			return;

		$childChainDb = MailingChainTable::getList(array(
			'select' => array(
				'ID',
				'MAILING_ID',
				'PARENT_ID',
				'POSTING_ID',
				'STATUS',
				'TIME_SHIFT'
			),
			'filter' => array(
				'=MAILING.ACTIVE' => 'Y',
				'=IS_TRIGGER' => 'Y',
				'=STATUS' => MailingChainTable::STATUS_WAIT,
				'=PARENT_ID' => $data['MAILING_CHAIN']['ID']
			)
		));
		while($childChain = $childChainDb->fetch())
		{
			$isSend = false;

			$settings = new Settings();
			if($settings->getEndpoint('CODE') == '')
			{
				// send certainly
				$isSend = true;
			}
			elseif($settings->isEventOccur())
			{
				// send if event occur
			}
			else
			{
				// send if event not occur
			}

			if(empty($childChain['POSTING_ID']) || $childChain['STATUS'] != MailingChainTable::STATUS_WAIT)
			{
				$isSend = false;
			}


			if($isSend)
			{
				static::send($childChain);
			}

		}
	}


	/**
	 * @param \Bitrix\Main\Event $event
	 * @return void
	 */
	public static function onAfterPostingSendRecipient(Event $event)
	{
		$data = $event->getParameter(0);

		if(!$data || !$data['SEND_RESULT'] || empty($data['POSTING']['MAILING_CHAIN_ID']))
			return;

		$chainId = $data['POSTING']['MAILING_CHAIN_ID'];
		$dataRecipient = $data['RECIPIENT'];

		static $mailingParams = array();
		if(!isset($mailingParams[$chainId]))
		{
			$mailingParams[$chainId] = array();

			$childChainDb = MailingChainTable::getList(array(
				'select' => array(
					'ID', 'MAILING_ID', 'PARENT_ID', 'POSTING_ID'
				),
				'filter' => array(
					'=MAILING.ACTIVE' => 'Y',
					'=IS_TRIGGER' => 'Y',
					'=STATUS' => array(MailingChainTable::STATUS_WAIT, MailingChainTable::STATUS_SEND),
					'=PARENT_ID' => $chainId
				)
			));
			while($childChain = $childChainDb->fetch())
			{
				// add posting
				$postingAddDb = PostingTable::add(array(
					'MAILING_ID' => $childChain['MAILING_ID'],
					'MAILING_CHAIN_ID' => $childChain['ID'],
				));
				if(!$postingAddDb->isSuccess())
				{
					continue;
				}

				$mailingParams[$chainId][] = array(
					'POSTING_ID' => $postingAddDb->getId(),
					'CHAIN' => $childChain,
				);
			}
		}

		if(empty($mailingParams[$chainId]))
		{
			return;
		}

		foreach($mailingParams[$chainId] as $chainKey => $mailingParamsItem)
		{
			$postingId = $mailingParamsItem['POSTING_ID'];
			$childChain = $mailingParamsItem['CHAIN'];

			// check email as unsubscribed
			// TODO: modify to accept RID
			if(Subscription::isUnsubscibed($childChain['MAILING_ID'], $data['RECIPIENT']['EMAIL']))
				continue;

			$recipient = array('POSTING_ID' => $postingId);
			$recipient['STATUS'] = PostingRecipientTable::SEND_RESULT_NONE;

			$recipient['CONTACT_ID'] = $dataRecipient['CONTACT_ID'];
			if(!empty($dataRecipient['FIELDS']))
			{
				$recipient['FIELDS'] = $dataRecipient['FIELDS'];
			}

			if(!empty($dataRecipient['ROOT_ID']))
			{
				$recipient['ROOT_ID'] = $dataRecipient['ROOT_ID'];
			}
			else
			{
				$recipient['ROOT_ID'] = $dataRecipient['ID'];
			}

			if(!empty($dataRecipient['USER_ID']))
			{
				$recipient['USER_ID'] = $dataRecipient['USER_ID'];
			}

			// add recipient
			PostingTable::addRecipient($recipient, true);
			if(empty($childChain['POSTING_ID']))
			{
				$chainUpdateDb = Model\LetterTable::update($childChain['ID'], array('POSTING_ID' => $postingId));
				if($chainUpdateDb->isSuccess())
				{
					$mailingParams[$chainId][$chainKey]['CHAIN']['POSTING_ID'] = $postingId;
				}
			}
		}

	}

	/**
	 * @param array $data
	 * @return void
	 */
	public static function onAfterRecipientUnsub($data)
	{
		static::stop(
			array('MAILING_ID' => $data['MAILING_ID']),
			array(
				'RECIPIENT_ID' => $data['RECIPIENT_ID'],
				'CONTACT_ID' => $data['CONTACT_ID'],
			),
			false
		);
	}

	/**
	 * @param array $data
	 * @return array
	 */
	public static function onTriggerList($data)
	{
		return Integration\EventHandler::onTriggerList($data);
	}
}