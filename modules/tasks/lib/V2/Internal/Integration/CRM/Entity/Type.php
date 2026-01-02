<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\CRM\Entity;

enum Type: string
{
	case Deal = 'deal';
	case Lead = 'lead';
	case Company = 'company';
	case Contact = 'contact';
	case Unknown = 'unknown';
}