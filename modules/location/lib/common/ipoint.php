<?php

namespace Bitrix\Location\Common;

/**
 * Interface IPoint
 * @package Bitrix\Location\Common
 */
interface IPoint
{
	/**
	 * @return string
	 */
	public function getLatitude(): string;

	/**
	 * @return string
	 */
	public function getLongitude(): string;
}