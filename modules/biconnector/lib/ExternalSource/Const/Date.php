<?php

namespace Bitrix\BIConnector\ExternalSource\Const;

enum Date: string
{
	case Ymd_dot = 'Y.m.d';
	case Ydm_dot = 'Y.d.m';
	case dmY_dot = 'd.m.Y';
	case mdY_dot = 'm.d.Y';
	case Ymd_dash = 'Y-m-d';
	case Ydm_dash = 'Y-d-m';
	case dmY_dash = 'd-m-Y';
	case mdY_dash = 'm-d-Y';
	case Ymd_slash = 'Y/m/d';
	case Ydm_slash = 'Y/d/m';
	case dmY_slash = 'd/m/Y';
	case mdY_slash = 'm/d/Y';
}
