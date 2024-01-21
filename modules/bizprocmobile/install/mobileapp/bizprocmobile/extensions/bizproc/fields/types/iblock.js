/**
 * @module bizproc/fields/types/iblock
 */
jn.define('bizproc/fields/types/iblock', (require, exports, module) => {

	const {
		MoneyType,
		EntitySelectorType,
	} = require('layout/ui/fields');
	const { isObjectLike } = require('utils/object');

	const IblockTypeMap = {
		'E:ECrm': {
			type: EntitySelectorType,
			config: (property) => (isObjectLike(property.Settings) ? property.Settings : {}),
			extractor: (crmIds, crmData) => {
				return crmData.map((item) => {

					if (item.type === 'dynamic_multiple')
					{
						const [typeId, id] = item.id.split(':');

						return `dynamic_${typeId}_${id}`;
					}

					return `${item.type}_${item.id}`;
				});
			},
		},
		'S:Money': {
			type: MoneyType,
		},
		'E:EList': {
			type: EntitySelectorType,
			config: (property) => (isObjectLike(property.Settings) ? property.Settings : {}),
		},
		// 'S:HTML': { },// converted to TEXT now, todo: BB code editor
		// 'S:employee': { },// converted to USER
		// 'S:DiskFile': { },// converted to FILE
	};

	module.exports = { IblockTypeMap };
});
