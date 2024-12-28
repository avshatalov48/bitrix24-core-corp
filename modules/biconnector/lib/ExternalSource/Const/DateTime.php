<?php

namespace Bitrix\BIConnector\ExternalSource\Const;

enum DateTime: string
{
	case Ymd_dot_His_colon = 'Y.m.d H:i:s';
	case Ydm_dot_His_colon = 'Y.d.m H:i:s';
	case dmY_dot_His_colon = 'd.m.Y H:i:s';
	case mdY_dot_His_colon = 'm.d.Y H:i:s';
	case Ymd_dash_His_colon = 'Y-m-d H:i:s';
	case Ydm_dash_His_colon = 'Y-d-m H:i:s';
	case dmY_dash_His_colon = 'd-m-Y H:i:s';
	case mdY_dash_His_colon = 'm-d-Y H:i:s';
	case Ymd_slash_His_colon = 'Y/m/d H:i:s';
	case Ydm_slash_His_colon = 'Y/d/m H:i:s';
	case dmY_slash_His_colon = 'd/m/Y H:i:s';
	case mdY_slash_His_colon = 'm/d/Y H:i:s';
}
