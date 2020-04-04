<?php
namespace Bitrix\ImConnector;

use \Bitrix\Main\Event,
	\Bitrix\Main\EventResult;

use \Bitrix\ImConnector\Rest\Helper,
	\Bitrix\ImConnector\Input\ReceivingMessage,
	\Bitrix\ImConnector\Input\DeactivateConnector,
	\Bitrix\ImConnector\Input\ReceivingStatusReading,
	\Bitrix\ImConnector\Input\ReceivingStatusDelivery;

class CustomConnectors
{
	const PREFIX = 'custom_';

	const DEFAULT_DEL_EXTERNAL_MESSAGES = true;
	const DEFAULT_EDIT_INTERNAL_MESSAGES = true;
	const DEFAULT_DEL_INTERNAL_MESSAGES = true;
	const DEFAULT_NEWSLETTER = true;
	const DEFAULT_NEED_SYSTEM_MESSAGES = true;
	const DEFAULT_NEED_SIGNATURE = true;
	const DEFAULT_CHAT_GROUP = false;

	/** @var array(\Bitrix\ImConnector\CustomConnectors) */
	private static $instance;
	private static $customConnectors = array();

	public static function getInstance()
	{
		if (empty(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct()
	{
		$event = new Event(Library::MODULE_ID, Library::EVENT_REGISTRATION_CUSTOM_CONNECTOR);
		$event->send();

		foreach ($event->getResults() as $eventResult)
		{
			if ($eventResult != EventResult::ERROR && $params = $eventResult->getParameters())
			{
				if (isset($params['ID']) && isset($params['NAME']) && isset($params['COMPONENT']) && isset($params['ICON']['DATA_IMAGE']))
				{
					self::$customConnectors[$params['ID']] = self::handlingValues($params);
				}
			}
		}

		$restConnectors = Helper::listRestConnector();

		foreach ($restConnectors as $restConnector)
		{
			if (isset($restConnector['ID']) && isset($restConnector['NAME']) && isset($restConnector['COMPONENT']) && isset($restConnector['ICON']['DATA_IMAGE']))
			{
				self::$customConnectors[$restConnector['ID']] = self::handlingValues($restConnector);
			}
		}
	}

	private static function handlingValues($data)
	{
		$result = array(
			'ID' => $data['ID'],
			'NAME' => $data['NAME'],
			'COMPONENT' => $data['COMPONENT'],
			'ICON' => $data['ICON']
		);

		if (isset($data['ICON_DISABLED']))
			$result['ICON_DISABLED'] = $data['ICON_DISABLED'];

		if (isset($data['DEL_EXTERNAL_MESSAGES']) && ($data['DEL_EXTERNAL_MESSAGES'] === true || $data['DEL_EXTERNAL_MESSAGES'] === false))
			$result['DEL_EXTERNAL_MESSAGES'] = $data['DEL_EXTERNAL_MESSAGES'];
		else
			$result['DEL_EXTERNAL_MESSAGES'] = self::DEFAULT_DEL_EXTERNAL_MESSAGES;

		if (isset($data['EDIT_INTERNAL_MESSAGES']) && ($data['EDIT_INTERNAL_MESSAGES'] === true || $data['EDIT_INTERNAL_MESSAGES'] === false))
			$result['EDIT_INTERNAL_MESSAGES'] = $data['EDIT_INTERNAL_MESSAGES'];
		else
			$result['EDIT_INTERNAL_MESSAGES'] = self::DEFAULT_EDIT_INTERNAL_MESSAGES;

		if (isset($data['DEL_INTERNAL_MESSAGES']) && ($data['DEL_INTERNAL_MESSAGES'] === true || $data['DEL_INTERNAL_MESSAGES'] === false))
			$result['DEL_INTERNAL_MESSAGES'] = $data['DEL_INTERNAL_MESSAGES'];
		else
			$result['DEL_INTERNAL_MESSAGES'] = self::DEFAULT_DEL_INTERNAL_MESSAGES;

		if (isset($data['NEWSLETTER']) && ($data['NEWSLETTER'] === true || $data['NEWSLETTER'] === false))
			$result['NEWSLETTER'] = $data['NEWSLETTER'];
		else
			$result['NEWSLETTER'] = self::DEFAULT_NEWSLETTER;

		if (isset($data['NEED_SYSTEM_MESSAGES']) && ($data['NEED_SYSTEM_MESSAGES'] === true || $data['NEED_SYSTEM_MESSAGES'] === false))
			$result['NEED_SYSTEM_MESSAGES'] = $data['NEED_SYSTEM_MESSAGES'];
		else
			$result['NEED_SYSTEM_MESSAGES'] = self::DEFAULT_NEED_SYSTEM_MESSAGES;

		if (isset($data['NEED_SIGNATURE']) && ($data['NEED_SIGNATURE'] === true || $data['NEED_SIGNATURE'] === false))
			$result['NEED_SIGNATURE'] = $data['NEED_SIGNATURE'];
		else
			$result['NEED_SIGNATURE'] = self::DEFAULT_NEED_SIGNATURE;

		if (isset($data['CHAT_GROUP']) && ($data['CHAT_GROUP'] === true || $data['CHAT_GROUP'] === false))
			$result['CHAT_GROUP'] = $data['CHAT_GROUP'];
		else
			$result['CHAT_GROUP'] = self::DEFAULT_CHAT_GROUP;

		return $result;
	}

	private function __clone()
	{

	}

	private function __wakeup()
	{

	}

	public function getCustomConnectors()
	{
		return self::$customConnectors;
	}

	public static function getListConnector()
	{
		$result = array();

		foreach (self::getInstance()->getCustomConnectors() as $connector)
			$result[$connector['ID']] = $connector['NAME'];

		return $result;
	}

	public static function getListConnectorReal()
	{
		return self::getListConnector();
	}

	public static function getListConnectorId()
	{
		$result = array();

		foreach (self::getInstance()->getCustomConnectors() as $connector)
			$result[] = $connector['ID'];

		return $result;
	}

	public static function getListComponentConnector()
	{
		$result = array();

		foreach (self::getInstance()->getCustomConnectors() as $connector)
			$result[$connector['ID']] = $connector['COMPONENT'];

		return $result;
	}

	public static function getListConnectorDelExternalMessages()
	{
		$result = array();

		foreach (self::getInstance()->getCustomConnectors() as $connector)
			if($connector['DEL_EXTERNAL_MESSAGES'] === true)
				$result[] = $connector['ID'];

		return $result;
	}

	public static function getListConnectorEditInternalMessages()
	{
		$result = array();

		foreach (self::getInstance()->getCustomConnectors() as $connector)
			if($connector['EDIT_INTERNAL_MESSAGES'] === true)
				$result[] = $connector['ID'];

		return $result;
	}

	public static function getListConnectorDelInternalMessages()
	{
		$result = array();

		foreach (self::getInstance()->getCustomConnectors() as $connector)
			if($connector['DEL_INTERNAL_MESSAGES'] === true)
				$result[] = $connector['ID'];

		return $result;
	}

	public static function getListConnectorNotNewsletter()
	{
		$result = array();

		foreach (self::getInstance()->getCustomConnectors() as $connector)
			if($connector['NEWSLETTER'] === false)
				$result[] = $connector['ID'];

		return $result;
	}

	public static function getListNotNeedSystemMessages()
	{
		$result = array();

		foreach (self::getInstance()->getCustomConnectors() as $connector)
			if($connector['NEED_SYSTEM_MESSAGES'] === false)
				$result[] = $connector['ID'];

		return $result;
	}

	public static function getListNotNeedSignature()
	{
		$result = array();

		foreach (self::getInstance()->getCustomConnectors() as $connector)
			if($connector['NEED_SIGNATURE'] === false)
				$result[] = $connector['ID'];

		return $result;
	}

	public static function getListChatGroup()
	{
		$result = array();

		foreach (self::getInstance()->getCustomConnectors() as $connector)
			if($connector['CHAT_GROUP'] === true)
				$result[] = $connector['ID'];

		return $result;
	}

	/**
	 * @param $connector
	 * @param $line
	 * @param $data
	 * @param $type
	 * @return Result
	 */
	protected static function setMessages($connector, $line, $data, $type)
	{
		self::getInstance();

		foreach ($data as $cell => $message)
		{
			$data[$cell]['type_message'] = $type;
		}

		$receivingHandlers = new ReceivingMessage($connector, $line, $data);
		$result = $receivingHandlers->receiving();

		return $result;
	}

	/**
	 * @param $connector
	 * @param $line
	 * @param $data
	 * @return Result
	 */
	public static function sendMessages($connector, $line, $data)
	{
		$result = self::setMessages($connector, $line, $data, 'message');

		return $result;
	}

	/**
	 * @param $connector
	 * @param $line
	 * @param $data
	 * @return Result
	 */
	public static function updateMessages($connector, $line, $data)
	{
		$result = self::setMessages($connector, $line, $data, 'message_update');

		return $result;
	}

	/**
	 * @param $connector
	 * @param $line
	 * @param $data
	 * @return Result
	 */
	public static function deleteMessages($connector, $line, $data)
	{
		$result = self::setMessages($connector, $line, $data, 'message_del');

		return $result;
	}

	/**
	 * @param $connector
	 * @param $line
	 * @param $data
	 * @return Result
	 */
	public static function sendStatusDelivery($connector, $line, $data)
	{
		$receivingHandlers = new ReceivingStatusDelivery($connector, $line, $data);
		$result = $receivingHandlers->receiving();

		return $result;
	}

	/**
	 * @param $connector
	 * @param $line
	 * @param $data
	 * @return Result
	 */
	public static function sendStatusReading($connector, $line, $data)
	{
		$receivingHandlers = new ReceivingStatusReading($connector, $line, $data);
		$result = $receivingHandlers->receiving();

		return $result;
	}

	/**
	 * @param $connector
	 * @param $line
	 * @return Result
	 */
	public static function deactivateConnectors($connector, $line)
	{
		$receivingHandlers = new DeactivateConnector($connector, $line);
		$result = $receivingHandlers->receiving();

		return $result;
	}

	public static function getStyleCss()
	{
		$result = '';

		foreach (self::getInstance()->getCustomConnectors() as $connector)
		{
			$style = '';

			if(!empty($connector["ICON"]["DATA_IMAGE"]))
			{
				$style = '.connector-icon-' . str_replace('.', '_', $connector['ID']) . ' {
	' . (!empty($connector["ICON"]["COLOR"])? 'background-color: ' . $connector["ICON"]["COLOR"] : '') . ';
	' . (!empty($connector["ICON"]["SIZE"])? 'background-size: ' . $connector["ICON"]["SIZE"] : '') . ';
	' . (!empty($connector["ICON"]["POSITION"])? 'background-position: ' . $connector["ICON"]["POSITION"] : '') . ';
	background-image: url(\'' . $connector["ICON"]["DATA_IMAGE"] . '\');
}
';
				$style .= '.ui-icon-service-' . str_replace('.', '_', $connector['ID']) . '>i {
	' . (!empty($connector["ICON"]["COLOR"])? 'background-color: ' . $connector["ICON"]["COLOR"] : '') . ';
	' . (!empty($connector["ICON"]["SIZE"])? 'background-size: ' . $connector["ICON"]["SIZE"] : '') . ';
	' . (!empty($connector["ICON"]["POSITION"])? 'background-position: ' . $connector["ICON"]["POSITION"] : '') . ';
	background-image: url(\'' . $connector["ICON"]["DATA_IMAGE"] . '\');
}
';
				$style .= '.imconnector-' . str_replace('.', '_', $connector['ID']) . '-background-color {
	' . (!empty($connector["ICON"]["COLOR"])? 'background-color: ' . $connector["ICON"]["COLOR"] : '') . ';
}
';
				$style .= '.intranet-' . str_replace('.', '_', $connector['ID']) . '-background-color {
	' . (!empty($connector["ICON"]["COLOR"])? 'background-color: ' . $connector["ICON"]["COLOR"] : '') . ';
}
';
			}

			if(!empty($style))
			{
				$result .= $style;
			}
		}

		return $result;
	}

	public static function getStyleCssDisabled()
	{
		$result = '';

		foreach (self::getInstance()->getCustomConnectors() as $connector)
		{
			$style = '.connector-icon-disabled.connector-icon-' . str_replace('.', '_', $connector['ID']) . ' {
	' . (!empty($connector["ICON_DISABLED"]["COLOR"])? 'background-color: ' . $connector["ICON_DISABLED"]["COLOR"] : 'background-color: #ebeff2') . ';
	' . (!empty($connector["ICON_DISABLED"]["SIZE"])? 'background-size: ' . $connector["ICON_DISABLED"]["SIZE"] : '') . ';
	' . (!empty($connector["ICON_DISABLED"]["POSITION"])? 'background-position: ' . $connector["ICON_DISABLED"]["POSITION"] : '') . ';
	' . (!empty($connector["ICON_DISABLED"]["DATA_IMAGE"])? 'background-image: url(\'' . $connector["ICON_DISABLED"]["DATA_IMAGE"] . '\'' : '') . ');
}
';

			if(!empty($style))
			{
				$result .= $style;
			}
		}

		return $result;
	}
}