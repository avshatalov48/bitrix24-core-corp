<?php

namespace Bitrix\Mobile\Collab\Dto;

class CollabTaskPermissionsSettingsDto
{
	public function __construct(
		public string $view_all = 'K',
		public string $sort = 'K',
		public string $create_tasks = 'K',
		public string $edit_tasks = 'E',
		public string $delete_tasks = 'E',
	)
	{

	}
}