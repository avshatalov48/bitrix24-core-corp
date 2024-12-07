<?

use Bitrix\Main\HttpResponse;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Engine;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\ActionFilter;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class IntranetInvitationGuestController extends Engine\Controller
{
	protected function shouldDecodePostData(Action $action): bool
	{
		return false;
	}

	public function configureActions()
	{
		return [
			'getSliderContent' => [
				'-prefilters' => [
					ActionFilter\Csrf::class,
				],
			],
		];
	}

	public function getSliderContentAction(string $componentParams = '')
	{
		$params =
			$componentParams
				? Json::decode($componentParams)
				: []
		;

		$content = $GLOBALS['APPLICATION']->includeComponent(
			'bitrix:ui.sidepanel.wrapper',
			'',
			[
				'RETURN_CONTENT' => true,
				'POPUP_COMPONENT_NAME' => 'bitrix:intranet.invitation.guest',
				'POPUP_COMPONENT_TEMPLATE_NAME' => '',
				'POPUP_COMPONENT_PARAMS' => [
					'USER_OPTIONS' => isset($params['USER_OPTIONS']) ? $params['USER_OPTIONS'] : [],
					'ROWS' => isset($params['ROWS']) ? $params['ROWS'] : []
				],
				'IFRAME_MODE' => true
			]
		);

		$response = new HttpResponse();
		$response->setContent($content);

		return $response;
	}
}