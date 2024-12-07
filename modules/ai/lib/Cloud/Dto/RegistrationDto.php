<?php
declare(strict_types=1);

namespace Bitrix\AI\Cloud\Dto;

/**
 * Registration DTO.
 * Contains data for registration of a new client.
 */
final class RegistrationDto
{
	public function __construct(
		public readonly string $clientId,
		public readonly string $secretKey,
		public readonly string $serverHost,
	)
	{
	}
}