<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage timeman
 * @copyright 2001-2013 Bitrix
 */


IncludeModuleLangFile(__FILE__);

class CTimemanNotifySchema
{
	public function __construct()
	{
	}

	public static function OnGetNotifySchema()
	{
		return array(
			"timeman" => array(
				"entry" => Array(
					"NAME" => GetMessage("TIMEMAN_NS_ENTRY"),
				),
				"entry_comment" => Array(
					"NAME" => GetMessage("TIMEMAN_NS_ENTRY_COMMENT"),
				),
				"entry_approve" => Array(
					"NAME" => GetMessage("TIMEMAN_NS_ENTRY_APPROVE"),
				),
				"report" => Array(
					"NAME" => GetMessage("TIMEMAN_NS_REPORT"),
				),
				"report_comment" => Array(
					"NAME" => GetMessage("TIMEMAN_NS_REPORT_COMMENT"),
				),
				"report_approve" => Array(
					"NAME" => GetMessage("TIMEMAN_NS_REPORT_APPROVE"),
				),
			),
		);
	}
}