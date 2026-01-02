<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\Proxy\Exception;

use \Bitrix\Main;

class ProxyRespondsInWrongFormatException extends UseCaseException
{
	protected $code = 7700;

	public function __construct(protected ?array $fieldNames = null)
	{
		parent::__construct();
	}

	public function getLocalizedMessage(): string
	{
		if ($this->fieldNames === null)
		{
			return Main\Localization\Loc::getMessage(
				'BAAS_EXCEPTION_BAASPROXY_RESPONDS_IN_WRONG_FORMAT',
			)
				?? 'Baas proxy responds in a wrong format.';
		}

		$replacement = implode(', ', $this->fieldNames);

		return Main\Localization\Loc::getMessage(
			'BAAS_EXCEPTION_BAASPROXY_MUST_RESPOND_WITH',
			['#FIELDS#' => $replacement],
		)
			?? 'Baas proxy must contain the following fields in the response: ' . $replacement . '.';
	}
}
