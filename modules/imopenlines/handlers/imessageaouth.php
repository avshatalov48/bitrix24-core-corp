<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\ImConnector\InteractiveMessage\Output;
use Bitrix\ImOpenLines\Im;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Web\HttpClient;

//\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
\Bitrix\Main\Localization\Loc::loadMessages(__DIR__.'/imessage.php');

if (is_object($APPLICATION))
{
	$APPLICATION->RestartBuffer();
}

if (!Loader::includeModule('imopenlines'))
{
	CMain::FinalActions();
	die();
}

$error = '';
$getRequest = Context::getCurrent()->getRequest()->toArray();
$check = parse_url($getRequest['DOMAIN']);
if(!in_array($check['scheme'], Array('http', 'https')) || empty($check['host']))
{
	$style = "
	    font-family: 'Helvetica Neue', Helvetica, sans-serif;
	    font-size: 14px;
		padding: 10 12px;
    	display: block;
    	background-color: #e8f7fe;
    	border: 1px solid #e8f7fe;
    	border-radius: 4px;
   	";

	echo '<span style="'.$style.'">'.\Bitrix\Main\Localization\Loc::getMessage('IMOP_QUICK_IFRAME_ERROR_ADDRESS').'</span>';
	die();
}
$params = array();

$params['DOMAIN'] = $check['scheme'].'://'.$check['host'];
$params['SERVER_NAME'] = $check['host'];

if(!isset($_SERVER['HTTP_REFERER']) || empty($_SERVER['HTTP_REFERER']) || mb_strpos($_SERVER['HTTP_REFERER'], $params['DOMAIN']) !== 0)
{
	$style = "
	    font-family: 'Helvetica Neue', Helvetica, sans-serif;
	    font-size: 14px;
		padding: 10 12px;
    	display: block;
    	background-color: #e8f7fe;
    	border: 1px solid #e8f7fe;
    	border-radius: 4px;
   	";

	echo '<span style="'.$style.'">'.\Bitrix\Main\Localization\Loc::getMessage('IMOP_QUICK_IFRAME_ERROR_SECURITY').'</span>';
	die();
}

if(!isset($_SERVER['HTTP_REFERER']) || empty($_SERVER['HTTP_REFERER']) || mb_strpos($_SERVER['HTTP_REFERER'], $params['DOMAIN']) !== 0)
{
	$style = "
	    font-family: 'Helvetica Neue', Helvetica, sans-serif;
	    font-size: 14px;
		padding: 10 12px;
    	display: block;
    	background-color: #e8f7fe;
    	border: 1px solid #e8f7fe;
    	border-radius: 4px;
   	";

	echo '<span style="'.$style.'">'.\Bitrix\Main\Localization\Loc::getMessage('IMOP_QUICK_IFRAME_ERROR_SECURITY').'</span>';
	die();
}

if (
	$getRequest['DIALOG_ID'] &&
	$request->isPost() &&
	check_bitrix_sessid() &&
	Loader::includeModule('imconnector')
)
{
	Loader::includeModule('ui');
	\Bitrix\Main\UI\Extension::load("ui.buttons");

	if (mb_strpos($getRequest['DIALOG_ID'], 'chat') === 0)
	{
		$chatId = (int)mb_substr($getRequest['DIALOG_ID'], 4);
	}

	$fieldsMessage = [
		'DIALOG_ID' => $getRequest['DIALOG_ID'],
		'AUTHOR_ID' => $getRequest['USER_ID'],
		'FROM_USER_ID' => $getRequest['USER_ID'],
	];

	if ($getRequest['oauth'] === 'send')
	{
		$oauth = new \ImessageOuath();
		$publicKey = $oauth->getPublicKey();
		$state = $oauth->generateNonce();

		$oauthParams = [
			'appId' => '205555657495740',
			'clientSecret' => '30c4e35894322a6e42a4c16bef202713', //appSecret
			'responseEncryptionKey' => $publicKey,
			'state' => $state,
			'responseType' => 'token',
			'scope' => ['email'],
			'receivedMessage' =>
				[
					'title' => 'Sign in to Facebook',
				],
			'replyMessage' =>
				[
					'title' => 'You signed in',
				]
		];
		Output::getInstance($chatId)->setOauthParams($oauthParams);
		$fieldsMessage['MESSAGE'] = Loc::getMessage('IMOL_IMESSAGE_OAUTH_FACEBOOK_REQUEST_MESSAGE_SENT');
	}

	if (!empty($fieldsMessage['MESSAGE']))
	{
		$messageId = Im::addMessage($fieldsMessage);

		if ($messageId > 0)
		{
			echo Loc::getMessage('IMOL_IMESSAGE_SUCCESS');
		}
		else
		{
			echo Loc::getMessage('IMOL_IMESSAGE_FAIL');
		}
	}

}

if (!$request->isPost())
{
	?>
	<form method="POST" action=<?= POST_FORM_ACTION_URI ?>>
		<?= bitrix_sessid_post() ?>
		<div id="imessage-form">
			<p><?=Loc::getMessage('IMOL_IMESSAGE_OAUTH_FACEBOOK_REQUEST')?></p>
			<button class="ui-btn ui-btn-success ui-btn-sm" type="submit" value="send" name="oauth">
				<?=Loc::getMessage('IMOL_IMESSAGE_OAUTH_FACEBOOK_BUTTON_SEND')?>
			</button>
		</div>
	</form>

	<script>
		document.getElementById('imessage-form').onclick = function () {
			document.getElementById('imessage-form').hidden = true;
		};

	</script>

	<?php
}

class ImessageOuath
{
	//oauth service
	protected $decryptBaseUrl = 'http://ec2-54-246-81-158.eu-west-1.compute.amazonaws.com:9999';
	protected $pairKeysEndpoint = '/getkey';

	private const REQUEST_TIMEOUT = 5;

	private function getPublicKeyUrl(): string
	{
		return $this->decryptBaseUrl . $this->pairKeysEndpoint;
	}

	public function getPublicKey(): string
	{
		$result = '';
		$status = \Bitrix\ImConnector\Status::getInstance('imessage', (int)$this->getLineId());
		$statusData = $status->getData();
		if (empty($statusData[$this->getChatId()]))
		{
			$http = new HttpClient();
			$http->setStreamTimeout(self::REQUEST_TIMEOUT);
			$url = $this->getPublicKeyUrl();

			$rawData = $http->get($url);
			$responseStatus = $http->getStatus();

			if ($responseStatus === 200)
			{
				$result = $rawData;
				$data[$this->getChatId()] = $result;
				$status->setData($data);
			}
		}
		else
		{
			$result = $statusData[$this->getChatId()];
		}

		return $result;
	}

	/**
	 * @return int
	 */
	private function getLineId()
	{
		$getRequest = Context::getCurrent()->getRequest()->toArray();
		$dialogEntityId = explode('|', $getRequest['DIALOG_ENTITY_ID']);

		return (int)$dialogEntityId[1];
	}

	private function getChatId()
	{
		$getRequest = Context::getCurrent()->getRequest()->toArray();
		return $getRequest['DIALOG_ID'];
	}

	public function generateNonce(): string
	{
		return Random::getString(32);
	}
}

CMain::FinalActions();
die();