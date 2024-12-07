/**
 * @module crm/selector/utils/processing
 */
jn.define('crm/selector/utils/processing', (require, exports, module) => {
	const { isEmpty } = require('utils/object');
	const { Type } = require('crm/type');
	const TYPE_ADVANCED_INFO = ['EMAIL', 'PHONE', 'IM'];

	/**
	 * @class SelectorProcessing
	 */
	class SelectorProcessing
	{
		static prepareContact(secondaryData)
		{
			const {
				id,
				title,
				type,
				desc,
				hidden = false,
				advancedInfo = {},
				notFound = false,
				modelMultiFieldData,
			} = secondaryData;

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
				multiFields.forEach(({ TITLE = '', TYPE_ID, VALUE_TYPE, COMPLEX_NAME, VALUE_FORMATTED }) => {
					const key = TYPE_ID.toLowerCase();
					if (TYPE_ADVANCED_INFO.includes(TYPE_ID) && VALUE_FORMATTED)
					{
						const fieldValue = SelectorProcessing.prepareValue({
							TITLE,
							VALUE: VALUE_FORMATTED,
							COMPLEX_NAME,
							VALUE_TYPE,
						});

						if (key in params)
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
			// for hidden contacts
			else if (modelMultiFieldData)
			{
				const entityTypeId = Type.resolveIdByName(type);
				const connectionId = `${entityTypeId}_${id}`;
				const entityInfo = SelectorProcessing.getMultiFieldClientInfo(modelMultiFieldData, connectionId);

				Object.keys(entityInfo).forEach((key) => {
					if (key in params)
					{
						params[key].push(...entityInfo[key]);
					}
					else
					{
						params[key] = entityInfo[key];
					}
				});
			}

			return params;
		}

		static getMultiFieldClientInfo(multiFieldData, connectionId)
		{
			const contactsInfo = {};

			TYPE_ADVANCED_INFO.forEach((connectionType) => {
				const connectionsByType = multiFieldData?.[connectionType]?.[connectionId] ?? [];

				if (connectionsByType.length > 0)
				{
					contactsInfo[connectionType.toLowerCase()] = connectionsByType
						.map((value) => SelectorProcessing.prepareValue(value))
						.filter(Boolean);
				}
			});

			return contactsInfo;
		}

		static prepareValue(data)
		{
			return {
				title: data.TITLE,
				value: data.VALUE,
				complexName: data.COMPLEX_NAME,
				valueType: data.VALUE_TYPE,
			};
		}
	}

	module.exports = { SelectorProcessing, TYPE_ADVANCED_INFO };
});
