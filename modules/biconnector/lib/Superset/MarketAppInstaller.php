<?php

namespace Bitrix\BIConnector\Superset;

use Bitrix\BiConnector\Configuration\Action;
use Bitrix\BiConnector\Configuration\Manifest;
use Bitrix\BIConnector\Superset\Logger\MarketDashboardLogger;
use Bitrix\Main\Error;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\FileOpenException;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\Configuration\Action\Import;
use Bitrix\Rest\Configuration\DataProvider;
use Bitrix\Rest\Configuration\Helper;
use Bitrix\Rest\Configuration\Setting;
use Bitrix\Rest\Configuration\Structure;
use Bitrix\Rest\Marketplace\Application;

class MarketAppInstaller
{
	private static ?MarketAppInstaller $instance = null;

	public static function getInstance(): self
	{
		return self::$instance ?? new self;
	}

	/**
	 * Installs dashboard application by code and version.
	 * Should be called only from MarketDashboardManager.
	 * @see \Bitrix\BIConnector\Superset\MarketDashboardManager
	 *
	 * @param string $code
	 * @param int|null $version
	 * @param string|null $checkHash
	 * @param string|null $installHash
	 * @return Result
	 */
	public function installApplication(
		string $code,
		?int $version = null,
		?string $checkHash = null,
		?string $installHash = null,
	): Result
	{
		$result = new Result();
		Application::setContextUserId($this->getAdminId());
		$installResult = Application::install(
			code: $code,
			version: $version,
			checkHash: $checkHash,
			installHash: $installHash,
		);

		if (isset($installResult['errorDescription']))
		{
			MarketDashboardLogger::logErrors([new Error($installResult['errorDescription'])], [
				'message' => 'Cannot install rest application',
				'app_code' => $code,
				'version' => $version ?? 'no version',
			]);

			$result->addError(new Error($installResult['errorDescription']));

			return $result;
		}

		$importResult = $this->importConfiguration($installResult['id']);
		if (!$importResult->isSuccess())
		{
			MarketDashboardLogger::logErrors($importResult->getErrors(), [
				'message' => 'Cannot import configuration',
				'app_code' => $code,
				'version' => $version ?? 'no version',
			]);
			$result->addErrors($importResult->getErrors());
		}

		return $result;
	}

	private function importConfiguration(int $appId): Result
	{
		$result = new Result();

		$app = AppTable::getRowById($appId);
		$prepareArchiveResult = $this->prepareArchive($app);
		if (!$prepareArchiveResult->isSuccess())
		{
			$result->addErrors($prepareArchiveResult->getErrors());
		}

		$import = $this->prepareImport($app);
		$prepareContentResult = $this->prepareContent($app);
		if (!$prepareContentResult->isSuccess())
		{
			$result->addErrors($prepareContentResult->getErrors());
		}

		$content = $prepareContentResult->getData()['content'];
		if (!$content)
		{
			$result->addError(new Error("importConfiguration: Content is empty. {$app['CODE']}"));
			return $result;
		}

		$step = 0;
		$type = Action::ENTITY_CODE;
		$import->doLoad(
			$step,
			$type,
			$content
		);
		$importErrorList = $import->getNotificationInstance()?->list();
		foreach ($importErrorList as $error)
		{
			if (
				($error['type'] ?? null) === 'exception'
				&& ($error['message'] ?? '') !== ''
			)
			{
				$result->addError(new Error($error['message']));
			}
		}
		$import->doFinish();

		return $result;
	}

	private function getContext(string $appCode): string
	{
		$postfix = 'import' . $appCode;

		return Helper::getInstance()->getContextUser($postfix);
	}

	private function prepareImport(array $app): Import
	{
		$context = $this->getContext($app['CODE']);
		$manifestCode = Manifest::MANIFEST_CODE_SUPERSET;

		$import = new Import();
		$import->setContext($context);
		$import->setManifestCode($manifestCode);
		$import->doStart($app);
		$adminId = $this->getAdminId();

		$import->getSetting()->set(
			Setting::SETTING_USER_ID,
			$adminId
		);

		return $import;
	}

