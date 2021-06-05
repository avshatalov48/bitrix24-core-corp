<?php

namespace Bitrix\Crm\Service\Operation;

use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;

class ConversionResult extends Result
{
	/** @var Uri */
	protected $redirectUrl;
	/** @var bool */
	protected $isConversionFinished;

	public function getRedirectUrl(): ?Uri
	{
		return $this->redirectUrl;
	}

	public function setRedirectUrl(Uri $redirectUrl): ConversionResult
	{
		if ($redirectUrl->getUri() !== '')
		{
			$this->redirectUrl = $redirectUrl;
		}

		return $this;
	}

	public function isConversionFinished(): bool
	{
		return $this->isConversionFinished;
	}

	public function setIsConversionFinished(bool $isConversionFinished): ConversionResult
	{
		$this->isConversionFinished = $isConversionFinished;

		return $this;
	}

}