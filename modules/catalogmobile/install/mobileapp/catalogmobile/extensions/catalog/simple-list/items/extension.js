/**
 * @module catalog/simple-list/items
 */
jn.define('catalog/simple-list/items', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { StoreDocument } = require('catalog/simple-list/items/store-document');
	const { ListItemsFactory: BaseListItemsFactory } = require('layout/ui/simple-list/items');

	const ListItemType = {
		STORE_DOCUMENT: 'StoreDocument',
	};

	const StatusColors = {
		Y: {
			backgroundColor: AppTheme.colors.accentSoftGreen1,
			color: AppTheme.colors.accentSoftElementGreen1,
		},
		N: {
			backgroundColor: AppTheme.colors.bgSeparatorSecondary,
			color: AppTheme.colors.base3,
		},
		C: {
			backgroundColor: AppTheme.colors.accentSoftOrange1,
			color: AppTheme.colors.accentExtraBrown,
		},
	};

	/**
	 * @class ListItemsFactory
	 */
	class ListItemsFactory extends BaseListItemsFactory
	{
		static create(type, data)
		{
			if (type === ListItemType.STORE_DOCUMENT)
			{
				const processedData = ListItemsFactory.fillStatusColors(data);

				return new StoreDocument(processedData);
			}

			return BaseListItemsFactory.create(type, data);
		}

		static fillStatusColors(data)
		{
			const filledData = data;
			const fields = filledData.item.data.fields;
			const statusValue = filledData.item.data.statuses[0];

			for (const [i, field] of fields.entries())
			{
				if (field.name !== 'DOC_STATUS')
				{
					continue;
				}

				const fieldWithColors = field;
				fieldWithColors.value[0].backgroundColor = StatusColors[statusValue].backgroundColor;
				fieldWithColors.value[0].color = StatusColors[statusValue].color;

				filledData.item.data.fields[i] = fieldWithColors;
			}

			return filledData;
		}
	}

	module.exports = { ListItemsFactory, ListItemType };
});
