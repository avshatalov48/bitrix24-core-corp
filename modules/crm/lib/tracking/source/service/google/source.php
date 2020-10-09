<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Source\Service\Google;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Tracking;

Loc::loadMessages(__FILE__);

/**
 * Class Source
 *
 * @package Bitrix\Crm\Tracking\Source\Service\Google
 */
class Source extends Tracking\Source\Base
{
	protected $code = self::Ga;
}