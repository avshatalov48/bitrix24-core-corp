<?php

declare(strict_types = 1);

namespace Bitrix\Dav\Public;

enum WebDavMethod: string
{
	case MKCOL = 'MKCOL';
	case PROPPATCH = 'PROPPATCH';
	case PROPFIND = 'PROPFIND';
	case REPORT = 'REPORT';
}
