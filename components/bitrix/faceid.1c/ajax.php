<?php

define("NOT_CHECK_FILE_PERMISSIONS", true);
define("PUBLIC_AJAX_MODE", true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('faceid');

use Bitrix\Main\Localization\Loc;

$contragents = array();
$errorResponse = array();

// check auth
$oauthToken = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get('auth');
$app = \Bitrix\Rest\AppTable::getList(array('filter' => array('=CODE' => 'bitrix.1c'), 'select' => array('CLIENT_ID')))->fetch();

$authResult = array();
$foundToken = \CRestUtil::checkAuth(array('access_token' => $oauthToken), 'crm', $authResult);

if ($foundToken && $app['CLIENT_ID'] == $authResult['client_id'] && $authResult['user_id'] > 0)
{
	if (\Bitrix\Faceid\AgreementTable::checkUser($authResult['user_id']))
	{
		// ok
	}
	else
	{
		$errorResponse = array('msg' => Loc::getMessage('FACEID_TRACKER1C_CMP_AJAX_NO_AGREEMENT'));
	}
}
else
{
	$errorResponse = array('msg' => Loc::getMessage("FACEID_TRACKER1C_CMP_AJAX_NO_AUTH"));
}

// continue with image
$imageContent = str_replace('data:image/jpeg', 'data://image/jpeg', $_POST['image']);
$fileContent = base64_decode(str_replace('data://image/jpeg;base64,', '', $imageContent));

if (empty($errorResponse) && !empty($_POST['action']))
{
	if ($_POST['action'] == 'identify')
	{
		$response = \Bitrix\FaceId\FaceId::identify($fileContent, '1c');
		$result = $response['result'];

		if (!empty($response['success']) && !empty($result['found']))
		{
			$item = $result['items'][0];

			$faceId = $item['face_id'];
			$contragents = array(array(
				'ID' => $faceId,
				'FACE_X' => $item['x'],
				'FACE_Y' => $item['y'],
				'FACE_WIDTH' => $item['width'],
				'FACE_HEIGHT' => $item['height']
			));
		}
		elseif (!$result['found'] && $result['code'] == \Bitrix\FaceId\FaceId::CODE_OK_UNKNOWN_PERSON)
		{
			$response = \Bitrix\FaceId\FaceId::add($fileContent, '1c');
			if (!empty($response['success']) && !empty($response['result']['added']))
			{
				$item = $response['result']['item'];

				$faceId = $item['face_id'];
				$contragents = array(array(
					'ID' => $faceId,
					'FACE_X' => $item['x'],
					'FACE_Y' => $item['y'],
					'FACE_WIDTH' => $item['width'],
					'FACE_HEIGHT' => $item['height']
				));
			}
			else
			{
				$errorResponse = array(
					'code' => $response['result']['code'],
					'msg' => \Bitrix\FaceId\FaceId::getErrorMessage($response['result']['code'])
				);
			}
		}
		else
		{
			$errorResponse = array(
				'msg' => \Bitrix\FaceId\FaceId::getErrorMessage($response['result']['code'])
			);
		}
	}
	else
	{
		$errorResponse = array('msg' => 'unknown action');
	}

	// event
	foreach ($contragents as $contragent)
	{
		$event = new Bitrix\Main\Event("faceid", "on1cFaceIdentified", array(
			'FACE_ID' => $contragent['ID']
		));
		$event->send();
	}
}

// output
echo \Bitrix\Main\Web\Json::encode(array(
	'contragents' => $contragents,
	'snapshot' => base64_encode($fileContent),
	'error' => $errorResponse
));

CMain::FinalActions();
