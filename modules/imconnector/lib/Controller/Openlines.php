<?php

namespace Bitrix\ImConnector\Controller;

use Bitrix\Im\User;
use Bitrix\ImConnector\Connector;
use Bitrix\ImConnector\Controller\Filter\LineAccess;
use Bitrix\ImConnector\Controller\Filter\Connector as ConnectorFilter;
use Bitrix\ImConnector\Controller\Filter\LinesAccess;
use Bitrix\ImConnector\Controller\Filter\LineViewAccess;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Output;
use Bitrix\ImConnector\InfoConnectors;
use Bitrix\ImOpenlines\Security\Permissions;
use Bitrix\ImConnector\Status;
use Bitrix\ImOpenLines\Config;
use Bitrix\ImOpenLines\Queue;
use Bitrix\Intranet\ActionFilter\IntranetUser;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\UI\Barcode\Barcode;

class Openlines extends Controller
{
	private const QR_WIDTH = 300;
	private const QR_HEIGHT = 300;
	private const ENTITY_TYPE_USER = 'user';
	public const CONNECTORS = [
		Library::ID_TELEGRAMBOT_CONNECTOR => [
			'usernameField' => 'username',
			'titleField' => 'name',
			'urlPrefix' => 'https://t.me/'
		]
	];

	public const ERROR_ACCESS_DENIED = [
		'code' => 'ACCESS_DENIED',
		'message' => 'Access denied'
	];
	public const ERROR_CONNECTOR_NOT_FOUND = [
		'code' => 'CONNECTOR_NOT_FOUND',
		'message' => 'Connector not found'
	];
	public const ERROR_CONNECTOR_ALREADY_EXISTS = [
		'code' => 'CONNECTOR_ALREADY_EXISTS',
		'message' => 'Connector already exists'
	];

	public function configureActions()
	{
		return [
			'create' => [
				'+prefilters' => [
					new IntranetUser(),
					new LinesAccess(),
					new LineAccess(),
					new ConnectorFilter(),
				],
			],
			'get' => [
				'+prefilters' => [
					new IntranetUser(),
					new LineViewAccess(),
					new ConnectorFilter(),
				],
			],
			'delete' => [
				'+prefilters' => [
					new IntranetUser(),
					new LinesAccess(),
					new LineAccess(),
				],
			],
			'list' => [
				'+prefilters' => [
					new IntranetUser(),
					new ConnectorFilter(),
				],
			],
			'setUsers' => [
				'+prefilters' => [
					new IntranetUser(),
					new LinesAccess(),
					new LineAccess(),
				],
			],
		];
	}

	public function processBeforeAction(Action $action): bool
	{
		if (
			!Loader::includeModule('imopenlines')
			|| !Loader::includeModule('intranet')
		)
		{
			return false;
		}

		return parent::processBeforeAction($action);
	}

	/**
	 * @restMethod imconnector.Openlines.list
	 */
	public function listAction(string $connectorId, bool $withConnector = false, bool $withQr = false): array
	{
		$statuses = Status::getInstanceAllLine($connectorId);

		$lines = [];
		foreach ($statuses as $lineId => $status)
		{
			if (
				!$status->getError()
				&& $status->getRegister()
				&& $status->getActive()
			)
			{
				$lines[$lineId] = [
					'lineId' => $lineId
				];
			}
		}

		$allLines = Config::getAllLinesSettings(['LINE_ID' => 'ID', 'LINE_NAME']);
		foreach ($allLines as $key => $line)
		{
			unset($allLines[$key]['ID']);
			if (!$withConnector)
			{
				if (in_array((int)$line['LINE_ID'], array_keys($lines), true))
				{
					unset($allLines[$key]);
				}
			}
			else
			{
				if (!in_array((int)$line['LINE_ID'], array_keys($lines), true))
				{
					unset($allLines[$key]);
				}
			}
		}

		$lines = $this->convertKeysToCamelCase($allLines);

		foreach ($lines as $lineId => $line)
		{
			if (!Config::canViewLine($lineId))
			{
				unset($lines[$lineId]);
			}
			$lines[$lineId]['canEditLine'] = Config::canEditLine($lineId);
			$lines[$lineId]['canEditConnector'] = Config::canEditConnector($lineId);
		}

		foreach ($lines as $key => $line)
		{
			$linesQueue = Queue::getList([
				'select' => [
					'USER_ID'
				],
				'filter' => [
					'=CONFIG_ID' => $line['lineId'],
					'=USER.ACTIVE' => 'Y'
				],
			]);

			if (!isset($lines[$key]['userIds']))
			{
				$lines[$key]['userIds'] = array_map(function ($item) {
					return (int)$item['USER_ID'];
				}, $linesQueue->fetchAll());
			}
		}

		return array_values($lines);
	}

