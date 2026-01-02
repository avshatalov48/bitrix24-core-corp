<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field;

enum FieldType: string
{
	case CUSTOM = 'custom';
	case SELECT = 'select';
	case RADIO = 'radio';
	case TEXT = 'text';
	case TEXTAREA = 'textarea';
}
