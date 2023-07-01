/**
 * @module crm/selector/utils/processing
 */
jn.define('crm/selector/utils/processing', (require, exports, module) => {
	const { isEmpty } = require('utils/object');
	const TYPE_ADVANCED_INFO = new Set(['EMAIL', 'PHONE', 'IM']);

	/**
	 * @class SelectorProcessing
	 */
	class SelectorProcessing
	{
		static prepareContact(secondaryData)
		{
			const { id, title, type, desc, hidden = false, advancedInfo = {}, notFound = false } = secondaryData;

			const params = {
				id: Number(id),
				title,
				type,
				subtitle: desc,
				hidden,
				deleted: notFound,
			};

			const multiFields = advancedInfo.multiFields || [];
			const requisiteData = advancedInfo.requisiteData || [];

			params.addresses = requisiteData
				.filter(({ selected }) => selected)
				.flatMap(({ requisiteData: requisiteInfo }) => {
					try
					{
						const requisite = JSON.parse(requisiteInfo);

						if (isEmpty(requisite) || isEmpty(requisite.formattedAddresses))
						{
							return [];
						}

						return Object.values(requisite.formattedAddresses);
					}
					catch (e)
					{
						console.error(e);
						return [];
					}
				});

			if (multiFields.length > 0)
			{
				multiFields.forEach(({ TYPE_ID, VALUE_FORMATTED, COMPLEX_NAME, VALUE_TYPE }) => {
					const key = TYPE_ID.toLowerCase();
					if (TYPE_ADVANCED_INFO.has(TYPE_ID) && VALUE_FORMATTED)
					{
						const fieldValue = {
							value: VALUE_FORMATTED,
							complexName: COMPLEX_NAME,
							valueType: VALUE_TYPE,
						};

						if (params.hasOwnProperty(key))
						{
							params[key].push(fieldValue);
						}
						else
						{
							params[key] = [fieldValue];
						}
					}
				});
			}

			return params;
		}
	}

	module.exports = { SelectorProcessing };
});
