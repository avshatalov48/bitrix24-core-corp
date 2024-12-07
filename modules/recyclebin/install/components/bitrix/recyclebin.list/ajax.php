<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
if(!defined('STOP_STATISTICS'))
{
	define('STOP_STATISTICS', true);
}
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

if (isset($_POST['SITE_ID']) && (string)$_POST['SITE_ID'] != '')
{
	$siteId = mb_substr(trim((string)$_POST['SITE_ID']), 0, 2);
	if (preg_match('#^[a-zA-Z0-9]{2}$#', $siteId))
	{
		define('SITE_ID', $siteId);
	}
}

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Recyclebin\Internals\Models\RecyclebinTable;
use Bitrix\Recyclebin\Recyclebin;

class RecyclebinListAjaxController extends \Bitrix\Main\Engine\Controller
{
	public function restoreAction($recyclebinId, $isAll = false)
	{
		return $this->doAction('restore', $recyclebinId, $isAll);
	}

	public function removeAction($recyclebinId, $isAll = false)
	{
		CModule::IncludeModule('recyclebin');
		if(!\Bitrix\Recyclebin\Internals\User::isSuper() &&
		   !\Bitrix\Recyclebin\Internals\User::isAdmin()
		)
			return false;

		return $this->doAction('remove', $recyclebinId, $isAll);
	}

	private function doAction($action, $recyclebinId, $isAll = false)
	{
		$isAll = (bool)$isAll;
		try
		{
			Loader::includeModule('recyclebin');

			if (is_array($recyclebinId))
			{
				if(!$isAll || !\Bitrix\Recyclebin\Internals\User::isSuper())
				{
					$ids = $recyclebinId;
				}
				else
				{
					$list = RecyclebinTable::getList(['select'=>['ID']])->fetchAll();
					if($list)
					{
						$ids = array_column($list, 'ID');
					}
				}

				if($ids)
				{
					foreach ($ids as $id)
					{
						$resultItem = Recyclebin::$action((int)$id);
						$result[] = $resultItem;

						$this->checkResult($resultItem);
					}
				}
			}
			else
			{
				$result = Recyclebin::$action((int)$recyclebinId);

				$this->checkResult($result);
			}

			return $result;
		}
		catch (\Exception $e)
		{
			$this->errorCollection[] = new Error($e->getMessage(), $e->getCode());

			return null;
		}
	}

	private function checkResult(mixed $result): void
	{
		if ($result instanceof Result && !$result->isSuccess())
		{
			foreach ($result->getErrors() as $error)
			{
				$this->errorCollection[] = $error;
			}
		}
	}

}