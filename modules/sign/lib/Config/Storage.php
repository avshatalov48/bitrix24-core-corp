<?php
namespace Bitrix\Sign\Config;

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Intranet\Util;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Main\Loader;
use Bitrix\Sign\Service\Sign\UrlGeneratorService;

class Storage
{
	private const INTRANET_TOOL_ID = 'sign';

	private static ?self $instance = null;

	public static function instance(): self
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function isAvailable(?string $region = null): bool
	{
		if (!Loader::includeModule('crm'))
		{
			return false;
		}

		static $isAvailable = [];

		$region = $region ?: Main\Application::getInstance()->getLicense()->getRegion();

		if (isset($isAvailable[$region]))
		{
			return $isAvailable[$region];
		}

		if (empty($this->getServiceAddress($region)))
		{
			$isAvailable[$region] = false;

			return false;
		}

		$isPublic = ($this->read('service.publicity')[$region] ?? false) === true;
		if (!$isPublic && Main\Config\Option::get('sign', '~available', 'N') !== 'Y')
		{
			$isAvailable[$region] = false;

			return false;
		}

		if (!$this->isSignToolEnabled() || !$this->isCrmToolEnabled())
		{
			$isAvailable[$region] = false;

			return false;
		}

		if (!(bool)\Bitrix\Main\Config\Option::get('crm', 'DOCUMENTS_SIGNING_ENABLED', false))
		{
			Crm\Settings\Crm::setDocumentSigningEnabled(true);
			\Bitrix\Crm\Service\Container::getInstance()->getDynamicTypesMap()->invalidateTypesCollectionCache();
		}

		$isAvailable[$region] = true;

		return true;
	}

	public function isB2eAvailable(?string $region = null): bool
	{
		static $isB2eAvailable = [];

		$region = $region ?: Main\Application::getInstance()->getLicense()->getRegion();

		if (!isset($isB2eAvailable[$region]))
		{
			$isPublic = ($this->read('service.b2e.publicity')[$region] ?? false) === true;
			if (!$isPublic && Main\Config\Option::get('sign', '~b2e_available', 'N') !== 'Y')
			{
				$isB2eAvailable[$region] = false;

				return false;
			}

			$isB2eAvailable[$region] = $this->isAvailable($region);
		}

		return $isB2eAvailable[$region];
	}

	/**
	 * @return bool
	 */
	public function isNewSignEnabled(): bool
	{
		$isAllowed = ($this->read('service.new.ui') ?? false) === true;
		return $isAllowed || Main\Config\Option::get('sign', 'NEW_SIGN_UI', 'N') === 'Y';
	}

	public function getSelfAddress(): ?string
	{
		$address = Main\Web\WebPacker\Builder::getDefaultSiteUri();
		$host = Main\Config\Option::get('sign', 'public_url');
		if ($host)
		{
			$address = (new Main\Web\Uri($address))->setHost($host)->getLocator();
		}

		return $address;
	}

	public function getSelfHost(): ?string
	{
		$host = Main\Config\Option::get('sign', 'public_url');
		$host = $host ?: $this->read('host');
		$host = $host ?: Main\Context::getCurrent()->getServer()->getHttpHost();
		if ($host)
		{
			return $host;
		}

		$host = Main\Web\WebPacker\Builder::getDefaultSiteUri();
		if ($host)
		{
			$host = (new Main\Web\Uri($host))->getHost();
		}

		return $host;
	}

	public function getServiceAddress(?string $region = null): ?string
	{
		if (Main\Config\Option::get('sign', 'use_proxy_local') === 'Y')
		{
			$address = Main\Config\Option::get('sign', 'proxy_local');
			$address = $address ?: $this->getSelfAddress();
			$host = (new Main\Web\Uri($address))->getHost();
			$proto = mb_substr($address, 0, mb_strpos($address, ':'));
			return "{$proto}://{$host}";
		}

		$address = Main\Config\Option::get('sign', 'service_address');
		if (!$address)
		{
			$region = $region ?: Main\Application::getInstance()->getLicense()->getRegion();
			$address = $this->read('service.address')[$region] ?? null;
		}

		if ($address && !preg_match("#^https?://#", $address))
		{
			$address = "https://{$address}";
		}

		return $address;
	}

	public function getBitrixServiceAddress(?string $region = null): ?string
	{
		$region = $region ?: Main\Application::getInstance()->getLicense()->getRegion();

		return $this->read('bitrix.service.domain.address')[$region]
			?? $this->read('bitrix.service.domain.address')['en'];
	}

	public function getApiEndpoint(): string
	{
		return $this->getServiceAddress() . '/api/';
	}

	public function getServiceSignLink(
		string $docHash = '',
		string $memberHash = '',
		?string $region = null,
	): string
	{
		if (Main\Config\Option::get('sign', 'use_proxy_local') === 'Y')
		{
			return Main\Config\Option::get('sign', 'proxy_local');
		}

		$link = str_replace(
			'#address#',
			$this->getServiceAddress($region),
			$this->read('service.doc.link'),
		);

		if ($docHash)
		{
			$link = str_replace('#doc_hash#', $docHash, $link);
		}
		if ($memberHash)
		{
			$link = str_replace('#member_hash#', $memberHash, $link);
		}

		return $link;
	}

