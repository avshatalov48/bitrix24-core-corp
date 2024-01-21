<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator\Dto;

final class DashboardEmbeddedCredentials
{
	public function __construct(
		public string	$uuid,
		public string	$guestToken,
		public string	$supersetDomain,
	)
	{}
}