<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Exception;

use \Bitrix\Main;

class BaasControllerRespondsInWrongFormatException extends UseCaseException
{
	protected $code = 8700;

	public function __construct(protected ?array $fieldNames = null)
	{
		parent::__construct();
	}

	public function getLocalizedMessage(): string
	{
		if ($this->fieldNames === null)
		{
			return Main\Localization\Loc::getMessage(
				'BAAS_EXCEPTION_BAASCONTROLLER_RESPONDS_IN_WRONG_FORMAT',
			)
				?? 'Baas controller responds in a wrong format.';
		}

		$replacement = implode(', ', $this->fieldNames);

		return Main\Localization\Loc::getMessage(
			'BAAS_EXCEPTION_BAASCONTROLLER_MUST_RESPOND_WITH',
			['#FIELDS#' => $replacement],
		)
			?? 'Baas controller must contain the following fields in the response: ' . $replacement . '.';
	}
}
