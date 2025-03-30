<?php

namespace Bitrix\Disk\Type;

enum DocumentGridVariant: int
{

	/**
	 * Show all available files
	 */
	case All = 0;
	/**
	 * Show documents and flipchart files
	 */
	case DocumentsList = 1;
	/**
	 * Show only flipchart files
	 */
	case FlipchartList = 2;

}