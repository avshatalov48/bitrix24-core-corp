<?php


namespace Bitrix\Market;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Web\Uri;

class Link
{
	private static string $keyFrom = 'from';

	private static array $linkList = [
		'favorites' => 'favorites/',
		'installed' => 'installed/',
		'search' => 'search/',
		'detail' => 'detail/#appCode#/',
		'install' => 'install/#appCode#/#version#/#installHash#/#checkHash#/',
		'category' => 'category/#categoryCode#/',
		'collectionFull' =>  'collection/#collectionId#/',
		'collectionPage' =>  'collection/page/#collectionId#/',
		'devops' => '/devops/',
	];

	public static function getFrom(): string
	{
		$from = '';
		$request = Application::getInstance()->getContext()->getRequest();
		$value = $request->get(Link::$keyFrom);
		if (is_string($value)) {
			$from = htmlspecialcharsbx($value);
		}

		return $from;
	}

	public static function getDir(string $from = ''): string
	{
		return PageRules::MAIN_PAGE . ($from !== '' ? '?' . Link::$keyFrom . '=' . $from : '');
	}

	protected static function getUrl($page, $replace = null, $subject = null, $query = null, string $from = '')
	{
		if (!Link::$linkList[$page]) {
			return null;
		}

		$url = Link::getDir() . Link::$linkList[$page];
		if (mb_strpos(Link::$linkList[$page], '/') === 0) {
			$url = Link::$linkList[$page];
		}
		$url = Link::getReplaced($url, $replace, $subject);
		$url = preg_replace('/(\/){2,}/', '/', $url);

		$requestFrom = Link::getFrom();
		if ($requestFrom) {
			$from = $requestFrom . ($from ? '|' . $from : '');
		}
		if ($from) {
			$query[Link::$keyFrom] = $from;
		}

		if (is_array($query)) {
			$url = Link::addParams($url, $query);
		}

		return $url;
	}

	private static function getReplaced(string $url, $replace = null, $subject = null)
	{
		if (!is_null($replace) && !is_null($subject)) {
			$url = str_replace($replace, $subject, $url);
		}

		return $url;
	}

	private static function addParams($url, $params)
	{
		if (is_array($params)) {
			$uri = new Uri($url);
			$uri->addParams($params);
			$url = $uri->getUri();
		}

		return $url;
	}

	public static function getFullCollection($collectionId, $showOnPage, string $from = ''): string
	{
		if ($showOnPage === 'Y') {
			return Link::getCollectionPage($collectionId, $from);
		}

		return Link::getUrl('collectionFull', '#collectionId#', $collectionId, null, $from);
	}

	public static function getCollectionPage($collectionId, string $from = ''): string
	{
		return Link::getUrl('collectionPage', '#collectionId#', $collectionId, null, $from);
	}

	public static function getFavoritesPage(string $from = ''): string
	{
		return Link::getUrl('favorites', null, null, null, $from);
	}

	public static function getInstalledPage(string $from = ''): string
	{
		return Link::getUrl('installed', null, null, null, $from);
	}

	public static function getCategoryPage(string $categoryCode, string $from = ''): string
	{
		return Link::getUrl('category', '#categoryCode#', $categoryCode, null, $from);
	}

	public static function getDetailPage(string $appCode, string $from = ''): string
	{
		return Link::getUrl('detail', '#appCode#', $appCode, null, $from);
	}

	public static function getInstallPage(string $appCode = '', int $version = null, string $installHash = null, string $checkHash = null, string $from = ''): string
	{
		if ($appCode !== '') {
			$installHash = $version > 0 ? $installHash : null;
			$checkHash = $version > 0 && $installHash ? $checkHash : null;
			$version = $version === 0 ? null : $version;

			return Link::getUrl(
				'install',
				[
					'#appCode#',
					'#version#',
					'#installHash#',
					'#checkHash#',
				],
				[
					$appCode,
					$version,
					$installHash,
					$checkHash,
				],
				null,
				$from
			);
		}

		return Link::getUrl('install');
	}

	public static function getSearchPage(string $from = ''): string
	{
		return Link::getUrl('search', null, null, null, $from);
	}

	public static function getDevOpsSection(string $from = ''): string
	{
		return Link::getUrl('devops', null, null, null, $from);
	}

	public static function getIntegrationCategory(string $from = ''): string
	{
		return Link::getCategoryPage('integrations', $from);
	}

	public static function getMarketCategory(string $from = ''): string
	{
		return Link::getCategoryPage('subscription', $from);
	}

	public static function getSubscriptionBuy(string $from = ''): string
	{
		if ($from !== '') {
			$from = '&' . Link::$keyFrom . '=' . $from;
		}

		$result = '';
		if (ModuleManager::isModuleInstalled('bitrix24')) {
			$result = '/settings/license_buy.php?product=subscr' . $from;
		} else {
			$region = Option::get('main', '~PARAM_CLIENT_LANG', LANGUAGE_ID);

			if ($region === 'ru') {
				$result = 'https://www.1c-bitrix.ru/buy/products/b24.php?subscr=y' . $from;
			} elseif ($region === 'ua') {
				$result = 'https://www.bitrix.ua/buy/products/b24.php?subscr=y' . $from;
			}
		}

		return $result;
	}
}