<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator;

final class ProxyIntegratorResponse extends IntegratorResponse
{
	public const HTTP_STATUS_OK = 200;
	public const HTTP_STATUS_CREATED = 201;
	public const HTTP_STATUS_ACCEPTED = 202;
	public const HTTP_STATUS_ETERNAL_SERVER_ERROR = 500;
	public const HTTP_STATUS_UNAUTHORIZED = 401;
	public const HTTP_STATUS_FORBIDDEN = 403;
	public const HTTP_STATUS_NOT_FOUND = 404;
	public const HTTP_STATUS_SERVICE_FROZEN = 555;

	protected static function parseInnerStatus(mixed $status): int
	{
		return match ((int)$status)
		{
			self::HTTP_STATUS_OK => IntegratorResponse::STATUS_OK,
			self::HTTP_STATUS_CREATED => IntegratorResponse::STATUS_CREATED,
			self::HTTP_STATUS_ACCEPTED => IntegratorResponse::STATUS_IN_PROGRESS,
			self::HTTP_STATUS_UNAUTHORIZED, self::HTTP_STATUS_FORBIDDEN => IntegratorResponse::STATUS_NO_ACCESS,
			self::HTTP_STATUS_ETERNAL_SERVER_ERROR => IntegratorResponse::STATUS_SERVER_ERROR,
			self::HTTP_STATUS_SERVICE_FROZEN => IntegratorResponse::STATUS_FROZEN,
			default => IntegratorResponse::STATUS_UNKNOWN,
		};
	}
}