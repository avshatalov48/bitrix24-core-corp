<?php

namespace Bitrix\Mobile;

class Action
{
	protected $actions;

	function __construct()
	{
		$this->actions = include(\Bitrix\Main\Application::getDocumentRoot() . "/bitrix/modules/mobile/ajax_action.php");
	}

	public function getAction($name)
	{
		if (array_key_exists($name, $this->actions))
		{
			return $this->actions[$name];
		}

		return false;
	}

	/**
	 * @param string $name
	 * @param array $params
	 */
	public function executeAction($name, $params = [])
	{

		global $USER;

		$actionDesc = $this->getAction($name);

		if ($actionDesc)
		{

			$isSessidValid = true;
			if ($actionDesc["needBitrixSessid"] == true || (array_key_exists("sessid", $_REQUEST) && strlen($_REQUEST["sessid"]) > 0))
			{
				$isSessidValid = check_bitrix_sessid();
			}

			if (!isset($actionDesc["fireInitMobileEvent"]) || $actionDesc["fireInitMobileEvent"] != true)
			{
				if (!defined("MOBILE_INIT_EVENT_SKIP"))
				{
					define("MOBILE_INIT_EVENT_SKIP", true);
				}
			}

			if ($actionDesc["no_check_auth"] !== true && (!$USER->IsAuthorized() || !$isSessidValid))
			{
				Auth::setNotAuthorizedHeaders();
				echo json_encode(Auth::getNotAuthorizedResponse());
			}
			elseif ($actionDesc["file"])
			{
				header("BX-Mobile-Action: " . $name);
				if ($actionDesc["json"] === true)
				{
					header("Content-Type: application/x-javascript");
					$data = include($actionDesc["file"]);
					if ($data)
					{
						if ($actionDesc["removeNulls"])
						{
							echo json_encode(self::removeNulls($data));
						}
						else
						{
							echo json_encode($data);
						}
					}

				}
				else
				{
					include($actionDesc["file"]);
				}
			}
		}
		else
		{
			if (!defined("MOBILE_INIT_EVENT_SKIP"))
			{
				define("MOBILE_INIT_EVENT_SKIP", true);
			}

			header("Content-Type: application/x-javascript");
			echo json_encode(["error" => "unknown action for data request"]);
		}
	}

	/**
	 * @param array $array
	 * @param null $replace
	 * @return array
	 */
	public static function removeNulls($array = [], $replace = null)
	{
		foreach ($array as $key => $value)
		{
			if (is_array($value))
			{
				$array[$key] = self::removeNulls($array[$key]);
			}

			if ($array[$key] === null)
			{
				if ($replace != null)
				{
					$array[$key] = $replace;
				}
				else
				{
					unset($array[$key]);
				}
			}
		}

		return $array;
	}

}
