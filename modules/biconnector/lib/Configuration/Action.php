<?php


namespace Bitrix\BiConnector\Configuration;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Context;
use Bitrix\Main\Event;
use Bitrix\Rest\Configuration\Manifest;
use Bitrix\Rest\Configuration\Setting;
use Bitrix\Rest\Configuration\Structure;
use Bitrix\BIConnector\Services\GoogleDataStudio;
use Bitrix\BIConnector\KeyTable;
use Bitrix\BIConnector\KeyManager;
use CFile;

Loc::loadMessages(__FILE__);

class Action
{
	public const ENTITY_CODE = 'BUSINESS_INTELLIGENCE';
	public const ENTITY_TYPE_POWER_BI = 'POWER_BI';
	public const ENTITY_TYPE_DATA_STUDIO = 'DATA_STUDIO';
	private static $entityList = [
		self::ENTITY_CODE => 1000,
	];

	private static $accessManifest = [
		'bi'
	];

	/**
	 * @return array of entity
	 */
	public static function getEntityList()
	{
		return static::$entityList;
	}

	/**
	 * Checks event CODE availability.
	 *
	 * @param Event $event Event parameters.
	 *
	 * @return bool
	 */
	private static function checkAccess(Event $event): bool
	{
		$code = $event->getParameter('CODE');

		return
			static::$entityList[$code]
			&& Manifest::isEntityAvailable($code, $event->getParameters(), static::$accessManifest);
	}

	/**
	 * Event onRestApplicationConfigurationImport handler.
	 *
	 * @param Event $event Event parameters.
	 *
	 * @return null|array
	 */
	public static function onImport(Event $event)
	{
		$result = null;

		if (!static::checkAccess($event))
		{
			return $result;
		}

		$content = $event->getParameter('CONTENT');
		if (!empty($content['DATA']['type']))
		{
			if ($content['DATA']['type'] === static::ENTITY_TYPE_POWER_BI)
			{
				$result = static::importPowerBI($content, $event);
			}
			elseif ($content['DATA']['type'] === static::ENTITY_TYPE_DATA_STUDIO)
			{
				$result = static::importDataStudio($content, $event);
			}
		}

		return $result;
	}

	/**
	 * importDataStudio
	 *
	 * @param array $content Event parameter CONTENT.
	 * @param Event $event All event parameters.
	 *
	 * @return null|array
	 */
	private static function importDataStudio($content, Event $event)
	{
		$result = null;
		$appId = (int)$event->getParameter('APP_ID');
		$host = Context::getCurrent()->getServer()->get('HTTP_HOST');
		$accessKey = false;
		$res = KeyTable::getList([
			'select' => [
				'ACCESS_KEY',
			],
			'filter' => [
				'=ACTIVE' => 'Y',
				'=APP_ID' => $appId,
			],
			'order' => [
				'ID' => 'DESC',
			],
			'limit' => 1,
		]);

		if ($key = $res->fetch())
		{
			$accessKey = $key['ACCESS_KEY'];
		}
		else
		{
			$userId = (int)$event->getParameter('USER_ID');
			if ($userId <= 0)
			{
				global $USER;
				$userId = $USER->getID();
			}

			$key = KeyManager::generateAccessKey();
			$resultSave = KeyManager::save([
				'USER_ID' => $userId,
				'ACTIVE' => true,
				'ACCESS_KEY' => $key,
				'USERS' => [
					$userId,
				],
				'APP_ID' => $appId,
			]);

			if ($resultSave instanceof ErrorCollection)
			{
				foreach ($resultSave->getValues() as $error)
				{
					if ($error instanceof \Bitrix\Main\Error)
					{
						$result['ERROR_EXCEPTION'] = [
							'code' => $error->getCode(),
							'message' => Loc::getMessage('BI_CONNECTOR_CONFIGURATION_ACTION_ERROR_DATA_STUDIO_SAVE_KEY') . ' ' . $error->getMessage(),
						];
					}
				}
			}
			else
			{
				$accessKey = $key;
			}
		}

		if ($accessKey)
		{
			$url = [
				'connectorId' => Option::get('biconnector', GoogleDataStudio::OPTION_DEPLOYMENT_ID),
				'connectorConfig' => Json::encode([
						'server_name' => Option::get('main', 'server_name', $host),
						'key' => $accessKey . LANGUAGE_ID,
						'table' => $content['DATA']['table'] ?? '',
				]),
				'reportTemplateId' => $content['DATA']['reportTemplateId'],
			];

			$uri = new Uri(GoogleDataStudio::URL_CREATE);
			$uri->addParams($url);

			$result['RATIO']['URL'] = $uri->getLocator();
		}

		return $result;
	}

