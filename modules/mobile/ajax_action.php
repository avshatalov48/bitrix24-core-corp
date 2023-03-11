<?php

$pathToComponents =  $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/";
$fileType = $_REQUEST["fileType"] ?? null;

$ajaxActions = [
	"checkout" => [
		"json" => true,
		"removeNulls" => true,
		"file" => $pathToComponents."mobile.data/actions/checkout.php",
		"no_check_auth" => true
	],
	"service" => [
		"json" => true,
		"file" => $pathToComponents."mobile.data/actions/service.php",
		"no_check_auth" => true
	],
	"list" => [
		"json" => true,
		"file" => $pathToComponents."mobile.data/actions/service.php",
		"no_check_auth" => true
	],
	"get_captcha" => [
		"json" => false,
		"file" => $pathToComponents."mobile.data/actions/captcha.php",
		"no_check_auth" => true
	],
	"save_device_token" => [
		"json" => true,
		"needBitrixSessid" => true,
		"file" => $pathToComponents."mobile.data/actions/save_device_token.php"
	],
	"removeToken" => [
		"json" => true,
		"needBitrixSessid" => false,
		"no_check_auth" => true,
		"file" => $pathToComponents."mobile.data/actions/save_device_token.php"
	],
	"get_element_crm_list" => [
		"json" => true,
		"file" => $pathToComponents."mobile.data/actions/crm_elements.php"
	],
	"get_user_list" => [
		"json" => true,
		"file" => $pathToComponents."mobile.data/actions/users_groups.php"
	],
	"get_group_list" => [
		"json" => true,
		"file" => $pathToComponents."mobile.data/actions/users_groups.php"
	],
	"get_usergroup_list" => [
		"json" => true,
		"file" => $pathToComponents."mobile.data/actions/users_groups.php"
	],
	'get_subordinated_user_list' => [
		"json" => true,
		"file" => $pathToComponents."mobile.data/actions/users_subordinates.php"
	],
	"get_likes" => [
		"json" => true,
		"file" => $pathToComponents."mobile.data/actions/get_likes.php"
	],
	"logout" => [
		"file" => ""
	],
	"calendar" => [
		"json" => true,
		"file" => $pathToComponents."mobile.data/actions/calendar.php"
	],
	"calendar_livefeed_view" => [
		"file" => $pathToComponents."calendar.livefeed.view/action.php"
	],
	"like" => [
		"file" => $pathToComponents."rating.vote/vote.ajax.php"
	],
	"pull" => [
		"file" => $pathToComponents."pull.request/ajax.php",
	],
	"im" => [
		"file" => $pathToComponents."im.messenger/im.ajax.php",
	],
	"im_files" => [
		"file" => $pathToComponents."im.messenger/" . ($fileType == 'show' ? 'show.file.php' : 'download.file.php'),
		"json" => false
	],
	"im_answer" => [
		"json" => true,
		"file" => $pathToComponents."mobile.data/actions/im_answer.php"
	],
	"calls" => [
		"file" => $pathToComponents."im.messenger/im.ajax.php",
	],
	"task_ajax" => [
		"file" => $pathToComponents."tasks.base/ajax.php",
	],
	"bitrix24_ajax" => [
		"file" => $pathToComponents."bitrix24.business.tools.info/templates/.default/ajax.php",
	],
	"task_answer" => [
		"json" => true,
		"file" => $pathToComponents."mobile.data/actions/task_answer.php",
	],
	"change_follow" => [
		"file" => $pathToComponents."mobile.socialnetwork.log.ex/ajax.php"
	],
	"change_follow_default" => [
		"file" => $pathToComponents."mobile.socialnetwork.log.ex/ajax.php"
	],
	"change_expert_mode" => [
		"file" => $pathToComponents."mobile.socialnetwork.log.ex/ajax.php"
	],
	"change_favorites" => [
		"file" => $pathToComponents."mobile.socialnetwork.log.ex/ajax.php"
	],
	"log_error" => [
		"file" => $pathToComponents."mobile.socialnetwork.log.ex/ajax.php"
	],
	"get_more_destination" => [
		"file" => $pathToComponents."mobile.socialnetwork.log.entry/ajax.php"
	],
	"add_comment" => [
		"file" => $pathToComponents."mobile.socialnetwork.log.entry/ajax.php"
	],
	"edit_comment" => [
		"file" => $pathToComponents."mobile.socialnetwork.log.entry/ajax.php"
	],
	"delete_comment" => [
		"file" => $pathToComponents."mobile.socialnetwork.log.entry/ajax.php"
	],
	"get_comment" => [
		"file" => $pathToComponents."mobile.socialnetwork.log.entry/ajax.php"
	],
	"get_comments" => [
		"file" => $pathToComponents."mobile.socialnetwork.log.entry/ajax.php"
	],
	"delete_post" => [
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/templates/mobile_app/components/bitrix/socialnetwork.blog.post/mobile/ajax.php"
	],
	"read_post" => [
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/templates/mobile_app/components/bitrix/socialnetwork.blog.post/mobile/ajax.php"
	],
	"get_blog_post_data" => [
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/templates/mobile_app/components/bitrix/socialnetwork.blog.post/mobile/ajax.php"
	],
	"get_blog_comment_data" => [
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/templates/mobile_app/components/bitrix/socialnetwork.blog.post/mobile/ajax.php"
	],
	"get_log_comment_data" => [
		"file" => $pathToComponents."mobile.socialnetwork.log.entry/ajax.php"
	],
	"comment_activity" => [
		"file" => $pathToComponents."main.post.list/activity.php"
	],
	"mobile_grid_sort" => [
		"file" => $pathToComponents."mobile.interface.sort/ajax.php"
	],
	"mobile_grid_fields" => [
		"file" => $pathToComponents."mobile.interface.fields/ajax.php"
	],
	"mobile_grid_filter" => [
		"file" => $pathToComponents."mobile.interface.filter/ajax.php"
	],
	"crm_activity_edit" => [
		"file" => $pathToComponents."mobile.crm.activity.edit/ajax.php"
	],
	"crm_contact_edit" => [
		"file" => $pathToComponents."mobile.crm.contact.edit/ajax.php"
	],
	"crm_lead_edit" => [
		"file" => $pathToComponents."mobile.crm.lead.edit/ajax.php"
	],
	"crm_product_row_edit" => [
		"file" => $pathToComponents."mobile.crm.product_row.edit/ajax.php"
	],
	"crm_company_edit" => [
		"file" => $pathToComponents."mobile.crm.company.edit/ajax.php"
	],
	"crm_config_user_email" => [
		"file" => $pathToComponents."mobile.crm.config.user_email/ajax.php"
	],
	"crm_deal_edit" => [
		"file" => $pathToComponents."mobile.crm.deal.edit/ajax.php"
	],
	"crm_deal_list" => [
		"file" => $pathToComponents."mobile.crm.deal.list/ajax.php"
	],
	"crm_invoice_edit" => [
		"file" => $pathToComponents."mobile.crm.invoice.edit/ajax.php"
	],
	"crm_location_list" => [
		"file" => $pathToComponents."mobile.crm.location.list/ajax.php"
	],
	"crm_product_list" => [
		"file" => $pathToComponents."mobile.crm.product.list/ajax.php"
	],
	"disk_folder_list" => [
		"json" => true,
		"file" => $pathToComponents."mobile.data/actions/disk_folder_list.php",
	],
	"disk_uf_view" => [
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/tools/disk/uf.php",
	],
	"disk_download_file" => [
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/services/disk/index.php",
	],
	"blog_image" => [
		"json" => false,
		"file" => $pathToComponents."blog/show_file.php"
	],
	"calendar_livefeed" => [
		"json" => false,
		"file" => $pathToComponents."calendar.livefeed.view/action.php"
	],
	"file_upload_log" => [
		"file" => $pathToComponents."mobile.socialnetwork.log.entry/ajax.php"
	],
	"file_upload_blog" => [
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/templates/mobile_app/components/bitrix/socialnetwork.blog.post/mobile/ajax.php"
	],
	"send_comment_writing" => [
		"file" => $pathToComponents."mobile.socialnetwork.log.entry/ajax.php"
	],
	"bp_make_action" => [
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/templates/mobile_app/components/bitrix/bizproc.task/mobile/ajax.php"
	],
	"bp_livefeed_action" => [
		"file" => $pathToComponents."bizproc.workflow.livefeed/ajax.php"
	],
	"bp_do_task" => [
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/tools/bizproc_do_task_ajax.php"
	],
	"bp_show_file" => [
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/tools/bizproc_show_file.php"
	],
	"mobile_crm_lead_list" => [
		"file" => $pathToComponents."mobile.crm.lead.list/ajax.php"
	],
	"mobile_crm_lead_actions" => [
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/tools/mobile_crm_lead.php"
	],
	"mobile_crm_deal_list" => [
		"file" => $pathToComponents."mobile.crm.deal.list/ajax.php"
	],
	"mobile_crm_deal_actions" => [
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/tools/mobile_crm_deal.php"
	],
	"mobile_crm_invoice_list" => [
		"file" => $pathToComponents."mobile.crm.invoice.list/ajax.php"
	],
	"mobile_crm_invoice_actions" => [
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/tools/mobile_crm_invoice.php"
	],
	"mobile_crm_contact_list" => [
		"file" => $pathToComponents."mobile.crm.contact.list/ajax.php"
	],
	"mobile_crm_contact_actions" => [
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/tools/mobile_crm_contact.php"
	],
	"mobile_crm_company_list" => [
		"file" => $pathToComponents."mobile.crm.company.list/ajax.php"
	],
	"mobile_crm_company_actions" => [
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/tools/mobile_crm_company.php"
	],
	"mobile_crm_quote_list" => [
		"file" => $pathToComponents."mobile.crm.quote.list/ajax.php"
	],
	"mobile_crm_quote_actions" => [
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/tools/mobile_crm_quote.php"
	],
	"mobile_crm_product_actions" => [
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/tools/mobile_crm_product.php"
	],
	"get_raw_data" => [
		"file" => $pathToComponents."socialnetwork.log.ex/ajax.php"
	],
	"create_task_comment" => [
		"file" => $pathToComponents."socialnetwork.log.ex/ajax.php"
	],
	"timeman" => [
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/tools/timeman.php"
	],
	"bizcard" => [
		"file" => $pathToComponents."mobile.data/actions/bizcard.php",
		"json" => false
	],
	"vote" => [
		"file" => $_SERVER["DOCUMENT_ROOT"] . '/bitrix/tools/vote/uf.php'
	],
	"set_content_view" => [
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/tools/sonet_set_content_view.php"
	],
];

return $ajaxActions;