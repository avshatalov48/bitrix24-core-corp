<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator\Dto;

final class User
{
	public function __construct(
		public int $id,
		public string $userName,
		public string $email,
		public string $firstName,
		public string $lastName,
		public ?string $clientId = null,
		public ?string $permissionHash = null,
	)
	{}
}
