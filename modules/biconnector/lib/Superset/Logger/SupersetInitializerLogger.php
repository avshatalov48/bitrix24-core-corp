<?php

namespace Bitrix\BIConnector\Superset\Logger;

class SupersetInitializerLogger extends Logger
{
	final protected static function getAuditSubType(): string
	{
		return 'INSTANCE_INITIALIZER';
	}
}