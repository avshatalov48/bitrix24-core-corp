<?
require_once __DIR__.'/autoload.php';

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