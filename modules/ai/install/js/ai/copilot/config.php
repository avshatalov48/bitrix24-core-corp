<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\AI\Agreement;
use Bitrix\AI\Container;
use Bitrix\AI\Facade\User;
use Bitrix\AI\Facade\Bitrix24;
use Bitrix\AI\Services\CopilotAccessCheckerService;
use Bitrix\Main\Loader;

$isShowAgreementPopup = false;
$isRestrictByEula = false;
$userHasAccessToLibrary = false;

if (Loader::includeModule('ai'))
{
	$userId = User::getCurrentUserId();

	if (Bitrix24::shouldUseB24() === false)
	{
		$isShowAgreementPopup = !Agreement::get('AI_BOX_AGREEMENT')->isAcceptedByUser($userId);
	}

	if (Bitrix24::isFeatureEnabled('ai_available_by_version') === false)
	{
		$isRestrictByEula = true;
	}

	$copilotAccessCheckerService = Container::init()->getItem(CopilotAccessCheckerService::class);
	$userHasAccessToLibrary = $copilotAccessCheckerService->canShowLibrariesInFrontend($userId);
}

return [
	'css' => 'dist/copilot.bundle.css',
	'js' => 'dist/copilot.bundle.js',
	'rel' => [
		'ai.engine',
		'ui.design-tokens',
		'ui.icon-set.editor',
		'ui.icon-set.crm',
		'ui.label',
		'main.loader',
		'ai.speech-converter',
		'ui.hint',
		'ui.icon-set.main',
		'ui.icon-set.actions',
		'ui.feedback.form',
		'ui.lottie',
		'ai.copilot',
		'ai.copilot.copilot-text-controller',
		'main.core.events',
		'main.popup',
		'ui.icon-set.api.core',
		'main.core',
		'ai.ajax-error-handler',
	],
	'skip_core' => false,
	'settings' => [
		'isRestrictByEula' => $isRestrictByEula,
		'isShowAgreementPopup' => $isShowAgreementPopup,
		'isLibraryVisible' => $userHasAccessToLibrary,
	]
];