	private function read(string $name)
	{
		$value = Main\Config\Configuration::getValue('sign')[$name] ?? null;
		if ($value !== null)
		{
			return $value;
		}

		return Main\Config\Configuration::getInstance('sign')->get($name);
	}

	public function isToursDisabled(): bool
	{
		return Main\Config\Option::get('sign.tour', 'DISABLE_ALL_TOURS', "N") === "Y";
	}

	public function getImagesCountLimitForBlankUpload(): int
	{
		return (int) Main\Config\Option::get('sign', 'max_count_pages_img');
	}

	/**
	 * @return int size in bytes
	 */
	public function getUploadTotalMaxSize(): int
	{
		return 1024 * (int)Main\Config\Option::get('sign', 'max_upload_total_size_kb');
	}

	/**
	 * @return int size in bytes
	 */
	public function getUploadDocumentMaxSize(): int
	{
		return 1024 * (int)Main\Config\Option::get('sign', 'max_upload_doc_size_kb');
	}

	/**
	 * @return int size in bytes
	 */
	public function getUploadImagesMaxSize(): int
	{
		return 1024 * (int)Main\Config\Option::get('sign', 'max_upload_image_size_kb');
	}

	public function getClientId(): ?string
	{
		return Main\Config\Option::get('sign', '~sign_safe_client_id', null);
	}

	public function getClientToken(): ?string
	{
		return Main\Config\Option::get('sign', '~sign_safe_client_token_id', null);
	}

	public function setClientId(string $id): self
	{
		Main\Config\Option::set('sign', '~sign_safe_client_id', $id);
		return $this;
	}

	public function setClientToken(string $token): self
	{
		Main\Config\Option::set('sign', '~sign_safe_client_token_id', $token);
		return $this;
	}

	public function makeCallbackUri(): string
	{
		return Main\Service\MicroService\Client::getServerName()
			. "/bitrix/services/main/ajax.php"
			. "?action=sign.callback.handle"
		;
	}

	public function getLicenseToken(): string
	{
		$licenseData = [
			'BX_TYPE' => Main\Service\MicroService\Client::getPortalType(),
			'BX_LICENCE' => Main\Service\MicroService\Client::getLicenseCode(),
			'SERVER_NAME' => Main\Service\MicroService\Client::getServerName(),
		];
		$licenseData['BX_HASH'] = Main\Service\MicroService\Client::signRequest($licenseData);

		return base64_encode(
			Main\Web\Json::encode(
				[
					'payload' => $licenseData,
					'region' => Main\Application::getInstance()->getLicense()->getRegion(),
				],
			),
		);
	}

	public function getLanguages(): array
	{
		if (!Main\Loader::includeModule('intranet'))
		{
			return [];
		}

		return Util::getLanguageList();
	}

	public function getSavedDomain(): ?string
	{
		return \Bitrix\Main\Config\Option::get('sign', 'current_domain', null);
	}

	public function setCurrentDomain(string $currentDomain): void
	{
		\Bitrix\Main\Config\Option::set('sign', 'current_domain', $currentDomain);
	}

	private function isSignToolEnabled(): bool
	{
		if (
			Main\Loader::includeModule('intranet')
			&& class_exists('\Bitrix\Intranet\Settings\Tools\ToolsManager')
		)
		{
			return ToolsManager::getInstance()->checkAvailabilityByToolId(self::INTRANET_TOOL_ID);
		}

		return true;
	}

	private function isCrmToolEnabled(): bool
	{
		if (
			Main\Loader::includeModule('crm')
			&& class_exists('\Bitrix\Crm\Integration\Intranet\ToolsManager')
		)
		{
			return (new Crm\Integration\Intranet\ToolsManager())->checkCrmAvailability();
		}

		return true;
	}

	/**
	 * @deprecated use url generator
	 * @see UrlGeneratorService::makeMySafeUrl()
	 */
	public function getB2eMySafeUrl(): string
	{
		return '/sign/b2e/mysafe/';
	}

	/**
	 * @deprecated use url generator
	 * @see UrlGeneratorService::makeProfileSafeUrl()
	 */
	public function getProfileSafeUrl(int $userId): string
	{
		return '/company/personal/user/'.$userId.'/sign';
	}

	public function isEdoRegion(?string $regionCode = null): bool
	{
		$regionCode = $regionCode ?? \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();

		return in_array($regionCode, ['ru', 'by'], true);
	}

	public function getFieldsFillMembersLimit(): int
	{
		$option = (int)\Bitrix\Main\Config\Option::get('sign', 'FIELDS_FILL_MEMBER_LIMIT');

		return $option > 0 ? $option : 30;
	}

	public function isBlankExportAllowed(): bool
	{
		return \Bitrix\Main\Config\Option::get('sign', 'TEMPLATE_EXPORT_ALLOWED', 'N') === 'Y';
	}
}
