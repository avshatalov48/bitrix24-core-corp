<?php
namespace Bitrix\ImOpenLines;

use \Bitrix\Imopenlines\Model\LogTable;

/**
 * Class Debug
 * @package Bitrix\ImOpenLines
 */
class Debug
{
	/**
	 * @param Session $session
	 * @param $type
	 * @param $data
	 */
	public static function addSession(Session $session, $type, $data = null)
	{
		if(defined('IMOPENLINES_FULL_DEBUG'))
		{
			if($session instanceof Session)
			{
				try
				{
					LogTable::add(array(
						'LINE_ID' => $session->getData('CONFIG_ID'),
						'CONNECTOR_ID' => $session->getData('SOURCE'),
						'SESSION_ID' => $session->getData('ID'),
						'TYPE' => 'session: ' . $type,
						'DATA' => $data,
						'TRACE' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6)
					));
				}
				catch (\Bitrix\Main\SystemException $e)
				{
				}
				catch (\Exception $e)
				{
				}
			}
		}
	}

	/**
	 * @param $type
	 * @param null $data
	 */
	public static function addAgent($type, $data = null)
	{
		if(defined('IMOPENLINES_FULL_DEBUG'))
		{
			try
			{
				LogTable::add(array(
					'LINE_ID' => 0,
					'CONNECTOR_ID' => 0,
					'SESSION_ID' => 0,
					'TYPE' => 'agent: ' . $type,
					'DATA' => $data,
					'TRACE' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6)
				));
			}
			catch (\Bitrix\Main\SystemException $e)
			{
			}
			catch (\Exception $e)
			{
			}
		}
	}

	/**
	 * @param $lineId
	 * @param $sessionId
	 * @param $type
	 * @param null $data
	 */
	public static function addQueue($lineId, $sessionId, $type, $data = null)
	{
		if(defined('IMOPENLINES_FULL_DEBUG'))
		{
			try
			{
				LogTable::add(array(
					'LINE_ID' => $lineId,
					'SESSION_ID' => $sessionId,
					'TYPE' => 'queue: ' . $type,
					'DATA' => $data,
					'TRACE' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6)
				));
			}
			catch (\Bitrix\Main\SystemException $e)
			{
			}
			catch (\Exception $e)
			{
			}
		}
	}

	/**
	 * @param $lineId
	 * @param $sessionId
	 * @param $type
	 * @param null $data
	 */
	public static function addQueueEvent($type, $lineId = 0, $sessionId = 0, $data = null)
	{
		if(defined('IMOPENLINES_FULL_DEBUG'))
		{
			try
			{
				LogTable::add(array(
					'LINE_ID' => $lineId,
					'SESSION_ID' => $sessionId,
					'TYPE' => 'queue event: ' . $type,
					'DATA' => $data,
					'TRACE' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6)
				));
			}
			catch (\Bitrix\Main\SystemException $e)
			{
			}
			catch (\Exception $e)
			{
			}
		}
	}
}
