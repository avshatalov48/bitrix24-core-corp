import { Type } from 'main.core';

const MAIN_DIR = '/market/';

export class MarketLinks
{
	static siteTemplateUrn = false;

	static mainLink()
	{
		return MAIN_DIR;
	}

	static favoritesLink()
	{
		return MAIN_DIR + 'favorites/';
	}

	static installedLink()
	{
		return MAIN_DIR + 'installed/';
	}

	static categoryLink(categoryCode) {
		return MAIN_DIR + 'category/' + categoryCode + '/';
	}

	static collectionLink(collectionId, showOnPage) {
		if (showOnPage === 'Y') {
			return MarketLinks.collectionPageLink(collectionId);
		}

		return MAIN_DIR + 'collection/' + collectionId + '/';
	}

	static collectionPageLink(collectionId) {
		return MAIN_DIR + 'collection/page/' + collectionId + '/';
	}

	static appDetail(appItem, queryParams = {})
	{
		const appCode = appItem.CODE ?? appItem.APP_CODE;

		if (appItem.IS_SITE_TEMPLATE === 'Y') {
			if (Type.isString(appItem.LANDING_TYPE) && appItem.LANDING_TYPE === 'VIBE') {
				return MarketLinks.vibeDetailLink(appCode, queryParams);
			}

			return MarketLinks.siteDetailLink(appCode, queryParams);
		}

		const params = (new URLSearchParams(queryParams)).toString();
		const query = (params.length) ? '?' + params : '';

		return MAIN_DIR + 'detail/' + appCode + '/' + query;
	}

	static siteDetailLink(appCode, queryParams)
	{
		const from = queryParams.from ?? '';
		let path = '/sites/site/edit/0/?IS_FRAME=Y&tpl=market/' + appCode + '&from=' + from;

		if (MarketLinks.siteTemplateUrn === false) {
			MarketLinks.siteTemplateUrn = (new URLSearchParams(document.location.search)).get("create_uri");
			if (!Type.isString(MarketLinks.siteTemplateUrn)) {
				MarketLinks.siteTemplateUrn = '';
			}
		}

		if (MarketLinks.siteTemplateUrn.length > 0 && MarketLinks.siteTemplateUrn.startsWith('/')) {
			let uri = new URL(MarketLinks.siteTemplateUrn, window.location.href);
			uri.searchParams.append('IS_FRAME', 'Y');
			uri.searchParams.append('tpl', 'market/' + appCode);

			path = uri.pathname + uri.search;
		}

		return path;
	}

	static vibeDetailLink(appCode, queryParams)
	{
		const from = queryParams.from ?? '';

		return '/vibe/new/?tpl=market/' + appCode + '&from=' + from;
	}

	static openSiteTemplate(event, isSiteTemplate)
	{
		if (isSiteTemplate) {
			event.preventDefault();
			BX.SidePanel.Instance.open(event.currentTarget.href, {
				customLeftBoundary: 60,
			});
		}
	}
}