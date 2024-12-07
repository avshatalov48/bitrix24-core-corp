<?php

namespace Bitrix\Tasks\Flow\Template\Access\Permission;

use Bitrix\Tasks\Access\Permission\PermissionDictionary;
use Bitrix\Tasks\Flow\Attribute\AccessCodes;
use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Internals\Attribute\InArray;
use Bitrix\Tasks\Internals\Attribute\PositiveNumber;
use Bitrix\Tasks\Internals\Attribute\Template;

/**
 * @method self setTemplateId(int $templateId)
 * @method self setAccessCodes(array $accessCodes)
 * @method self setPermissionId(int $permissionId)
 * @method self setValue(int $value)
 */
class TemplatePermissionCommand extends AbstractCommand
{
	#[Template]
	#[PositiveNumber]
	public int $templateId;

	#[AccessCodes]
	public array $accessCodes = [];

	#[InArray([PermissionDictionary::TEMPLATE_VIEW, PermissionDictionary::TEMPLATE_FULL])]
	public int $permissionId;

	#[InArray([\Bitrix\Main\Access\Permission\PermissionDictionary::VALUE_NO, \Bitrix\Main\Access\Permission\PermissionDictionary::VALUE_YES])]
	public int $value;
}