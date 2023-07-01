/**
 * @module crm/duplicates/finder
 */
jn.define('crm/duplicates/finder', (require, exports, module) => {
	const { isEmpty } = require('utils/object');
	const { Type, TypeName } = require('crm/type');
	const COMPONENT_MAP = {
		[TypeName.Contact]: 'crm.contact.edit',
		[TypeName.Company]: 'crm.company.edit',
		[TypeName.Lead]: 'crm.lead.edit',
	};

	/**
	 * @function findDuplicates
	 */
	const findDuplicates = (props) => {
		const { duplicateControl, values, entityTypeName, entityId } = props;
		const component = COMPONENT_MAP[entityTypeName];

		if (
			!duplicateControl
			|| isEmpty(values)
			|| !Type.existsByName(entityTypeName)
			|| !component
		)
		{
			return Promise.resolve([]);
		}

		const { groupId, field } = duplicateControl;
		const ignoredCurrentItem = {
			ENTITY_TYPE_ID: Type.resolveIdByName(entityTypeName),
			ENTITY_ID: entityId,
		};

		const fetchDuplicates = (PARAMS) => BX.ajax.runComponentAction(
			`bitrix:${component}`,
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
			const findParams = { ENTITY_TYPE_NAME: entityTypeName, ENTITY_ID: entityId };
			const groups = { GROUP_ID: groupId };
			Object.keys(values).forEach((key) => {
				groups[key] = values[key];
			});

			if (field)
			{
				groups.FIELD_ID = field.id;
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
