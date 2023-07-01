<?php

namespace Bitrix\Disk\Document\OnlyOffice;

use Bitrix\Main;
use Bitrix\Disk\Driver;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\IO\File;
use Bitrix\Main\ModuleManager;

final class Configuration
{
	public const DEFAULT_MAX_FILESIZE = 104857600;
	public const MODE_CLOUD = 2;
	public const MODE_LOCAL = 3;

	/** @var string|null */
	protected $server;
	/** @var string|null */
	protected $secretKey;
	/** @var int|null */
	protected $maxFileSize;
	/** @var array|null */
	protected $localValues;

	public function __construct()
	{
		$this->loadLocalValues();
	}

	protected function loadLocalValues(): void
	{
		$path = $_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/disk-documents.php";
		if (File::isFileExists($path))
		{
			$localValues = require($path);
			if (is_array($localValues))
			{
				$this->localValues = $localValues;
			}
		}
	}

	public function getInstallationMode(): ?int
	{
		return Option::get(Driver::INTERNAL_MODULE_ID, 'disk_onlyoffice_install_mode', null);
	}

	public function getB24DocumentsServerListEndpoint(): ?string
	{
		$b24documentsPrimary = Main\Config\Configuration::getInstance()->get('b24documents');
		$b24documents = Main\Config\Configuration::getInstance('disk')->get('b24documents');

		return $b24documentsPrimary['serverListEndpoint'] ?? $b24documents['serverListEndpoint'];
	}

	public function setInstallationMode(int $mode): void
	{
		if ($mode === self::MODE_LOCAL && !ModuleManager::isModuleInstalled('bitrix24'))
		{
			throw new ArgumentException('Installation mode should be MODE_CLOUD');
		}

		Option::set(Driver::INTERNAL_MODULE_ID, 'disk_onlyoffice_install_mode', $mode);
	}

	/**
	 * @see \Bitrix\DocumentProxy\Controller\Registration::registerClientAction()
	 * @return string|null
	 */
	public function getTempSecretForDomainVerification(): ?string
	{
		return Option::get(Driver::INTERNAL_MODULE_ID, 'disk_onlyoffice_temp_secret', null);
	}

	public function resetTempSecretForDomainVerification(): void
	{
		Option::set(Driver::INTERNAL_MODULE_ID, 'disk_onlyoffice_temp_secret', null);
	}

	public function storeTempSecretForDomainVerification(string $value): void
	{
		Option::set(Driver::INTERNAL_MODULE_ID, 'disk_onlyoffice_temp_secret', $value);
	}

	/**
	 * @param array{clientId: string, secretKey: string, serverHost: string} $data
	 * @return void
	 */
	public function storeCloudRegistration(array $data): void
	{
		if (!isset($data['clientId'], $data['secretKey'], $data['serverHost']))
		{
			return;
		}

		Option::set(Driver::INTERNAL_MODULE_ID, 'disk_onlyoffice_b24_clientId', $data['clientId']);
		Option::set(Driver::INTERNAL_MODULE_ID, 'disk_onlyoffice_b24_secretKey', $data['secretKey']);
		Option::set(Driver::INTERNAL_MODULE_ID, 'disk_onlyoffice_b24_serverHost', $data['serverHost']);
	}

	public function resetCloudRegistration(): void
	{
		Option::delete('disk', [
			'name' => 'disk_onlyoffice_b24_clientId',
		]);
		Option::delete('disk', [
			'name' => 'disk_onlyoffice_b24_secretKey',
		]);
		Option::delete('disk', [
			'name' => 'disk_onlyoffice_b24_serverHost',
		]);
	}

	/**
	 * @return null|array{clientId: string, secretKey: string, serverHost: string}
	 */
	public function getCloudRegistrationData(): ?array
	{
		$data = array_filter([
			'clientId' => Option::get(Driver::INTERNAL_MODULE_ID, 'disk_onlyoffice_b24_clientId'),
			'secretKey' => Option::get(Driver::INTERNAL_MODULE_ID, 'disk_onlyoffice_b24_secretKey'),
			'serverHost' => Option::get(Driver::INTERNAL_MODULE_ID, 'disk_onlyoffice_b24_serverHost'),
		]);

		if (count($data) === 3)
		{
			return $data;
		}

		return null;
	}

	public function getServer(): ?string
	{
		if ($this->server === null)
		{
			$this->server = $this->getValue('server', 'disk_onlyoffice_server');
		}

		return $this->server;
	}

	public function getSecretKey(): ?string
	{
		if ($this->secretKey === null)
		{
			$cloudData = $this->getCloudRegistrationData();
			if (isset($cloudData['secretKey']) && $cloudData['secretKey'])
			{
				$this->secretKey = $cloudData['secretKey'];
			}
			else
			{
				$this->secretKey = $this->getValue('secret_key', 'disk_onlyoffice_secret_key');
			}
		}

		return $this->secretKey;
	}

	public function getMaxFileSize(): ?int
	{
		if ($this->maxFileSize === null)
		{
			$value = $this->getValue('max_filesize', 'disk_onlyoffice_max_filesize');
			$this->maxFileSize = ($value === null || $value === '') ? self::DEFAULT_MAX_FILESIZE : (int)$value;
		}

		return $this->maxFileSize;
	}

	protected function getValue($key, string $optionName): ?string
	{
		$value = $this->getLocalValues($key);
		if ($value === null)
		{
			return Option::get(Driver::INTERNAL_MODULE_ID, $optionName, null);
		}

		return $value;
	}

	public function getLocalValues($key)
	{
		return $this->localValues[$key] ?? null;
	}
}