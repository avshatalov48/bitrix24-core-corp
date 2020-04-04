<?php
namespace Bitrix\Pull;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Config
{
	public static function get($params = array())
	{
		if (!\CPullOptions::GetQueueServerStatus())
			return false;

		$userId = intval($params['USER_ID']);
		if ($userId <= 0)
		{
			global $USER;
			$userId = $USER->GetID();
		}
		if ($userId <= 0)
		{
			return false;
		}

		$cache = $params['CACHE'] !== false;
		$reopen = $params['REOPEN'] !== false;

		$privateChannelType = isset($params['CUSTOM_TYPE'])? $params['CUSTOM_TYPE']: \CPullChannel::TYPE_PRIVATE;
		$sharedChannelType = isset($params['CUSTOM_TYPE'])? $params['CUSTOM_TYPE']: \CPullChannel::TYPE_SHARED;

		$privateChannel = \CPullChannel::Get($userId, $cache, $reopen, $privateChannelType);
		$sharedChannel = \CPullChannel::GetShared($cache, $reopen, $sharedChannelType);

		$domain = defined('BX24_HOST_NAME')? BX24_HOST_NAME: $_SERVER['SERVER_NAME'];

		$config['SERVER'] = Array(
			'VERSION' => \CPullOptions::GetQueueServerVersion(),
			'SERVER_ENABLED' => \CPullOptions::GetQueueServerStatus(),
			'LONG_POLLING' => str_replace('#DOMAIN#', $domain, \CPullOptions::GetListenUrl()),
			'LONG_POOLING_SECURE' => str_replace('#DOMAIN#', $domain, \CPullOptions::GetListenSecureUrl()),
			'WEBSOCKET_ENABLED' => \CPullOptions::GetWebSocket(),
			'WEBSOCKET' => str_replace('#DOMAIN#', $domain, \CPullOptions::GetWebSocketUrl()),
			'WEBSOCKET_SECURE' => str_replace('#DOMAIN#', $domain, \CPullOptions::GetWebSocketSecureUrl()),
			'PUBLISH_ENABLED' => \CPullOptions::GetPublishWebEnabled(),
			'PUBLISH' => str_replace('#DOMAIN#', $domain, \CPullOptions::GetPublishWebUrl()),
			'PUBLISH_SECURE' => str_replace('#DOMAIN#', $domain, \CPullOptions::GetPublishWebSecureUrl()),
			'CONFIG_TIMESTAMP' => \CPullOptions::GetConfigTimestamp(),
		);
		$config['API'] = Array(
			'REVISION_WEB' => PULL_REVISION_WEB,
			'REVISION_MOBILE' => PULL_REVISION_MOBILE,
		);

		$config['CHANNELS'] = Array();
		if ($sharedChannel)
		{
			$config['CHANNELS']['SHARED'] = Array(
				'ID' => \CPullChannel::SignChannel($sharedChannel["CHANNEL_ID"]),
				'START' => $sharedChannel['CHANNEL_DT'],
				'END' => $sharedChannel['CHANNEL_DT']+\CPullChannel::CHANNEL_TTL,
			);
		}
		if ($privateChannel)
		{
			if (\CPullOptions::GetQueueServerVersion() > 3)
			{
				$privateId = $privateChannel["CHANNEL_PUBLIC_ID"]? $privateChannel["CHANNEL_ID"].":".$privateChannel["CHANNEL_PUBLIC_ID"]: $privateChannel["CHANNEL_ID"];
				$privateId = \CPullChannel::SignChannel($privateId);

				$publicId = \CPullChannel::SignPublicChannel($privateChannel["CHANNEL_PUBLIC_ID"]);
			}
			else
			{
				$privateId = \CPullChannel::SignChannel($privateChannel["CHANNEL_ID"]);
				$publicId = '';
			}

			$config['CHANNELS']['PRIVATE'] = Array(
				'ID' => $privateId,
				'PUBLIC_ID' => $publicId,
				'START' => $privateChannel['CHANNEL_DT'],
				'END' => $privateChannel['CHANNEL_DT']+\CPullChannel::CHANNEL_TTL,
			);
		}

		if ($params['JSON'])
		{
			$result['server'] = array_change_key_case($config['SERVER'], CASE_LOWER);
			$result['api'] = array_change_key_case($config['API'], CASE_LOWER);

			foreach ($config['CHANNELS'] as $type => $config)
			{
				$type = strtolower($type);
				$result['channels'][$type] = array_change_key_case($config, CASE_LOWER);
				$result['channels'][$type]['type'] = $type;
				$result['channels'][$type]['start'] = date('c', $config['START']);
				$result['channels'][$type]['end'] = date('c', $config['END']);
			}

			$config = $result;
		}

		return $config;

	}
}
