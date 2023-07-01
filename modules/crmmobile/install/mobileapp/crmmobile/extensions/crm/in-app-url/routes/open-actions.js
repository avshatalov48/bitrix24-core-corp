/**
 * @module crm/in-app-url/routes/open-actions
 */
jn.define('crm/in-app-url/routes/open-actions', (require, exports, module) => {
	const { Type } = require('crm/type');

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
	 * @param {string} entityTypeId
	 * @param {string} entityId
	 * @param {object} [options]
	 */
	const openEntityDetail = (
		entityTypeId,
		entityId,
		{
			linkText = '',
			canOpenInDefault,
			queryParams,
			parentWidget,
			...restPayload
		} = {},
	) => {
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

		jn.import('crm:entity-detail/opener')
			.then(() => {
				const { EntityDetailOpener } = require('crm/entity-detail/opener');

				EntityDetailOpener.open(
					payload,
					widgetParams,
					parentWidget || null,
					canOpenInDefault,
				);
			})
			.catch(console.error);
	};

	/**
	 * @function openEntityList
	 * @param {object} options
	 * @param {string} [options.activeTabName]
	 */
	const openEntityList = ({ activeTabName }) => {
		if (!Type.isEntitySupportedByName(activeTabName))
		{
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
