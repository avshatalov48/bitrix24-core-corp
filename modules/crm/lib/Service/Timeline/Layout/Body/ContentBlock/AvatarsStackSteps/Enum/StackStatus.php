<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\AvatarsStackSteps\Enum;

enum StackStatus: string
{
	case Ok = 'ok';
	case Wait = 'wait';
	case Cancel = 'cancel';
	case Custom = 'custom';
}
