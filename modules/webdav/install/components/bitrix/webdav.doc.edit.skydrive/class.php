<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!CModule::includeModule('webdav'))
{
	return;
}

class CWebDavDocEditSkydriveComponent extends CWebDavEditDocComponentBase
{
	/**
	 * @return string
	 */
	protected function getServiceName()
	{
		return CWebDavLogOnlineEdit::SKYDRIVE_SERVICE_NAME;
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
		$socGoogleOAuth = new CSocServLiveIDOAuth($this->getUser()->getId());
		//this bug. SocServ fill entityOAuth in method getUrl.....
		$liveIdOAuthUrl = $socGoogleOAuth->getUrl('modal', CWebDavEditSkyDrive::$SCOPE);
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
		$socGoogleOAuth = new CSocServLiveIDOAuth($userId);
		//this bug. SocServ fill entityOAuth in method getUrl.....
		$googleOAuthUrl = $socGoogleOAuth->getUrl('modal', CWebDavEditDocGoogle::$SCOPE);
		$accessToken = $socGoogleOAuth->getStorageToken();

		return $accessToken;
	}

	protected function getOAuthUrlBySocServ()
	{
		$socGoogleOAuth = new CSocServLiveIDOAuth($this->getUser()->getId());
		$liveIdOAuthUrl = $socGoogleOAuth->getUrl('modal', CWebDavEditSkyDrive::$SCOPE);

		return $liveIdOAuthUrl;
	}

	protected function checkActiveSocServ()
	{
		$oAuthManager = new CSocServAuthManager();
		$socNetServices = $oAuthManager->GetActiveAuthServices(array());//check active google oauth service
		if(empty($socNetServices['LiveIDOAuth']))
		{
			$this->sendJsonResponse(array('error' => GetMessage('WD_DOC_INSTALL_SOCSERV_SKYDRIVE')));
		}
	}

	/**
	 * @return $this
	 */
	protected function initDocHandler()
	{
		return $this->setDocHandler(new CWebDavEditSkyDrive());
	}

	public function executeComponent()
	{
		return parent::executeComponent();
	}
}