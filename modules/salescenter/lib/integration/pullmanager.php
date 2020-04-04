<?

namespace Bitrix\SalesCenter\Integration;

use Bitrix\SalesCenter\Driver;

class PullManager extends Base
{
	const EVENT_CONNECT = 'SETCONNECTEDSITE';
	const EVENT_LANDING_PUBLIC = 'LANDINGPUBLICATION';
	const EVENT_LANDING_UNPUBLIC = 'LANDINGUNPUBLICATION';
	const EVENT_ORDER_ADD = 'ORDERSAVED';

	/**
	 * @return bool|string
	 */
	public function subscribeOnLandingPublication()
	{
		return $this->subscribeOnEvent(static::EVENT_LANDING_PUBLIC);
	}

	/**
	 * @param $landingId
	 * @return bool
	 */
	public function sendLandingPublicationEvent($landingId)
	{
		$params = ['landingId' => $landingId];
		$orderPublicUrlInfo = LandingManager::getInstance()->getOrderPublicUrlInfo();
		// event comes before actual publication
		$isOrderPublicUrlAvailable = ($orderPublicUrlInfo && $orderPublicUrlInfo['landingId'] === $landingId);
		if($isOrderPublicUrlAvailable)
		{
			$params['isOrderPublicUrlAvailable'] = $isOrderPublicUrlAvailable;
		}
		return $this->sendEvent(static::EVENT_LANDING_PUBLIC, $params);
	}

	/**
	 * @return bool|string
	 */
	public function subscribeOnConnect()
	{
		return $this->subscribeOnEvent(static::EVENT_CONNECT);
	}

	/**
	 * @return bool
	 */
	public function sendConnectEvent()
	{
		return $this->sendEvent(static::EVENT_CONNECT, Driver::getInstance()->getManagerParams());
	}

	/**
	 * @return bool|string
	 */
	public function subscribeOnOrderAdd()
	{
		return $this->subscribeOnEvent(static::EVENT_ORDER_ADD);
	}

	/**
	 * @param int $orderId
	 * @param int $sessionId
	 * @return bool
	 */
	public function sendOrderAddEvent($orderId, $sessionId)
	{
		return $this->sendEvent(static::EVENT_ORDER_ADD, [
			'orderId' => $orderId,
			'sessionId' => $sessionId,
		]);
	}

	/**
	 * @return bool|string
	 */
	public function subscribeOnLandingUnPublication()
	{
		return $this->subscribeOnEvent(static::EVENT_LANDING_UNPUBLIC);
	}
	/**
	 * @param $landingId
	 * @return bool
	 */
	public function sendLandingUnPublicationEvent($landingId)
	{
		$params = ['landingId' => $landingId];
		$orderPublicUrlInfo = LandingManager::getInstance()->getOrderPublicUrlInfo();
		if($orderPublicUrlInfo && $orderPublicUrlInfo['landingId'] === $landingId)
		{
			$params['isOrderPublicUrlAvailable'] = false;
		}
		return $this->sendEvent(static::EVENT_LANDING_UNPUBLIC, $params);
	}

	/**
	 * @return string
	 */
	protected function getModuleName()
	{
		return 'pull';
	}

	/**
	 * @param string $eventName
	 * @param bool $immediate
	 * @return bool|string
	 */
	protected function subscribeOnEvent($eventName, $immediate = true)
	{
		if($this->isEnabled && is_string($eventName) && !empty($eventName))
		{
			$addResult = \CPullWatch::Add(Driver::getInstance()->getUserId(), $eventName, $immediate);
			if($addResult)
			{
				return $eventName;
			}
		}

		return false;
	}

	/**
	 * @param $eventName
	 * @param array $params
	 * @return bool
	 */
	protected function sendEvent($eventName, array $params = [])
	{
		$eventName = $this->subscribeOnEvent($eventName);
		if($eventName)
		{
			return \CPullWatch::AddToStack($eventName, [
				'module_id' => Driver::MODULE_ID,
				'command' => $eventName,
				'params' => $params,
			]);
		}

		return false;
	}
}