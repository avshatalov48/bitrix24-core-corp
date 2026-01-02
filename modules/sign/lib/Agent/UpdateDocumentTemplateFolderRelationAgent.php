<?php

namespace Bitrix\Sign\Agent;

use CDatabase;

/** @var CDatabase $DB */
final class UpdateDocumentTemplateFolderRelationAgent
{
	public static function run(): string
	{
		global $DB;
		if (
			$DB->TableExists('b_sign_document_template') &&
			$DB->TableExists('b_sign_document_template_folder_relation')
		)
		{
			$sql = "
			INSERT INTO b_sign_document_template_folder_relation (ENTITY_ID, PARENT_ID, ENTITY_TYPE, DEPTH_LEVEL, CREATED_BY_ID)
			SELECT ID, 0, 'template', 0, CREATED_BY_ID
			FROM b_sign_document_template
			WHERE NOT EXISTS (SELECT 1 FROM b_sign_document_template_folder_relation);
			";

			$DB->Query($sql, true);
		}

		return '';
	}
}