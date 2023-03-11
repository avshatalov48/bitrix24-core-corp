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
			$needBitrixSessid = $actionDesc["needBitrixSessid"] ?? false;
			if ($needBitrixSessid == true || (array_key_exists("sessid", $_REQUEST) && $_REQUEST["sessid"] <> ''))
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

			$noCheckAuth = $actionDesc["no_check_auth"] ?? null;
			$file = $actionDesc["file"] ?? null;

			if ($noCheckAuth !== true && (!$USER->IsAuthorized() || !$isSessidValid))
			{
				Auth::setNotAuthorizedHeaders();
				echo json_encode(Auth::getNotAuthorizedResponse());
			}
			elseif ($file)
			{
				header("BX-Mobile-Action: " . $name);
				$json = $actionDesc["json"] ?? false;
				if ($json === true)
				{
					header("Content-Type: application/x-javascript");
					$data = include($file);
					if ($data)
					{
						$removeNulls = $actionDesc["removeNulls"] ?? false;
						if ($removeNulls)
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
					include($file);
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
