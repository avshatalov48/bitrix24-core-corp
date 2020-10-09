<?
namespace Bitrix\Intranet\Integration;

use Bitrix\Main;
use Bitrix\Main\Config;
use Bitrix\Main\Localization\Loc;

final class Security
{
	public static function onOtpRequired(Main\Event $event)
	{
		if(Config\Option::get("intranet", "send_otp_push", "Y") <> "N")
		{
			if(Main\Loader::includeModule("im"))
			{
				$params = $event->getParameters();

				//todo: may be send push without the code for HOTP
				if($params["code"] !== null)
				{
					$server = Main\Context::getCurrent()->getServer();

					$pushMessage = Loc::getMessage("intranet_otp_push_code", ["#CODE#" => $params["code"]]);
					$message = Loc::getMessage("intranet_push_otp_notification1", [
						"#CODE#" => $params["code"],
						"#IP#" => $server->getRemoteAddr(),
						"#USER_AGENT#" => $server->get("HTTP_USER_AGENT"),
					]);

					\CIMNotify::Add([
						"TO_USER_ID" => $params["userId"],
						"FROM_USER_ID" => 0,
						"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
						"NOTIFY_MODULE" => "intranet",
						"NOTIFY_EVENT" => "security_otp",
						"NOTIFY_MESSAGE" => $message,
						"PUSH_MESSAGE" => $pushMessage,
					 	"PUSH_IMPORTANT" => "N",
					]);
				}
			}
		}
	}
}
