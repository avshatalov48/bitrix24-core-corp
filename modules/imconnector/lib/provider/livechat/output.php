<?php
namespace Bitrix\ImConnector\Provider\LiveChat;

use Bitrix\Main\Loader;

use Bitrix\ImConnector\Error;
use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Provider\Base;

use Bitrix\ImOpenLines\LiveChatManager;

class Output extends Base\Output
{
	/**
	 * Output constructor.
	 * @param string $connector ID connector.
	 * @param string|bool $line ID open line.
	 */
	public function __construct(string $connector, $line = false)
	{
		parent::__construct($connector, $line);

		if (!Loader::includeModule('im'))
		{
			$this->result->addError(new Error(
				'Unable to load the im module',
				'ERROR_LOAD_IM',
				__METHOD__,
				$connector
			));
		}
		if (!Loader::includeModule(Library::MODULE_ID_OPEN_LINES))
		{
			$this->result->addError(new Error(
				'Unable to load the Open Lines module',
				'ERROR_LOAD_IMOPENLINES',
				__METHOD__,
				$connector
			));
		}
	}

	/**
	 * @param array $data
	 * @return Result
	 */
	protected function sendStatusWriting(array $data): Result
	{
		$result = clone $this->result;

		if($result->isSuccess())
		{
			foreach ($data as $message)
			{
				\CIMMessenger::StartWriting('chat' . $message['connector']['chat_id'], $message['user']['ID'], $message['user']['NAME'], true);
			}
		}

		return $result;
	}

	/**
	 * The removal of the open line of this website from the remote server connectors.
	 *
	 * @param $lineId
	 * @return Result
	 */
	protected function deleteLine($lineId): Result
	{
		$result = clone $this->result;

		if($result->isSuccess())
		{
			$livechatManager = new LiveChatManager($lineId);
			$livechatManager->delete();
		}

		return $result;
	}

	/**
	 * Receive information about all the connected connectors.
	 *
	 * @param $lineId
	 * @return Result
	 */
	protected function infoConnectorsLine($lineId): Result
	{
		$result = clone $this->result;
		$resultLiveChat = [];

		if($result->isSuccess())
		{
			$managerLiveChat = new LiveChatManager($lineId);
			$infoLiveChat = $managerLiveChat->getPublicLink();

			if(!empty($infoLiveChat['ID']))
			{
				$resultLiveChat['id'] = $infoLiveChat['ID'];

				if(!Library::isEmpty($infoLiveChat['LINE_NAME']))
				{
					$resultLiveChat['name'] = $infoLiveChat['LINE_NAME'];
				}

				if(
					!empty($infoLiveChat['PICTURE']) &&
					is_array($infoLiveChat['PICTURE'])
				)
				{
					$resultLiveChat['picture'] = $infoLiveChat['PICTURE'];
				}

				if(!empty($infoLiveChat['URL']))
				{
					$resultLiveChat['url'] = $infoLiveChat['URL'];
				}

				if(!empty($infoLiveChat['URL_IM']))
				{
					$resultLiveChat['url_im'] = $infoLiveChat['URL_IM'];
				}
			}
		}
		$result->setData([Library::ID_LIVE_CHAT_CONNECTOR => $resultLiveChat]);

		return $result;
	}
}