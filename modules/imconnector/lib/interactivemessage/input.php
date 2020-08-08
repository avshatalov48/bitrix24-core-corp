<?php
namespace Bitrix\ImConnector\InteractiveMessage;

use Bitrix\ImConnector\Connector;

/**
 * Class Base
 * @package Bitrix\ImConnector\InteractiveMessage
 */
class Input
{
	protected $message;
	protected $isProcessing = false;
	protected $idConnector = '';
	public const URL_ACTIVITY = '/crm/activity/?open_view=#activity_id#';

	/**
	 * @param string $idConnector
	 * @return Input
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function initialization($idConnector = ''): Input
	{
		$class = __CLASS__;

		if(
			!empty($idConnector) &&
			Connector::isConnector($idConnector)
		)
		{
			$idConnector = Connector::getConnectorRealId($idConnector);
			$className = "Bitrix\\ImConnector\\InteractiveMessage\\Connectors\\" . $idConnector . "\\Input";
			if(class_exists($className))
			{
				$class = $className;
			}
		}

		return new $class($idConnector);
	}

	/**
	 * Input constructor.
	 * @param $idConnector
	 */
	protected function __construct($idConnector)
	{
		$this->idConnector = $idConnector;
	}

	/**
	 * @param $message
	 * @return Input
	 */
	public function setMessage($message): Input
	{
		$this->message = $message;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getMessage(): array
	{
		return $this->message;
	}

	/**
	 * @return array
	 */
	public function processing(): array
	{
		return $this->message;
	}

	/**
	 * @return bool
	 */
	public function isSendMessage(): bool
	{
		$result = true;

		if($this->isProcessing === true)
		{
			$result = false;
		}

		return $result;
	}

	/**
	 * @param $activity_id
	 * @return string
	 */
	protected static function getActivityUrl($activity_id): string
	{
		return str_replace('#activity_id#', $activity_id, self::URL_ACTIVITY);
	}
}