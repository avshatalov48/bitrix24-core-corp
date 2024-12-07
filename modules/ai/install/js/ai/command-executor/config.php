<?

use Bitrix\Main\Loader;
use Bitrix\AI\Agreement;
use Bitrix\AI\Facade\User;
use \Bitrix\AI\Facade\Bitrix24;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$isAgreementAccepted = true;

if (Loader::includeModule('ai'))
{
	if (Bitrix24::shouldUseB24() === false)
	{
		$userId = User::getCurrentUserId();
		$isAgreementAccepted = Agreement::get('AI_BOX_AGREEMENT')->isAcceptedByUser($userId);
	}
}

return [
	'css' => 'dist/command-executor.bundle.css',
	'js' => 'dist/command-executor.bundle.js',
	'rel' => [
		'ai.engine',
		'ai.payload.textpayload',
		'main.core',
	],
	'skip_core' => false,
	'settings' => [
		'isAgreementAccepted' => $isAgreementAccepted,
	]
];
