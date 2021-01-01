<?

require_once __DIR__.'/autoload.php';

CJSCore::RegisterExt('voximplant', array(
	'js' => array(
		'/bitrix/js/voximplant/client.js',
	),
	'rel' => ['ls', 'webrtc_adapter'],
	'oninit' => function()
	{
		global $USER;

		return array(
			'lang_additional' => array(
				'voximplantDefaultLineId' => CVoxImplantUser::getUserOutgoingLine($USER->getId()),
				'voximplantSdkUrl' => \CUtil::GetAdditionalFileURL("/bitrix/js/voximplant/voximplant.min.js"),
				'voximplantCanMakeCalls' => \Bitrix\Voximplant\Limits::canCall() ? 'Y' : 'N',
			)
		);
	}
));

CJSCore::RegisterExt('voximplant_transcript', array(
	'js' => '/bitrix/js/voximplant/transcript.js',
	'lang' => '/bitrix/modules/voximplant/lang/'.LANGUAGE_ID.'/install/js/transcript.php',
));
?>