<?php
namespace Bitrix\ImConnector\Connectors;

use \Bitrix\ImConnector\Library,
	\Bitrix\ImConnector\Connector,
	\Bitrix\ImConnector\Model\BotFrameworkTable;

class BotFramework
{
	public static function furtherMessageProcessing($message, $userSourceData, $connector, $connectorReal)
	{
		if($connectorReal == Library::ID_REAL_BOT_FRAMEWORK_CONNECTOR)
		{
			$dataBotFramework = array();

			if(!empty($message['recipient']))
			{
				$dataBotFramework['from'] = $message['recipient'];
				unset($message['recipient']);
			}

			if(!empty($message['service_url']))
			{
				$dataBotFramework['service_url'] = $message['service_url'];
				unset($message['service_url']);
			}

			//If the channel is kik
			if($connector == Library::ID_REAL_BOT_FRAMEWORK_KIK_CONNECTOR)
			{
				$dataBotFramework['recipient']['id'] = $userSourceData['id'];

				if(Library::isEmpty($userSourceData['name']))
					$dataBotFramework['recipient']['name'] = '';
				else
					$dataBotFramework['recipient']['name'] = $userSourceData['name'];
			}

			if(!empty($dataBotFramework))
			{
				$rawBotFramework = BotFrameworkTable::getList(
					array(
						'select'  => array(
							'ID',
							'DATA'
						),
						'filter'  => array(
							'VIRTUAL_CONNECTOR' => $connector,
							'ID_CHAT' => $message['chat']['id']
						)
					)
				);

				if ($rowBotFramework = $rawBotFramework->fetch())
				{
					if(array_diff($dataBotFramework, $rowBotFramework['DATA']))
					{
						$test = BotFrameworkTable::update($rowBotFramework['ID'], array(
							'ID_MESSAGE' => $message['message']['id'],
							'DATA' => $dataBotFramework
						));
					}
				}
				else
				{
					$test = BotFrameworkTable::add(array(
						'ID_CHAT' => $message['chat']['id'],
						'ID_MESSAGE' => $message['message']['id'],
						'VIRTUAL_CONNECTOR' => $connector,
						'DATA' => $dataBotFramework
					));
				}
			}
		}
	}

	public static function sendMessageProcessing($value, $connector)
	{
		if(!empty($value['chat']['id']))
		{
			$connectorReal = Connector::getConnectorRealId($connector);

			if($connectorReal == Library::ID_REAL_BOT_FRAMEWORK_CONNECTOR)
			{
				$rawBotFramework = BotFrameworkTable::getList(
					array(
						'select'  => array(
							'DATA'
						),
						'filter'  => array(
							'VIRTUAL_CONNECTOR' => $connector,
							'ID_CHAT' => $value['chat']['id']
						)
					)
				);

				if ($rowBotFramework = $rawBotFramework->fetch())
				{
					$value = array_merge($value, $rowBotFramework['DATA']);
				}
			}
		}

		return $value;
	}
}