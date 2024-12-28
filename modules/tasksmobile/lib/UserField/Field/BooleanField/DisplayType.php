<?php

namespace Bitrix\TasksMobile\UserField\Field\BooleanField;

enum DisplayType : string
{
	case Checkbox = 'CHECKBOX';
	case Dropdown = 'DROPDOWN';
	case Radio = 'RADIO';
}
