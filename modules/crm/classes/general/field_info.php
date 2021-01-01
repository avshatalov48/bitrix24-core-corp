<?php
class CCrmFieldInfoAttr
{
	public const Undefined = '';
	public const Hidden = 'HID';
	public const ReadOnly = 'R-O';
	public const Immutable = 'IM'; //User can define field value only on create
	public const UserPKey = 'UPK'; //User defined primary key (currency alpha code for example)
	public const Required = 'REQ';
	public const Multiple = 'MUL';
	public const Dynamic = 'DYN';
	public const Deprecated = 'DEP';
	public const Progress = 'PROG'; //It is progress field (for example: STAGE_ID in Deal)
	public const HasDefaultValue = 'HAS_DEFAULT_VALUE';
}

