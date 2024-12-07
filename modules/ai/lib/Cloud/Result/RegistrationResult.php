<?php
declare(strict_types=1);

namespace Bitrix\AI\Cloud\Result;

use Bitrix\AI\Cloud\Dto\RegistrationDto;
use Bitrix\Main\Result;

/**
 * Class RegistrationResult.
 * Represents the result of the registration process.
 */
final class RegistrationResult extends Result
{
	public function __construct(private readonly RegistrationDto $registrationDto)
	{
		parent::__construct();
	}

	public function getRegistrationData(): RegistrationDto
	{
		return $this->registrationDto;
	}
}