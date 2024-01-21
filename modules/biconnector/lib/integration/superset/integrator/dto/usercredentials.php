<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator\Dto;

final class UserCredentials
{
	public function __construct(
		public string $login,
		public string $password,
	){}
}