<?php

namespace Bitrix\Voximplant\Integration\Report\Handler\CallActivity;

use Bitrix\Voximplant\Integration\Report\Handler\Base;

/**
 * Class CallActivity
 * @package Bitrix\Voximplant\Integration\Report\Handler\CallActivity
 */
abstract class CallActivity extends Base
{
	protected $reportFilterKeysForSlider = [
		'PORTAL_NUMBER',
		'PHONE_NUMBER',
		'PORTAL_USER_ID'
	];
}