	/**
	 * @restMethod imconnector.Openlines.create
	 */
	public function createAction(
		string $connectorId,
		string $botToken,
		?int $lineId = null,
		array $userIds = [],
		bool $withQr = false
	): ?array
	{
		$config = new Config();
		if (!$lineId)
		{
			$lineId = $config->create([
				'QUEUE' => array_merge([$this->getCurrentUser()->getId()], $userIds),
			]);
		}
		else
		{
			$info = Connector::infoConnectorsLine($lineId);
			if ($info && isset($info[$connectorId]))
			{
				$this->addError(new Error(
					Openlines::ERROR_CONNECTOR_ALREADY_EXISTS['message'],
					Openlines::ERROR_CONNECTOR_ALREADY_EXISTS['code']
				));
				return null;
			}
		}

		$connectorOutput = new Output($connectorId, $lineId);
		$status = Status::getInstance($connectorId, $lineId);

		$saveResult = $connectorOutput->saveSettings([
			'api_token' => $botToken,
		]);

		if (!$saveResult->isSuccess())
		{
			$this->addErrors($saveResult->getErrors());
			return null;
		}

		$testConnect = $connectorOutput->testConnect();

		if (!$testConnect->isSuccess())
		{
			$this->addErrors($testConnect->getErrors());
			return null;
		}

		$register = $connectorOutput->register();

		if (!$register->isSuccess())
		{
			$this->addErrors($register->getErrors());
			return null;
		}
		Cache::createInstance()->cleanDir(Library::CACHE_DIR_COMPONENT);

		$status
			->setActive(true)
			->setConnection(true)
			->setRegister(true)
			->setError(false)
		;


		if (!is_array($status->getData()))
		{
			$status->setData([]);
		}
		$status->save();

		Status::addBackgroundTasks($connectorId, $lineId);

		Connector::cleanCacheConnector($lineId, Connector::getCacheIdConnector($lineId, $connectorId));

		$connectInfo = InfoConnectors::addInfoConnectors($lineId);
		$connectInfoData = $connectInfo->getResult();

		$botName = $botUrl = '';
		if (!empty($connectInfoData[$connectorId]))
		{
			$botCode = $connectInfoData[$connectorId][self::CONNECTORS[$connectorId]['usernameField']];
			$botName = $connectInfoData[$connectorId][self::CONNECTORS[$connectorId]['titleField']];
			$botUrl = self::CONNECTORS[$connectorId]['urlPrefix'] . $botCode;
		}
		else
		{
			$connectorOutput = new Output($connectorId, $lineId);
			$infoConnect = $connectorOutput->infoConnect();
			if ($infoConnect->isSuccess())
			{
				$connectInfoData = $infoConnect->getData();
				$botName = $connectInfoData[self::CONNECTORS[$connectorId]['titleField']];
				$botUrl = $connectInfoData['url'];
			}
		}
		if (empty($botUrl))
		{
			$messengerUrl = Connector::getImMessengerUrl($lineId, $connectorId);
			if ($messengerUrl)
			{
				$botUrl = $messengerUrl['web'];
			}
		}

		$line = $config->get($lineId);
		$result = [
			'lineId' => $lineId,
			'lineName' => $line['LINE_NAME'],
			'userIds' => array_map('intval', $line['QUEUE'] ?? []),
			'botName' => $botName,
			'url' => $botUrl,
		];

		if (
			Loader::includeModule('ui')
			&& $withQr
			&& $result['url']
		)
		{
			$urlQR = (new Barcode())
				->option('w', self::QR_WIDTH)
				->option('h', self::QR_HEIGHT)
				->render($result['url']);

			$result['qr'] = base64_encode($urlQR);
		}

		return $result;
	}

