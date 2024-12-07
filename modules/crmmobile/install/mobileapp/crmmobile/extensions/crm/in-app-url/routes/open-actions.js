/**
 * @module crm/in-app-url/routes/open-actions
 */
jn.define('crm/in-app-url/routes/open-actions', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');
	const { Type } = require('crm/type');
	const { DisablingTools } = require('crm/disabling-tools');
	const { InfoHelper } = require('layout/ui/info-helper');

	const SUPPORTED_QUERY_PARAMS = [
		'uid',
		'changeTab',
		'phone',
		'categoryId',
		'origin_id',
		'contact_id',
		'lead_id',
		'quote_id',
		'conv_quote_id',
		'conv_deal_id',
		'conv_source_type_id',
		'conv_source_id',
	];

	const filterQueryParams = (keys, queryParams) => {
		const result = {};
		const isBoolean = (value) => value === 'N' || value === 'Y';

		keys.forEach((key) => {
			if (queryParams && queryParams.hasOwnProperty(key))
			{
				const value = queryParams[key];
				result[key] = isBoolean(value) ? value === 'Y' : value;
			}
		});

		return result;
	};

	/**
	 * @function openEntityDetail
	 * @param {number} entityTypeId
	 * @param {number} entityId
	 * @param {object} [options]
	 */
	const openEntityDetail = async (
		entityTypeId,
		entityId,
		{
			linkText = '',
			canOpenInDefault,
			queryParams,
			parentWidget,
			analytics,
			...restPayload
		} = {},
	) => {
		if (!Type.isEntitySupportedById(entityTypeId))
		{
			return;
		}

		const sliderCode = await DisablingTools.getSliderCode(entityTypeId);
		if (sliderCode)
		{
			const sliderUrl = await InfoHelper.getUrlByCode(sliderCode);

			helpdesk.openHelp(sliderUrl);

			return;
		}

		const filteredQueryParams = filterQueryParams(SUPPORTED_QUERY_PARAMS, queryParams);

		const payload = {
			entityId: Number(entityId),
			entityTypeId: Number(entityTypeId),
			...restPayload,
			...filteredQueryParams,
		};

		const widgetParams = {};
		if (linkText)
		{
			widgetParams.titleParams = { text: linkText };
		}

		const { EntityDetailOpener } = await requireLazy('crm:entity-detail/opener');
		const preparedAnalytics = analytics ?? new AnalyticsEvent(BX.componentParameters.get('analytics', {}))
		EntityDetailOpener.open({
			payload,
			widgetParams,
			parentWidget: parentWidget || null,
			canOpenInDefault,
			analytics: preparedAnalytics,
		});
	};

	/**
	 * @function openEntityList
	 * @param {object} options
	 * @param {string} [options.activeTabName]
	 * @param {number} entityTypeId
	 */
	const openEntityList = async ({ activeTabName, entityTypeId }) => {
		activeTabName = activeTabName.toUpperCase();
		if (!Type.isEntitySupportedByName(activeTabName))
		{
			return;
		}

		const sliderCode = await DisablingTools.getSliderCode(entityTypeId);
		if (sliderCode)
		{
			const sliderUrl = await InfoHelper.getUrlByCode(sliderCode);

			helpdesk.openHelp(sliderUrl);

			return;
		}

		ComponentHelper.openLayout(
			{
				widgetParams: {
					titleParams: {
						text: 'CRM',
					},
				},
				name: 'crm:crm.tabs',
				canOpenInDefault: true,
				componentParams: {
					activeTabName,
				},
			},
		);
	};

	module.exports = { openEntityDetail, openEntityList };
});
