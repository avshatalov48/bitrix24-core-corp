<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!CModule::includeModule('webdav'))
{
	return;
}

class CWebDavDocEditGoogleComponent extends CWebDavEditDocComponentBase
{
	/**
	 * @return string
	 */
	protected function getServiceName()
	{
		return CWebDavLogOnlineEdit::GOOGLE_SERVICE_NAME;
	}

	/**
	 * @return string
	 */
	protected function generateUriToDoc()
	{
		return CUtil::JSEscape($this->getWebdav()->uri . '?' . 'editIn=' . $this->getServiceName() . '&proccess=1');
	}

	protected function getAccessTokenBySocServ()
	{
		$socGoogleOAuth = new CSocServGoogleOAuth($this->getUser()->getId());
		//this bug. SocServ fill entityOAuth in method getUrl.....
		$googleOAuthUrl = $socGoogleOAuth->getUrl('modal', CWebDavEditDocGoogle::$SCOPE);
		$accessToken = $socGoogleOAuth->getStorageToken();

		return $accessToken;
	}

	/**
	 * Get access token by another user (not current)
	 * @param $userId
	 * @return string
	 */
	protected function getAccessTokenByUserSocServ($userId)
	{
		$socGoogleOAuth = new CSocServGoogleOAuth($userId);
		//this bug. SocServ fill entityOAuth in method getUrl.....
		$googleOAuthUrl = $socGoogleOAuth->getUrl('modal', CWebDavEditDocGoogle::$SCOPE);
		$accessToken = $socGoogleOAuth->getStorageToken();

		return $accessToken;
	}

	protected function getOAuthUrlBySocServ()
	{
		$socGoogleOAuth = new CSocServGoogleOAuth($this->getUser()->getId());
		$googleOAuthUrl = $socGoogleOAuth->getUrl('modal', CWebDavEditDocGoogle::$SCOPE);

		return $googleOAuthUrl;
	}

	protected function checkActiveSocServ()
	{
		$oAuthManager = new CSocServAuthManager();
		$socNetServices = $oAuthManager->GetActiveAuthServices(array());//check active google oauth service
		if(empty($socNetServices['GoogleOAuth']))
		{
			$this->sendJsonResponse(array('error' => GetMessage('WD_DOC_INSTALL_SOCSERV_GOOGLE')));
		}
	}

	/**
	 * @return $this
	 */
	protected function initDocHandler()
	{
		return $this->setDocHandler(new CWebDavEditDocGoogle());
	}

	public function executeComponent()
	{
		return parent::executeComponent();
	}

}