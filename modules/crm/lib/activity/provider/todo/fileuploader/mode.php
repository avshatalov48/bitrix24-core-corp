<?php

namespace Bitrix\Crm\Activity\Provider\ToDo\FileUploader;

enum Mode: string
{
	case ADD = 'add';
	case COPY = 'copy';
}