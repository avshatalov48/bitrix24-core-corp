<?php

CModule::AddAutoloadClasses(
	"voximplant",
	array(
		"CVoxImplantMain" => "classes/general/vi_main.php",
		"CVoxImplantUser" => "classes/general/vi_user.php",
		"CVoxImplantAccount" => "classes/general/vi_account.php",
		"CVoxImplantOutgoing" => "classes/general/vi_outgoing.php",
		"CVoxImplantIncoming" => "classes/general/vi_incoming.php",
		"CVoxImplantPhone" => "classes/general/vi_phone.php",
		"CVoxImplantPhoneOrder" => "classes/general/vi_phone_order.php",
		"CVoxImplantDocuments" => "classes/general/vi_documents.php",
		"CVoxImplantHistory" => "classes/general/vi_history.php",
		"CVoxImplantEvent" => "classes/general/vi_event.php",
		"CVoxImplantHttp" => "classes/general/vi_http.php",
		"CVoxImplantError" => "classes/general/vi_error.php",
		"CVoxImplantCrmHelper" => "classes/general/vi_crm_helper.php",
		"CVoxImplantConfig" => "classes/general/vi_config.php",
		"CVoxImplantSip" => "classes/general/vi_sip.php",
		"CVoxImplantDiskHelper" => "classes/general/vi_webdav_helper.php",
		"CVoxImplantWebDavHelper" => "classes/general/vi_webdav_helper.php",
		"CVoxImplantTableSchema" => "classes/general/vi_table_schema.php",
		"CVoxImplantRestService" => "classes/general/vi_rest.php",
	)
);