	private function getAdminId(): int
	{
		$userId = 0;
		$admin = \CUser::GetList(
			'ID',
			'ASC',
			['GROUPS_ID' => [1], 'ACTIVE' => 'Y'],
			['FIELDS' => ['ID'], 'NAV_PARAMS' => ['nTopCount' => 1]]
		)->fetch();
		if ($admin)
		{
			$userId = $admin['ID'];
		}
		else
		{
			global $USER;
			if ($USER->IsAuthorized())
			{
				$userId = $USER->GetID();
			}
		}

		return $userId;
	}

	private function prepareArchive(array $app): Result
	{
		$result = new Result();

		try
		{
			$fileInfo = \CFile::makeFileArray(Uri::urnEncode($app['URL']));
		}
		catch (FileOpenException $e)
		{
			$result->addError(new Error("prepareArchive: file at {$app['URL']} cannot be opened: " . $e->getMessage()));
		}

		if (empty($fileInfo['tmp_name']))
		{
			$result->addError(new Error("prepareArchive: tmp_name of file at {$app['URL']} is not specified."));

			return $result;
		}

		$checkResult = \CFile::checkFile(
			arFile: $fileInfo,
			mimeType: [
				'application/gzip',
				'application/x-gzip',
				'application/zip',
				'application/x-zip-compressed',
				'application/x-tar'
			],
		);
		if ($checkResult !== '')
		{
			$result->addError(new Error($checkResult));

			return $result;
		}

		$context = $this->getContext($app['CODE']);
		$setting = new Setting($context);
		$setting->deleteFull();

		$structure = new Structure($context);
		if ($structure->unpack($fileInfo))
		{
			$data = [];
			$data['IMPORT_CONTEXT'] = $context;
			$data['APP'] = $app;
			$data['IMPORT_FOLDER_FILES'] = $structure->getFolder();
			$data['IMPORT_ACCESS'] = true;
			$result->setData($data);
		}
		else
		{
			$fileInfoToLog = Json::encode($fileInfo);
			$result->addError(new Error("prepareArchive: Unpacking file $fileInfoToLog failed."));

			return $result;
		}

		return $result;
	}

	private function prepareContent(array $app): Result
	{
		$result = new Result();

		$step = 0;
		$context = $this->getContext($app['CODE']);
		$type = Action::ENTITY_CODE;
		$structure = new Structure($context);
		$folder = $structure->getFolder() . $type;
		if (!Directory::isDirectoryExists($folder))
		{
			$result->addError(new Error("prepareContent: Folder $folder was not found."));

			return $result;
		}

		$fileList = array_values(array_diff(scandir($folder), ['.', '..']));
		$count = count($fileList);
		if (!isset($fileList[$step]))
		{
			$result->addError(new Error("prepareContent: Folder $folder is empty."));

			return $result;
		}

		$providerCode = DataProvider\Controller::CODE_IO;
		$path = $folder . '/' . $fileList[$step];

		/** @var DataProvider\ProviderBase $disk */
		$provider = DataProvider\Controller::getInstance()->get(
			$providerCode,
			[
				'CONTEXT' => 'app' . $app['ID'],
				'CONTEXT_USER' => $context,
			]
		);

		if (!$provider)
		{
			$result->addError(new Error("prepareContent: Provider with CODE = $providerCode, CONTEXT = app{$app['ID']} and CONTEXT_USER = $context was not found."));

			return $result;
		}

		$content = $provider->getContent($path, $step);
		if ($content['ERROR_CODE'])
		{
			$result->addError(new Error("prepareContent: \Bitrix\Rest\Configuration\DataProvider\ProviderBase::getContent caught {$content['ERROR_CODE']} exception."));

			return $result;
		}

		if ($content['COUNT'] === 0)
		{
			$content['COUNT'] = $count;
		}

		$result->setData(['content' => $content]);

		return $result;
	}
}
