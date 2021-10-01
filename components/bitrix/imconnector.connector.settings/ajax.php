<?if (
	!defined('B_PROLOG_INCLUDED') ||
	B_PROLOG_INCLUDED !== true
) die();

use \Bitrix\Main\Loader,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Engine\Controller;

use \Bitrix\Im;

use \Bitrix\Imopenlines\Limit,
	\Bitrix\ImOpenLines\Config,
	\Bitrix\ImOpenLines\Security,
	\Bitrix\ImOpenLines\Model\QueueTable,
	\Bitrix\ImOpenLines\Model\ConfigTable;

class ConnectorSettingsAjaxController extends Controller
{
	/**
	 * Saves user list for current open line by ajax request.
	 *
	 * @param $configId
	 * @param array $queue
	 * @return bool|string[]
	 */
	public function saveUsersAction($configId, array $queue)
	{
		$configId = (int)$configId;
		$result = false;

		if(
			Loader::includeModule('imopenlines')
		)
		{
			if(Config::canEditLine($configId))
			{
				$config['QUEUE'] = [];
				foreach ($queue as $entity)
				{
					$config['QUEUE'][] = [
						'ENTITY_TYPE' => $entity['type'],
						'ENTITY_ID' => $entity['id']
					];

					if($entity['type'] === 'user')
					{
						$users[] = $entity['id'];
					}
				}

				$configManager = new Config();
				$resultUpdate = $configManager->update($configId, $config);

				if($resultUpdate->isSuccess())
				{
					$result = true;
				}
			}
			else
			{
				$result = [
					'error' => 'Permission denied'
				];
			}
		}
		else
		{
			$result = [
				'error' => 'Failed to load module'
			];
		}


		return $result;
	}

	/**
	 * Activate line with checking possibility to do this.
	 *
	 * @param $lineId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function activateLineAction($lineId)
	{
		$result['result'] = false;

		if (Loader::includeModule('imopenlines'))
		{
			$canActivate = $this->checkCanActiveLine($lineId);

			if ($canActivate['result'])
			{
				$config = new Config();
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
	 * Return current line data.
	 *
	 * @param $lineId
	 * @return array|false
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getConfigItemAction($lineId)
	{
		$result = [];

		if (Loader::includeModule('imopenlines'))
		{
			$result = ConfigTable::getList(
				[
					'select' => [
						'ID',
						'NAME' => 'LINE_NAME',
						'IS_LINE_ACTIVE' => 'ACTIVE'
					],
					'filter' => [
						'=TEMPORARY' => 'N',
						'=ID' => $lineId
					]
				]
			)->fetch();
		}

		return $result;
	}

	/**
	 * @param $lineId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function checkCanActiveLine($lineId)
	{
		Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/bitrix/imconnector.connector.settings/ajax.php');

		$result['result'] = true;

		if (Loader::includeModule('imopenlines'))
		{
			$canModifyLine = Security\Helper::canCurrentUserModifyLine();

			if ($canModifyLine)
			{
				$linesLimit = Limit::getLinesLimit();

				if ($linesLimit > 0)
				{
					$activeLinesCount = ConfigTable::getList(
						[
							'select' => ['ID'],
							'filter' => ['ACTIVE' => 'Y', '!=ID' => $lineId, '=TEMPORARY' => 'N'],
							'count_total' => true
						]
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
}