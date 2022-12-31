<?php
namespace Bitrix\Mobile\Component\LogList;

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Component\LogList\Util;

class Counter
{
	protected $component;
	protected $request;

	public function __construct($params)
	{
		if (!empty($params['component']))
		{
			$this->component = $params['component'];
		}

		if (!empty($params['request']))
		{
			$this->request = $params['request'];
		}
		else
		{
			$this->request = Util::getRequest();;
		}
	}

	public function getRequest()
	{
		return $this->request;
	}

	public function getComponent()
	{
		return $this->component;
	}

	public function processCounterTypeData(&$result)
	{
		$params = $this->getComponent()->arParams;

		if ((int)$params['GROUP_ID'] > 0)
		{
			$result['COUNTER_TYPE'] = 'SG'.(int)$params['GROUP_ID'];
		}
		elseif(
			$params['IS_CRM'] === 'Y'
			&& $params['SET_LOG_COUNTER'] !== 'N'
		)
		{
			$result['COUNTER_TYPE'] = 'CRM_**';
		}
		elseif ($params['FIND'] <> '')
		{
		}
		else
		{
			$result['COUNTER_TYPE'] = \CUserCounter::LIVEFEED_CODE;
		}
	}

	public function clearLogCounter(&$result)
	{
		if ($result['currentUserId'] <= 0)
		{
			return;
		}

		$params = $this->getComponent()->arParams;

		$emptyCounter = false;

		if (
			$params['LOG_ID'] <= 0
			&& $params['NEW_LOG_ID'] <= 0
		)
		{
			$counters = \CUserCounter::getValues($result['currentUserId'], SITE_ID);
			if (isset($counters[$result["COUNTER_TYPE"]]))
			{
				$result['LOG_COUNTER'] = (int)$counters[$result['COUNTER_TYPE']];
			}
			else
			{
				$emptyCounter = true;
				$result['LOG_COUNTER'] = 0;
			}
		}

		$result['COUNTER_TO_CLEAR'] = false;

		if ($params['SET_LOG_COUNTER'] === 'Y')
		{
			if (
				$result['LOG_COUNTER'] > 0
				|| $emptyCounter
			)
			{
				\CUserCounter::clear(
					$result['currentUserId'],
					$result['COUNTER_TYPE'],
					[ SITE_ID, \CUserCounter::ALL_SITES ],
					false, // sendPull
					true // bMultiple
				);

				$result['COUNTER_TO_CLEAR'] = $result['COUNTER_TYPE'];

				$res = getModuleEvents('socialnetwork', 'OnSonetLogCounterClear');
				while ($event = $res->fetch())
				{
					executeModuleEventEx($event, [ $result['COUNTER_TYPE'], (int)$result['LAST_LOG_TS']]);
				}
			}
			elseif ($result['COUNTER_TYPE'] === \CUserCounter::LIVEFEED_CODE)
			{
				$pool = \Bitrix\Main\Application::getInstance()->getConnectionPool();
				$pool->useMasterOnly(true);

				\CUserCounter::clear(
					$result['currentUserId'],
					$result['COUNTER_TYPE'],
					[ SITE_ID, \CUserCounter::ALL_SITES ],
					false, // sendPull
					false, // bMultiple,
					false // cleanCache
				);

				$pool->useMasterOnly(false);

				$result['COUNTER_TO_CLEAR'] = $result['COUNTER_TYPE'];
			}

			if (
				$result['COUNTER_TYPE'] === \CUserCounter::LIVEFEED_CODE
				&& Loader::includeModule('pull')
			)
			{
				\Bitrix\Pull\Event::add($result['currentUserId'], [
					'module_id' => 'main',
					'command' => 'user_counter',
					'expiry' => 3600,
					'params' => [
						SITE_ID => [
							\CUserCounter::LIVEFEED_CODE => 0
						],
					],
				]);

				$result['COUNTER_TO_CLEAR'] = $result['COUNTER_TYPE'];
			}
		}

		if ($result['COUNTER_TO_CLEAR'])
		{
			$result['COUNTER_SERVER_TIME'] = date('c');
			$result['COUNTER_SERVER_TIME_UNIX'] = microtime(true);
		}
	}
}
