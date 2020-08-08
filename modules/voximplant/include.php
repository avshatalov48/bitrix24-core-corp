<?

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

CJSCore::RegisterExt('voximplant', array(
	'js' => array(
		'/bitrix/js/voximplant/client.js',
	),
	'rel' => ['webrtc_adapter'],
	'oninit' => function()
	{
		global $USER;

		$voximplantAuthorization = (new CVoxImplantUser())->getAuthorizationInfo($USER->getId());
		if($voximplantAuthorization->isSuccess())
		{
			$voximplantAuthorizationData = $voximplantAuthorization->getData();
			$voximplantServer = $voximplantAuthorizationData['server'];
			$voximplantLogin = $voximplantAuthorizationData['login'];
		}
		else
		{
			$voximplantServer = '';
			$voximplantLogin = '';
		}

		return array(
			'lang_additional' => array(
				'voximplantServer' => $voximplantServer,
				'voximplantLogin' => $voximplantLogin,
				'voximplantLines' => CVoxImplantConfig::GetLines(true, true),
				'voximplantDefaultLineId' => CVoxImplantUser::getUserOutgoingLine($USER->getId()),
				'voximplantSdkUrl' => \CUtil::GetAdditionalFileURL("/bitrix/js/voximplant/voximplant.min.js")
			)
		);
	}
));

CJSCore::RegisterExt('voximplant_transcript', array(
	'js' => '/bitrix/js/voximplant/transcript.js',
	'lang' => '/bitrix/modules/voximplant/lang/'.LANGUAGE_ID.'/install/js/transcript.php',
));
?>