	/**
	 * @restMethod imconnector.Openlines.get
	 */
	public function getAction(string $connectorId, int $lineId, bool $withQr = false): ?array
	{
		$info = Connector::infoConnectorsLine($lineId);

		if ($info)
		{
			$botName = $info[$connectorId][self::CONNECTORS[$connectorId]['titleField']];
			$botUrl = Connector::getImMessengerUrl($lineId, $connectorId)['web'];
		}
		else
		{
			$connectorOutput = new Output($connectorId, $lineId);
			$infoConnect = $connectorOutput->infoConnect();
			if ($infoConnect->isSuccess())
			{
				$connectInfoData = $infoConnect->getData();
				$botName = $connectInfoData[self::CONNECTORS[$connectorId]['titleField']];
				$botUrl = $connectInfoData['url'];
			}
			else
			{
				$this->addError(new Error(
					self::ERROR_CONNECTOR_NOT_FOUND['message'],
					self::ERROR_CONNECTOR_NOT_FOUND['code']
				));
				return null;
			}

			if (empty($botUrl))
			{
				$messengerUrl = Connector::getImMessengerUrl($lineId, $connectorId);
				if ($messengerUrl)
				{
					$botUrl = $messengerUrl['web'];
				}
			}
		}

		$line = (new Config())->get($lineId);
		$result = [
			'lineId' => $lineId,
			'lineName' => $line['LINE_NAME'],
			'userIds' => array_map('intval', $line['QUEUE'] ?? []),
			'botName' => $botName,
			'url' => $botUrl,
			'canEditLine' => Config::canEditLine($lineId),
			'canEditConnector' => Config::canEditConnector($lineId)
		];

		if (
			Loader::includeModule('ui')
			&& $withQr
			&& isset($result['url'])
		)
		{
			$urlQR = (new Barcode())
				->option('w', self::QR_WIDTH)
				->option('h', self::QR_HEIGHT)
				->render($result['url']);

			$result['qr'] = base64_encode($urlQR);
		}

		return $result;
	}

	/**
	 * @restMethod imconnector.Openlines.delete
	 */
	public function deleteAction(int $lineId, ?string $connectorId = null): bool
	{
		if ($connectorId)
		{
			$connectorOutput = new Output($connectorId, $lineId);

			$rawDelete = $connectorOutput->deleteConnector();
			if ($rawDelete->isSuccess())
			{
				Status::delete($connectorId, $lineId);
			}
			else
			{
				$this->addErrors($rawDelete->getErrors());
				return false;
			}
		}
		else
		{
			Output::deleteLine($lineId);
			InfoConnectors::deleteInfoConnectors($lineId);
		}

		InfoConnectors::updateInfoConnectors($lineId);

		return true;
	}

	/**
	 * @restMethod imconnector.Openlines.setUsers
	 */
	public function setUsersAction(int $lineId, array $userIds): bool
	{
		$config['QUEUE'] = [];
		foreach ($userIds as $userId)
		{
			$user = User::getInstance($userId);

			if (
				!($user->isBot() && $user->isConnector() && $user->isNetwork() && $user->isExtranet())
				&& $user->isActive()
			)
			{
				$config['QUEUE'][] = [
					'ENTITY_TYPE' => self::ENTITY_TYPE_USER,
					'ENTITY_ID' => (int)$userId
				];
			}
		}

		$resultUpdate = (new Config())->update($lineId, $config);

		if(!$resultUpdate->isSuccess())
		{
			$this->addErrors($resultUpdate->getErrors());
			return false;
		}

		return true;
	}

	/**
	 * @restMethod imconnector.Openlines.hasAccess
	 */
	public function hasAccessAction(?int $userId = null): array
	{
		if ($userId)
		{
			$userPermissions = Permissions::createWithUserId($userId);
		}
		else
		{
			$userPermissions = Permissions::createWithCurrentUser();
		}

		return [
			'canEditLine' => $userPermissions->canPerform(Permissions::ENTITY_LINES, Permissions::ACTION_MODIFY),
			'canEditConnector' => $userPermissions->canPerform(Permissions::ENTITY_CONNECTORS, Permissions::ACTION_MODIFY)
		];
	}
}