	/**
	 * importPowerBI
	 *
	 * @param array $content Event parameter CONTENT.
	 * @param Event $event All event parameters.
	 *
	 * @return null|array
	 */
	private static function importPowerBI($content, Event $event)
	{
		$result = null;
		if ((int)$content['DATA']['fileId'] > 0)
		{
			$contextUser = $event->getParameter('CONTEXT_USER');
			$structure = new Structure($contextUser);
			$fileInfo = $structure->getUnpackFile((int)$content['DATA']['fileId']);
			if (!empty($fileInfo['PATH']))
			{
				$file = CFile::makeFileArray(
					$fileInfo['PATH']
				);

				$file['MODULE_ID'] = 'rest';
				$file['name'] = $fileInfo['NAME'];
				$setting = new Setting($contextUser);
				$fileId = CFile::saveFile(
					$file,
					'configuration/' . static::ENTITY_CODE
				);
				$isSave = $setting->set(
					Structure::CODE_CUSTOM_FILE . static::ENTITY_TYPE_POWER_BI . time(),
					[
						'ID' => $fileId,
					]
				);
				if ($isSave)
				{
					$result['RATIO']['DOWNLOAD_FILE_ID'] = $fileId;
					$result['RATIO']['DOWNLOAD_FILE_NAME'] = $fileInfo['NAME'];
				}
				else
				{
					$result['EXCEPTION'] = 'error';
				}
			}
		}

		return $result;
	}

	/**
	 * Event OnRestApplicationConfigurationExport handler.
	 * Returns null to skip no access step.
	 *
	 * @param Event $event Event Parameters.
	 *
	 * @return null|array export result
	 */
	public static function onExport(Event $event)
	{
		$result = null;

		if (static::checkAccess($event))
		{
			$result = [
				'ERROR_MESSAGES' => Loc::getMessage('BI_CONNECTOR_CONFIGURATION_ACTION_EXPORT_HOLD'),
			];
		}

		return $result;
	}

	/**
	 * Event OnRestApplicationConfigurationFinish handler.
	 *
	 * @param Event $event Event Parameters.
	 *
	 * @return array
	 */
	public static function onFinish(Event $event)
	{
		$result = [
			'CREATE_DOM_LIST' => [],
		];

		$ratio = $event->getParameter('RATIO');

		if ((int)$ratio[self::ENTITY_CODE]['DOWNLOAD_FILE_ID'] > 0)
		{
			$path = CFile::getPath((int)$ratio[self::ENTITY_CODE]['DOWNLOAD_FILE_ID']);
			if ($path)
			{
				$result['CREATE_DOM_LIST'][] = [
					'TAG' => 'a',
					'DATA' => [
						'attrs' => [
							'class' => 'ui-btn ui-btn-lg ui-btn-primary',
							'href' => $path,
							'download' => htmlspecialcharsbx($ratio[self::ENTITY_CODE]['DOWNLOAD_FILE_NAME']),
						],
						'text' => Loc::getMessage('BI_CONNECTOR_CONFIGURATION_ACTION_DOWNLOAD_BTN'),
					],
				];
			}
		}

		if (!empty($ratio[self::ENTITY_CODE]['URL']))
		{
			$result['CREATE_DOM_LIST'][] = [
				'TAG' => 'a',
				'DATA' => [
					'attrs' => [
						'class' => 'ui-btn ui-btn-lg ui-btn-primary',
						'href' => $ratio[self::ENTITY_CODE]['URL'],
						'target' => '_blank',
					],
					'text' => Loc::getMessage('BI_CONNECTOR_CONFIGURATION_ACTION_CONNECT_BTN'),
				],
			];
		}

		return !empty($result['CREATE_DOM_LIST']) ? $result : [];
	}

	/**
	 * Event onBeforeApplicationUninstall handler.
	 *
	 * @param Event $event Event Parameters.
	 *
	 * @return null
	 */
	public static function onBeforeRestApplicationDelete(Event $event)
	{
		$appId = (int)$event->getParameter('ID');

		if ($appId > 0)
		{
			$res = KeyTable::getList(
				[
					'select' => [
						'ID',
					],
					'filter' => [
						'=ACTIVE' => 'Y',
						'=APP_ID' => $appId,
					],
					'order' => [
						'ID' => 'DESC',
					],
				]
			);

			while ($item = $res->fetch())
			{
				KeyTable::update(
					$item['ID'],
					[
						'ACTIVE' => 'N',
					]
				);
			}
		}

		return null;
	}
}
