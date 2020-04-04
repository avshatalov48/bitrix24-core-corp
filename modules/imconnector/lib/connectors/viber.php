<?php
namespace Bitrix\ImConnector\Connectors;

use \Bitrix\Im\User,
	\Bitrix\Main\Loader,
	\Bitrix\Main\Web\Uri,
	\Bitrix\Main\Config\Option;
use \Bitrix\ImConnector\Library;
use \Bitrix\ImOpenLines\Config,
	\Bitrix\ImOpenLines\Queue;

/**
 * Class Viber
 * @package Bitrix\ImConnector\Connectors
 */
class Viber
{
	/**
	 * @param $value
	 * @param $connector
	 * @param int $lineId
	 *
	 * @return mixed
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function sendMessageProcessing($value, $connector, $lineId = 0)
	{
		if($connector == Library::ID_VIBER_CONNECTOR && !empty($value['chat']['id']) && !empty($value['message']['user_id']) && Loader::includeModule('im'))
		{
			$lineId = intval($lineId);
			//$user = User::getInstance($value['message']['user_id'])->getFields();
			$user = User::getInstance($value['message']['user_id']);

			if ($lineId > 0) //New param value - can be issues on partners portals without this condition
			{
				$operatorDataType = Config::operatorDataConfig($lineId);

				if ($operatorDataType == Config::OPERATOR_DATA_QUEUE)
				{
					$userData = Queue::getQueueOperatorData($value['message']['user_id'], $lineId);
					$value['user']['name'] = !is_null($userData['USER_NAME']) ? $userData['USER_NAME'] : $user->getFullName(false);
					$value['user']['picture'] = array('url' => $userData['USER_AVATAR']);
				}
				elseif ($operatorDataType == Config::OPERATOR_DATA_HIDE)
				{
					$value['user']['name'] = '';
					$value['user']['picture'] = '';
				}
				else
				{
					if($user->getAvatarId() && $user->getAvatar())
					{
						if(!Library::isEmpty($user->getFullName(false)))
							$value['user']['name'] = $user->getFullName(false);

						$uri = new Uri($user->getAvatar());
						if($uri->getHost())
							$value['user']['picture'] = array('url' => $user->getAvatar());
						else
							$value['user']['picture'] = array('url' => Option::get(Library::MODULE_ID, "uri_client") . $user->getAvatar());
					}
				}
			}
			else
			{
				if($user->getAvatarId() && $user->getAvatar())
				{
					if(!Library::isEmpty($user->getFullName(false)))
						$value['user']['name'] = $user->getFullName(false);

					$uri = new Uri($user->getAvatar());
					if($uri->getHost())
						$value['user']['picture'] = array('url' => $user->getAvatar());
					else
						$value['user']['picture'] = array('url' => Option::get(Library::MODULE_ID, "uri_client") . $user->getAvatar());
				}
			}
		}

		return $value;
	}
}