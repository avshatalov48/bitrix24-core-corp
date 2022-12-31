/**
 * @module crm/duplicates/finder
 */
jn.define('crm/duplicates/finder', (require, exports, module) => {

	const { isEmpty } = require('utils/object');
	const { Type } = require('crm/type');

	/**
	 * @function findDuplicates
	 */
	const findDuplicates = (props) => {
		const { duplicateControl, values, entityType, entityId } = props;

		if (!duplicateControl || isEmpty(values) || !Type.existsByName(entityType))
		{
			return Promise.resolve([]);
		}

		const { groupId, field } = duplicateControl;
		const ignoredCurrentItem = {
			ENTITY_TYPE_ID: Type.resolveIdByName(entityType),
			ENTITY_ID: entityId,
		};

		const fetchDuplicates = (PARAMS) =>
			BX.ajax.runComponentAction(
				`bitrix:crm.${entityType.toLowerCase()}.edit`,
				'FIND_DUPLICATES',
				{
					mode: 'ajax',
					data: {
						ACTION: 'FIND_DUPLICATES',
						PARAMS,
					},
				},
			);

		return new Promise((resolve) => {
			const findParams = { ENTITY_TYPE_NAME: entityType, ENTITY_ID: entityId };
			const groups = { GROUP_ID: groupId };
			Object.keys(values).forEach((key) => {
				groups[key] = values[key];
			});

			if (field)
			{
				groups['FIELD_ID'] = field.id;
			}

			return fetchDuplicates({
				...findParams,
				GROUPS: [groups],
				IGNORED_ITEMS: [ignoredCurrentItem],
			}).then((result) => {
				const { GROUP_RESULTS } = result;
				if (!Array.isArray(GROUP_RESULTS))
				{
					resolve([]);
				}

				resolve(GROUP_RESULTS[0]);
			});

		});
	};

	module.exports = { findDuplicates };

});