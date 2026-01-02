<?php

namespace Bitrix\Baas\Config;

use Bitrix\Main;
use Bitrix\Bitrix24;

class Server extends Config
{
	private const SERVER_URLS = [
		'ru' => 'https://baas-cis.bitrix24.tech',
		'by' => 'https://baas-cis.bitrix24.tech',
		'kz' => 'https://baas-cis.bitrix24.tech',
		'en' => 'https://baas.bitrix.info',
	];
	private const OPTION_NAME_SERVER_URL = 'server_url';

	private Bitrix24\License|Main\License $license;

	public function __construct(Bitrix24\License|Main\License $license)
	{
		$this->license = $license;
	}

	protected function getModuleId(): string
	{
		return 'baas';
	}

	public function getUrl(): string
	{
		$url = $this->get(self::OPTION_NAME_SERVER_URL);
		if (empty($url))
		{
			$url = $this->getDefaultUrlByLicense($this->license);
		}

		return $url;
	}

	protected function getDefaultUrlByLicense(Bitrix24\License|Main\License $license): string
	{
		return self::SERVER_URLS[$license->getRegion()] ?? self::SERVER_URLS['en'];
	}

	public function setUrl(string $url): static
	{
		$this->set(self::OPTION_NAME_SERVER_URL, $url);

		return $this;
	}

	public function resetUrl(): static
	{
		$this->delete(self::OPTION_NAME_SERVER_URL);

		return $this;
	}

	/**
	 * For the test purposes only
	 * @param string $url
	 * @return string|null
	 */
	public function getProxyUrl(): ?string
	{
		return $this->get('proxy_url');
	}

	/**
	 * For the test purposes only
	 * @param string $url
	 * @return $this
	 */
	public function setProxyUrl(string $url): static
	{
		$this->set('proxy_url', $url);

		return $this;
	}

	/**
	 * For the test purposes only
	 * @return $this
	 */
	public function resetProxyUrl(): static
	{
		$this->delete('proxy_url');

		return $this;
	}
}
