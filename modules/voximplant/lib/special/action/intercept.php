<?php

namespace Bitrix\Voximplant\Special\Action;

use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Voximplant\Limits;
use Bitrix\Voximplant\Special;
use Bitrix\Voximplant\Tts\Language;

class Intercept extends Special\Action
{
	const ACTION = 'INTERCEPT_CALL';

	public function checkPhoneNumber($phoneNumber)
	{
		return $phoneNumber === \CVoxImplantConfig::GetCombinationInterceptGroup();
	}

	public function getResponse($callId, $userId, $phoneNumber)
	{
		$voiceLang = static::getLang();
		$voice = Language::getDefaultVoice($voiceLang);

		$result = array(
			'ACTION' => self::ACTION,
			'USER_ID' => $userId,
			'PORTAL_SIGN' => \CVoxImplantHttp::GetPortalSign(),
			'PORTAL_URL' => \CVoxImplantHttp::GetPortalUrl(),
			'FOUND' => 'N',
			'VOICE' => $voice
		);

		$callToIntercept = \CVoxImplantIncoming::findCallToIntercept($userId);
		if(!Limits::canInterceptCall())
		{
			$result['TEXT'] = Loc::getMessage("VOX_ACTION_INTERCEPT_LICENSE_ERROR", null, $voiceLang);
		}
		else if($callToIntercept)
		{
			$result['FOUND'] = 'Y';
			$result['CALL_ID'] = $callToIntercept;
			$result['TEXT'] = Loc::getMessage("VOX_ACTION_INTERCEPT_HANGUP_TO_ACCEPT_CALL", null, $voiceLang);
		}
		else
		{
			$result['TEXT'] = Loc::getMessage("VOX_ACTION_INTERCEPT_CALL_NOT_FOUND", null, $voiceLang);
		}

		return $result;
	}

	protected static function getLang()
	{
		$siteLang = Context::getCurrent()->getLanguage();

		if($siteLang === 'ru' || $siteLang === 'ua' || $siteLang === 'kz')
			return 'ru';
		else if ($siteLang === 'de')
			return 'de';
		else
			return 'en';
	}
}