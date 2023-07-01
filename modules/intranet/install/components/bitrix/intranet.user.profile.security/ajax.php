<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Bitrix24\Sso;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\Response;

class CIntranetUserProfileSecurityComponentAjaxController extends \Bitrix\Main\Engine\Controller
{
	protected $userId;
	protected $pathToUserCodes;

	protected function processBeforeAction(\Bitrix\Main\Engine\Action $action)
	{
		parent::processBeforeAction($action);

		if (!$this->getRequest()->isPost() || !$this->getRequest()->getPost('signedParameters'))
		{
			return false;
		}

		$parameters = $this->getUnsignedParameters();

		if (isset($parameters['USER_ID']))
		{
			$this->userId = $parameters['USER_ID'];
		}

		if (isset($parameters['PATH_TO_USER_CODES']))
		{
			$this->pathToUserCodes = CComponentEngine::MakePathFromTemplate($parameters["PATH_TO_USER_CODES"], array("user_id" => $this->userId));
		}

		return true;
	}

	public function appPasswordsAction()
	{
		$additionalParams = [
			'pageTitle' => Loc::getMessage("INTRANET_USER_PROFILE_SECURITY_PASSWORDS_TITLE"),
			//'pageControlsParams' => $analyticBoard->getButtonsContent()
		];

		$componentName = 'bitrix:main.app.passwords';
		$params = [];

		return new \Bitrix\Main\Engine\Response\Component($componentName, '', $params, $additionalParams);
	}

	public function otpAction()
	{
		$additionalParams = [
			'pageTitle' => Loc::getMessage("INTRANET_USER_PROFILE_SECURITY_OTP_TITLE"),
		];
		$componentName = 'bitrix:security.user.otp.init';

		$params = [
			"REDIRECT_AFTER_CONNECTION" => "N"
		];

		return new \Bitrix\Main\Engine\Response\Component($componentName, '', $params, $additionalParams);
	}

	public function otpConnectedAction($userId)
	{
		$additionalParams = [
			'pageTitle' => Loc::getMessage("INTRANET_USER_PROFILE_SECURITY_OTP_TITLE"),
		];
		$componentName = 'bitrix:intranet.user.otp.connected';

		$params = [
			"USER_ID" => $userId
		];

		return new \Bitrix\Main\Engine\Response\Component($componentName, '', $params, $additionalParams);
	}

	public function recoveryCodesAction($componentMode = "")
	{
		$additionalParams = [
			'pageTitle' => Loc::getMessage("INTRANET_USER_PROFILE_RECOVERY_CODES_TITLE"),
		];
		$componentName = 'bitrix:security.user.recovery.codes';

		$params = [
			"MODE" => $componentMode,
			"PATH_TO_CODES" => $this->pathToUserCodes
		];

		return new \Bitrix\Main\Engine\Response\Component($componentName, '', $params, $additionalParams);
	}

	public function synchronizeAction()
	{
		$additionalParams = [
			'pageTitle' => Loc::getMessage("INTRANET_USER_PROFILE_SYNCHRONIZE_TITLE"),
		];
		$componentName = 'bitrix:dav.synchronize_settings';

		$params = [
			"COMPONENT_AJAX_LOAD" => "Y"
		];

		return new \Bitrix\Main\Engine\Response\Component($componentName, '', $params, $additionalParams);
	}

	public function socnetEmailAction($userId)
	{
		$additionalParams = [
			'pageTitle' => Loc::getMessage("INTRANET_USER_PROFILE_SOCNET_EMAIL_TITLE"),
		];
		$componentName = 'bitrix:intranet.socnet.email.settings';

		$params = [
			"USER_ID" => $userId
		];

		return new \Bitrix\Main\Engine\Response\Component($componentName, '', $params, $additionalParams);
	}

	public function authAction($userId)
	{
		if (ModuleManager::isModuleInstalled("bitrix24"))
		{
			$additionalParams = [
				'pageTitle' => Loc::getMessage("INTRANET_USER_PROFILE_AUTH_TITLE_2"),
			];
			$componentName = 'bitrix:bitrix24.user.network_profile';
			$templateName = '';

			$params = [
				"USER_ID" => $userId,
			];

			return new \Bitrix\Main\Engine\Response\Component($componentName, $templateName, $params, $additionalParams);
		}
		else
		{
			$additionalParams = [
				'pageTitle' => Loc::getMessage("INTRANET_USER_PROFILE_AUTH_TITLE_2"),
			];
			$componentName = 'bitrix:intranet.user.profile.password';

			$params = [
				"USER_ID" => $userId
			];

			return new \Bitrix\Main\Engine\Response\Component($componentName, '', $params, $additionalParams);
		}
	}

	public function mailingAgreementAction()
	{
		$additionalParams = [
			'pageTitle' => Loc::getMessage("INTRANET_USER_PROFILE_MAILING_TITLE"),
		];
		$componentName = 'bitrix:bitrix24.mailing.agreement';

		$params = [];

		return new \Bitrix\Main\Engine\Response\Component($componentName, '', $params, $additionalParams);
	}

	public function ssoAction(): Response\AjaxJson
	{
		Loader::requireModule('bitrix24');
		//todo remove after release class_exists and add dependece
		if (!class_exists(Sso\Configuration::class))
		{
			return Response\AjaxJson::createDenied();
		}

		if (!Sso\Configuration::isSsoAvailable() || Sso\Configuration::isSsoLocked())
		{
			return Response\AjaxJson::createDenied();
		}

		$componentName = 'bitrix:bitrix24.sso.wizard';
		$params = [];
		$additionalParams = [
			'pageTitle' => Loc::getMessage('INTRANET_USER_PROFILE_SSO_TITLE'),
		];

		return new Response\Component(
			$componentName, '', $params, $additionalParams
		);
	}
}
