<?php
namespace Bitrix\Crm\Integration\Socialnetwork\CommentAux;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class CreateTask
{
	const SOURCE_TYPE_CRM_LEAD = 'CRM_LOG_LEAD';
	const SOURCE_TYPE_CRM_CONTACT = 'CRM_LOG_CONTACT';
	const SOURCE_TYPE_CRM_COMPANY = 'CRM_LOG_COMPANY';
	const SOURCE_TYPE_CRM_DEAL = 'CRM_LOG_DEAL';
	const SOURCE_TYPE_CRM_INVOICE = 'CRM_INVOICE';
	const SOURCE_TYPE_CRM_ACTIVITY = 'CRM_ACTIVITY';
	const SOURCE_TYPE_CRM_ENTITY_COMMENT = 'CRM_ENTITY_COMMENT';

	public static function getPostTypeList()
	{
		return array(
			self::SOURCE_TYPE_CRM_LEAD,
			self::SOURCE_TYPE_CRM_CONTACT,
			self::SOURCE_TYPE_CRM_COMPANY,
			self::SOURCE_TYPE_CRM_DEAL,
			self::SOURCE_TYPE_CRM_INVOICE,
			self::SOURCE_TYPE_CRM_ACTIVITY
		);
	}

	public static function getCommentTypeList()
	{
		return array(
			self::SOURCE_TYPE_CRM_ENTITY_COMMENT
		);
	}
}