<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\ImConnector\InteractiveMessage\Output;
use Bitrix\ImOpenLines\Im;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Web\HttpClient;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

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

	if ($getRequest['location'] === 'send')
	{
		$appParams = [
			'teamId' => '5B3T3A994N',
			'bundleId' => 'com.example.apple-samplecode.PackageDelivery5B3T3A994N.MessagesExtension',
			'addId' => '123456789',
			'appName' => 'Package Delivery',
			'useLiveLayout' => true,
			'urlData' => '?name=Bitrix24%20Goodies&deliveryDate=04-04-2020&destinationName=Bitrix24&street=3%20Gostinaya%20Street&state=Kaliningrad%20region&city=Kaliningrad&country=Russia&postalCode=236022&latitude=54%2E7191&longitude=20%2E4887&extraCharge=15%2E00&isFinalDestination=true',
			'receivedMessage' => [
				'subtitle' => 'Location of Package',
				'title' => 'Package Delivery'
			]
		];

		Output::getInstance($chatId)->setAppParams($appParams);

		$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::CHAT);
		$attach->AddMessage('Bitrix24, Gostinaya 3, Kalininrad, Russia');

		$fieldsMessage['MESSAGE'] = Loc::getMessage('IMOL_IMESSAGE_LOCATION_MESSAGE_SENT');
		$fieldsMessage['ATTACH'] = $attach;
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
			<p><?=Loc::getMessage('IMOL_IMESSAGE_FORM_TEXT')?></p>
			<button class="ui-btn ui-btn-success ui-btn-sm" type="submit" value="send" name="location">
				<?=Loc::getMessage('IMOL_IMESSAGE_CUSTOM_APP_BUTTON_SEND')?>
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

CMain::FinalActions();
die();