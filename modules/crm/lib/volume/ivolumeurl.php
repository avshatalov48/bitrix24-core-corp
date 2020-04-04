<?php

namespace Bitrix\Crm\Volume;

interface IVolumeUrl
{
	/**
	 * Get entity list path.
	 * @return string
	 */
	public function getUrl();

	/**
	 * Get filter alias for url to entity list path.
	 * @return array
	 */
	public function getFilterAlias();

	/**
	 * Get filter reset parems for entity grid.
	 * @return array
	 */
	public function getGridFilterResetParam();
}
