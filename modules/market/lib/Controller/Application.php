<?php

namespace Bitrix\Market\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Rest\AppTable;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Market\Link;
use Bitrix\Rest\Engine\Access;
use Bitrix\Rest\Marketplace\Transport;

class Application extends Controller
{
	public function getContextAction(string $from = ''): array
	{
		$landingCode = '';
		if (Loader::includeModule('rest')) {
			$landingCode = Access::getHelperCode(Access::ACTION_BUY);
		}

		return [
			'linkInstall' => Link::getInstallPage('', null, null, null, $from),
			'subscriptionLinkBuy' => Link::getSubscriptionBuy($from),
			'subscriptionBuyLandingCode' => $landingCode,
		];
	}

	public function installAction(string $code, int $version = 0, string $checkHash = null, string $installHash = null, $from = null): array
	{
		if (!Loader::includeModule('rest')) {
			return [
				'error' => 'ERROR_LOADS_MODULE_REST',
				'errorDescription' => Loc::getMessage('MARKET_ERROR_LOADS_MODULE_REST'),
			];
		}

		return \Bitrix\Rest\Marketplace\Application::install($code, $version, $checkHash, $installHash, $from);
	}

	public function uninstallAction(string $code, string $clean = 'N', string $from = null): array
	{
		if (!Loader::includeModule('rest')) {
			return [
				'error' => 'ERROR_LOADS_MODULE_REST',
				'errorDescription' => Loc::getMessage('MARKET_ERROR_LOADS_MODULE_REST'),
			];
		}

		return \Bitrix\Rest\Marketplace\Application::uninstall($code, ($clean === 'Y'), $from);
	}

	public function reinstallAction(int $id): array
	{
		if (!Loader::includeModule('rest')) {
			return [
				'error' => 'ERROR_LOADS_MODULE_REST',
				'errorDescription' => Loc::getMessage('MARKET_ERROR_LOADS_MODULE_REST'),
			];
		}

		return \Bitrix\Rest\Marketplace\Application::reinstall($id);
	}

	public function setRightsAction(string $appCode, array $rights = []): array
	{
		$result = [
			'error' => 'ERROR_LOADS_MODULE_REST',
			'errorDescription' => Loc::getMessage('MARKET_ERROR_LOADS_MODULE_REST'),
		];

		if (Loader::includeModule('rest')) {
			$app = AppTable::getByClientId($appCode);
			if ($app['ID'] > 0) {
				$result = \Bitrix\Rest\Marketplace\Application::setRights((int)$app['ID'], $rights);
			}
		}

		return $result;
	}

	public function getRightsAction(string $appCode)
	{
		$result = [
			'error' => 'ERROR_LOADS_MODULE_REST',
			'errorDescription' => Loc::getMessage('MARKET_ERROR_LOADS_MODULE_REST'),
		];

		if (Loader::includeModule('rest')) {
			$app = AppTable::getByClientId($appCode);
			if ($app['ID'] > 0) {
				$result = \Bitrix\Rest\Marketplace\Application::getRights((int)$app['ID']);
			}
		}

		return $result;
	}

	public function addReviewAction(string $appCode, string $reviewText, int $currentRating): AjaxJson
	{
		global $USER;

		$userName = $USER->GetFirstName() . ' ' . mb_substr($USER->GetLastName(), 0, 1) . '.';
		$fields = [
			'app_code' => $appCode,
			'rating' => $currentRating,
			'user_id' => $USER->GetID(),
			'user_name_hash' => md5($userName),
			'text_hash' => md5($reviewText),
		];

		if (function_exists('bx_sign')) {
			$fields['hash'] = bx_sign(md5(implode('|', $fields)));
		}

		$fields['user_name'] = $userName;
		$fields['user_email'] = $USER->GetEmail();
		$fields['text'] = $reviewText;

		$response = Transport::instance()->call(
			Transport::METHOD_ADD_REVIEW,
			$fields,
		);
		$error = (array)($response['ERROR'] ?? []);

		return AjaxJson::createSuccess([
			'success' => $response['SUCCESS'] === 'Y' ? 'Y' : 'N',
			'can_review' => $response['CAN_REVIEW'] === 'Y' ? 'Y' : 'N',
			'review_info' => $response['REVIEW_INFO'] ?? [],
			'error' => $error,
		]);
	}

	protected function getDefaultPreFilters(): array
	{
		$defaultPreFilters = parent::getDefaultPreFilters();
		$defaultPreFilters[] = new ActionFilter\Scope(ActionFilter\Scope::NOT_REST);

		return $defaultPreFilters;
	}
}