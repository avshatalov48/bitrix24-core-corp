<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Loader,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\ImOpenLines\Model\QueueTable;

class ConnectorSettingsAjaxController extends \Bitrix\Main\Engine\Controller
{
	/**
	 * Saves user list for current open line by ajax request
	 *
	 * @param int $lineId
	 * @param array $queue
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function saveUsersAction($lineId, array $queue)
	{
		$this->includeModules();

		$lineId = intval($lineId);
		$config['QUEUE'] = array();
		$arAccessCodes = array();

		foreach ($queue as $userCode)
		{
			$userId = substr($userCode, 1);
			$userId = intval($userId);

			if (\Bitrix\Im\User::getInstance($userId)->isExtranet())
				continue;

			$config['QUEUE'][] = $userId;

			$result = QueueTable::getList([
				'filter' => [
					'=USER_ID' => $userId,
					'=CONFIG_ID' => $lineId
				]
			]);
			while ($row = $result->fetch())
			{
				$config['QUEUE_USERS_FIELDS'][$userId] = $row;
			}

			$arAccessCodes[] = $userCode;
		}

		\Bitrix\Main\FinderDestTable::merge(
			array(
				"CONTEXT" => "IMCONNECTOR",
				"CODE" => \Bitrix\Main\FinderDestTable::convertRights($arAccessCodes, array('U' . $GLOBALS["USER"]->GetId()))
			)
		);

		$configManager = new \Bitrix\ImOpenLines\Config();

		return $configManager->update($lineId, $config);
	}

	/**
	 * Save and get formatted data about users in line
	 *
	 * @param $lineId
	 * @param array $queue
	 *
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getSaveUsersAction($lineId, array $queue)
	{
		$this->includeModules();
		$success = $this->saveUsersAction($lineId, $queue);
		$configManager = new \Bitrix\ImOpenLines\Config();
		$config = $configManager->get($lineId);
		$return['success'] = $success;
		$users = CSocNetLogDestination::GetUsers(array('id' => $config['QUEUE']));

		foreach ($config['QUEUE'] as $queue)
		{
			$key = 'U' . $queue;
			$return['users'][$key] = $users[$key];
		}

		return $return;
	}

	/**
	 * Activate line with checking possibility to do this
	 *
	 * @param $lineId
	 *
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function activateLineAction($lineId)
	{
		$result['result'] = false;

		if (Loader::includeModule('imopenlines'))
		{
			$canActivate = $this->checkCanActiveLine($lineId);

			if ($canActivate['result'])
			{
				$config = new \Bitrix\ImOpenLines\Config();
				$result['result'] = $config->setActive($lineId);
			}
			else
			{
				$result = $canActivate;
			}
		}

		return $result;
	}

	/**
	 * Return current line data
	 *
	 * @param $lineId
	 *
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getConfigItemAction($lineId)
	{
		$result = [];

		if (Loader::includeModule('imopenlines'))
		{
			$result = \Bitrix\ImOpenLines\Model\ConfigTable::getList(
				array(
					'select' => array(
						'ID',
						'NAME' => 'LINE_NAME',
						'IS_LINE_ACTIVE' => 'ACTIVE'
					),
					'filter' => array(
						'=TEMPORARY' => 'N',
						'=ID' => $lineId
					)
				)
			)->fetch();
		}

		return $result;
	}

	/**
	 * @param $lineId
	 *
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function checkCanActiveLine($lineId)
	{
		Loc::loadMessages($_SERVER["DOCUMENT_ROOT"] . '/bitrix/components/bitrix/imconnector.connector.settings/ajax.php');

		$result['result'] = true;

		if (Loader::includeModule('imopenlines'))
		{
			$canModifyLine = \Bitrix\ImOpenLines\Security\Helper::canCurrentUserModifyLine();

			if ($canModifyLine)
			{
				$linesLimit = \Bitrix\Imopenlines\Limit::getLinesLimit();

				if ($linesLimit > 0)
				{
					$activeLinesCount = \Bitrix\ImOpenLines\Model\ConfigTable::getList(
						array(
							'select' => array('ID'),
							'filter' => array('ACTIVE' => 'Y', '!=ID' => $lineId, '=TEMPORARY' => 'N'),
							'count_total' => true
						)
					)->getCount();

					if ($activeLinesCount >= $linesLimit)
					{
						$result = [
							'result' => false,
							'error' => Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_LINE_ACTIVATION_ERROR_LIMIT')
						];
					}
				}
			}
			else
			{
				$result = [
					'result' => false,
					'error' => Loc::getMessage('IMCONNECTOR_COMPONENT_CONNECTOR_LINE_ACTIVATION_ERROR_PERMISSION')
				];
			}
		}

		return $result;
	}

	/**
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function includeModules()
	{
		$moduleList = array('im', 'imopenlines', 'socialnetwork');

		foreach ($moduleList as $module)
		{
			Loader::includeModule($module);
		}
	}
}