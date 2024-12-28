<?php

namespace Bitrix\Intranet\Enum;

enum UserRole: string
{
	case ADMIN = 'admin';
	case INTRANET = 'employee';
	case INTEGRATOR = 'integrator';
	case COLLABER = 'collaber';
	case EXTRANET = 'extranet';
	case VISITOR = 'visitor';
	case EMAIL = 'email';
	case SHOP = 'shop';
	case EXTERNAL = 'external';
}
