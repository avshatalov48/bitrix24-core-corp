<?php

namespace Bitrix\Crm\Integration\Analytics;

final class Dictionary
{
	public const TOOL_CRM = 'crm';
	public const TOOL_AI = 'AI';

	public const CATEGORY_ENTITY_OPERATIONS = 'entity_operations';
	public const CATEGORY_CRM_OPERATIONS = 'crm_operations';

	// region Event const
	public const EVENT_ENTITY_ADD_OPEN = 'entity_add_open';
	public const EVENT_ENTITY_ADD = 'entity_add';
	public const EVENT_ENTITY_UPDATE = 'entity_update';
	public const EVENT_ENTITY_COPY_OPEN = 'entity_copy_open';
	public const EVENT_ENTITY_COPY = 'entity_copy';
	public const EVENT_ENTITY_CONVERT = 'entity_convert';
	public const EVENT_ENTITY_CONVERT_BATCH = 'entity_convert_batch';
	public const EVENT_ENTITY_CONVERT_OPEN = 'entity_convert_open';

	public const EVENT_CALL_PARSING = 'call_parsing';
	public const EVENT_AUDIO_TO_TEXT = 'audio_to_text';
	public const EVENT_SUMMARY = 'summary';
	public const EVENT_EXTRACT_FIELDS = 'extract_fields';
	// endregion

	// region Type const
	public const TYPE_MANUAL = 'manual';
	public const TYPE_AUTO = 'auto';
	// endregion

	// region Section const
	public const SECTION_CRM = 'crm';
	public const SECTION_LEAD = 'lead_section';
	public const SECTION_DEAL = 'deal_section';
	public const SECTION_CONTACT = 'contact_section';
	public const SECTION_COMPANY = 'company_section';
	public const SECTION_MYCOMPANY = 'my_company_section';
	public const SECTION_QUOTE = 'quote_section';
	public const SECTION_SMART_INVOICE = 'smart_invoice_section';
	public const SECTION_DYNAMIC = 'dynamic_section';
	public const SECTION_CUSTOM = 'custom_section';
	/**
	 * @see \Bitrix\Crm\Service\Factory\SmartDocument::CONTACT_CATEGORY_CODE
	 */
	public const SECTION_SMART_DOCUMENT_CONTACT = 'smart_document_contact_section';
	/**
	 * @see \Bitrix\Crm\Integration\Catalog\Contractor\CategoryRepository::CONTACT_CODE
	 */
	public const SECTION_CATALOG_CONTRACTOR_CONTACT = 'catalog_contractor_contact_section';
	/**
	 * @see \Bitrix\Crm\Integration\Catalog\Contractor\CategoryRepository::COMPANY_CODE
	 */
	public const SECTION_CATALOG_CONTRACTOR_COMPANY = 'catalog_contractor_company_section';
	// endregion

	// region Sub Section const
	public const SUB_SECTION_LIST = 'list';
	public const SUB_SECTION_KANBAN = 'kanban';
	public const SUB_SECTION_ACTIVITIES = 'activities';
	public const SUB_SECTION_CALENDAR = 'calendar';
	public const SUB_SECTION_DEADLINES = 'deadlines';
	public const SUB_SECTION_DETAILS = 'details';

	public const SUB_SECTION_DEAL = 'deal';
	public const SUB_SECTION_LEAD = 'lead';
	// endregion

	// region Element const
	public const ELEMENT_CREATE_BUTTON = 'create_button';
	public const ELEMENT_CONTROL_PANEL_CREATE_BUTTON = 'control_panel_create_button';
	public const ELEMENT_QUICK_BUTTON = 'quick_button';
	public const ELEMENT_SETTINGS_BUTTON = 'settings_button';
	public const ELEMENT_GRID_ROW_CONTEXT_MENU = 'grid_row_context_menu';
	public const ELEMENT_GRID_GROUP_ACTIONS = 'grid_group_actions';
	public const ELEMENT_CONVERT_BUTTON = 'convert_button';
	public const ELEMENT_TERMINATION_CONTROL = 'termination_control';
	public const ELEMENT_CREATE_LINKED_ENTITY_BUTTON = 'create_linked_entity_button';
	public const ELEMENT_DRAG_N_DROP = 'drag_n_drop';
	public const ELEMENT_FILL_REQUIRED_FIELDS_POPUP = 'fill_required_fields_popup';
	public const ELEMENT_CRM_MODE_CHANGE_POPUP = 'crm_mode_change_popup';
	public const ELEMENT_COPILOT_BUTTON = 'copilot_button';
	public const ELEMENT_FEEDBACK_SEND = 'feedback_send';
	public const ELEMENT_FEEDBACK_REFUSED = 'feedback_refused';
	public const ELEMENT_CONFLICT_ACCEPT_CHANGES = 'conflict_accept_changes';
	public const ELEMENT_CONFLICT_CANCEL_CHANGES = 'conflict_cancel_changes';
	// endregion

	// region Status const
	public const STATUS_ATTEMPT = 'attempt';
	public const STATUS_SUCCESS = 'success';
	public const STATUS_ERROR = 'error';
	public const STATUS_CANCEL = 'cancel';

	public const STATUS_SUCCESS_FIELDS = 'success_fields';
	public const STATUS_SUCCESS_COMMENT = 'success_comment_only';
	public const STATUS_ERROR_NO_LIMITS = 'error_no_limits';
	public const STATUS_ERROR_GPT = 'error_gpt';
	public const STATUS_ERROR_B24 = 'error_b24';
	// endregion

	private function __construct()
	{
	}

	public static function getAnalyticsEntityType(int|string $entityType): ?string
	{
		$entityTypeId = $entityType;
		if (is_string($entityType))
		{
			$entityTypeId = \CCrmOwnerType::ResolveID($entityType);
		}

		if (!\CCrmOwnerType::IsDefined($entityTypeId))
		{
			return null;
		}

		return mb_strtolower(
			\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId)
				? \CCrmOwnerType::CommonDynamicName
				: \CCrmOwnerType::ResolveName($entityTypeId)
		);
	}

	public static function getCrmMode(): string
	{
		return 'crmMode_' . mb_strtolower(\Bitrix\Crm\Settings\Mode::getCurrentName());
	}
}
