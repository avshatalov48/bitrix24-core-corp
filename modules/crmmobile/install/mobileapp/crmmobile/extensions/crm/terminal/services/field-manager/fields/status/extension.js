/**
 * @module crm/terminal/services/field-manager/fields/status
 */
jn.define('crm/terminal/services/field-manager/fields/status', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { mergeImmutable } = require('utils/object');

	const STATUS_COLOR = {
		paid: {
			backgroundColor: AppTheme.colors.accentSoftGreen1,
			color: AppTheme.colors.accentSoftElementGreen1,
		},
		unpaid: {
			backgroundColor: AppTheme.colors.accentSoftOrange1,
			color: AppTheme.colors.accentExtraBrown,
		},
	};

	class StatusFields
	{
		static getStatusColor(type)
		{
			return STATUS_COLOR[type];
		}

		static prepareData(data)
		{
			const value = data.value.map((dataValue) => {
				const statusColors = STATUS_COLOR[dataValue.id];

				return { ...dataValue, ...statusColors };
			});

			return mergeImmutable(data, { value });
		}
	}

	module.exports = { StatusFields };
});
