<?php

declare(strict_types=1);

namespace Bitrix\Disk\Public\Service\UnifiedLink;


use Bitrix\Disk\File;
use Bitrix\Main\Web\Uri;

class UrlGenerator
{
	public const PREFIX = '/disk/file/';
	public const EDIT_SUFFIX = '/edit';
	public const QUERY_PARAM_NO_REDIRECT = 'no_redirect';
	public const QUERY_PARAM_ATTACHED_ID = 'attachedId';
	public const QUERY_PARAM_VERSION_ID = 'versionId';

	private bool $absolute = false;
	private bool $editMode = false;
	private bool $withoutRedirect = false;
	private array $additionalQueryParams = [];

	public function __construct(
		private readonly string $hostUrl
	)
	{
	}

	public function build(File $file, ?int $attachedId = null, ?int $versionId = null): string
	{
		$uniqueCode = (string)$file->getRealObject()?->getUniqueCode();

		$path = self::PREFIX . $uniqueCode;

		if ($this->editMode)
		{
			$path .= self::EDIT_SUFFIX;
		}

		$uri = (new Uri($this->hostUrl))
			->setPath($path)
			->addParams(array_filter([
				self::QUERY_PARAM_ATTACHED_ID => $attachedId,
				self::QUERY_PARAM_VERSION_ID => $versionId,
			]))
		;

		if ($this->withoutRedirect)
		{
			$uri->addParams([self::QUERY_PARAM_NO_REDIRECT => 'Y']);
		}

		if ($this->absolute)
		{
			$uri->toAbsolute();
		}

		if (!empty($this->additionalQueryParams))
		{
			$uri->addParams($this->additionalQueryParams);
		}

		$this->resetState();

		return (string)$uri;
	}

	public function asAbsolute(bool $absolute): static
	{
		$this->absolute = $absolute;

		return $this;
	}

	public function forEditing(bool $editMode): static
	{
		$this->editMode = $editMode;

		return $this;
	}

	public function withoutRedirect(bool $withoutRedirect): static
	{
		$this->withoutRedirect = $withoutRedirect;

		return $this;
	}

	public function setAdditionalQueryParams(array $additionalQueryParams): static
	{
		$this->additionalQueryParams = $additionalQueryParams;

		return $this;
	}

	private function resetState(): void
	{
		$this->absolute = false;
		$this->editMode = false;
		$this->withoutRedirect = false;
		$this->additionalQueryParams = [];
	}
}