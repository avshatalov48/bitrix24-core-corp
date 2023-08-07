<?php

namespace Bitrix\Tasks\Integration\AI\Model;

use Bitrix\Tasks\Integration\AI\Restriction;
use Bitrix\Tasks\Integration\AI\WhiteList;

class Image
{
	private string $url;
	private string $scheme;
	private string $host;
	private WhiteList $whiteList;
	private Restriction $restriction;

	public function __construct(string $url, WhiteList $whiteList, Restriction $restriction)
	{
		$this->url = $url;
		$this->whiteList = $whiteList;
		$this->restriction = $restriction;

		$urlData = parse_url($this->url);
		$this->host = $urlData['host'] ?? '';
		$this->scheme = $urlData['scheme'] ?? '';
	}

	public function getUrl(): string
	{
		return $this->url;
	}

	public function isValid(): bool
	{
		return
			$this->whiteList->isHostAvailable($this->host)
			&& $this->whiteList->isSchemeAvailable($this->scheme);
	}

	public function getRestriction(): Restriction
	{
		return $this->restriction;
	}

	public function getWhiteList(): WhiteList
	{
		return $this->whiteList;
	}
}