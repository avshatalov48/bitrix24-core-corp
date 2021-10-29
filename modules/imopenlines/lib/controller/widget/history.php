<?php
namespace Bitrix\ImOpenLines\Controller\Widget;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\ImOpenLines\Chat;
use Bitrix\ImOpenLines\Controller\Widget\Filter;
use Bitrix\Main\Engine\CurrentUser;

class History extends Controller
{
	protected function getDefaultPreFilters(): array
	{
		return [
			new Filter\Authorization(),
			new ActionFilter\HttpMethod(['POST', 'OPTIONS']),
			new Filter\PreflightCors(),
		];
	}

	protected function getDefaultPostFilters(): array
	{
		return [
			new ActionFilter\Cors(null, true),
		];
	}

	public function configureActions(): array
	{
		return [
			'download' => [
				'-prefilters' => [
					ActionFilter\Csrf::class,
					ActionFilter\Authentication::class
				],
			]
		];
	}

	public function downloadAction(int $chatId, CurrentUser $currentUser): ?\Bitrix\Main\HttpResponse
	{
		$liveChat = new Chat($chatId);
		$chatAuthorId = (int)$liveChat->getData('AUTHOR_ID');
		$currentUserId = (int)$currentUser->getId();
		if ($chatAuthorId !== $currentUserId)
		{
			return null;
		}

		$chatFieldSession = $liveChat->getFieldData(Chat::FIELD_LIVECHAT);
		$sessionId = $chatFieldSession['SESSION_ID'];
		if ($sessionId <= 0)
		{
			return null;
		}

		ob_start();
		global $APPLICATION;
		$APPLICATION->IncludeComponent(
			"bitrix:imopenlines.mail.history",
			"html",
			[
				"TEMPLATE_SERVER_ADDRESS" => \Bitrix\ImOpenLines\Common::getServerAddress(),
				"TEMPLATE_SESSION_ID" => $sessionId,
				"TEMPLATE_WIDGET_LOCATION" => $this->request->getHeader('referer'),
				"TEMPLATE_TYPE" => 'HISTORY',
			]
		);
		$historyComponentContent = ob_get_clean();

		$response = new \Bitrix\Main\HttpResponse();
		$response->addHeader('Content-Type', 'application/octet-stream');
		$response->setContent($historyComponentContent);

		return $response;
	}
}