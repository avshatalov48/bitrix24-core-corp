/**
 * @module layout/ui/entity-editor/control/client
 */
jn.define('layout/ui/entity-editor/control/client', (require, exports, module) => {

	const { get } = require('utils/object');
	const { CRM_COMPANY, CRM_CONTACT } = EntitySelectorFactory.Type;

	let SelectorProcessing;

	try
	{
		SelectorProcessing = jn.require('crm/selector/utils/processing').SelectorProcessing;
	}
	catch (e)
	{
		console.warn(e);

		return;
	}

	/**
	 * @class EntityEditorClient
	 */
	class EntityEditorClient extends EntityEditorField
	{
		prepareConfig()
		{
			return {
				...super.prepareConfig(),
				showClientAdd: !this.isReadOnly(),
				showClientInfo: true,
				selectorTitle: this.getTitle(),
				reloadEntityListFromProps: get(this.editor, ['settings', 'loadFromModel'], false),
				entityList: this.prepareEntityList(),
				clientLayout: Number(get(this.editor, ['settings', 'config', 'options', 'client_layout'], 2)),
				owner: { id: this.editor.entityId },
			};
		}

		getModelDataByType(type)
		{
			const entityInfo = get(this.model, ['data', 'CLIENT_INFO', `${type.toUpperCase()}_DATA`], []);

			return entityInfo.map(SelectorProcessing.prepareContact);
		}

		getValuesToSave()
		{
			if (!this.isEditable())
			{
				return {};
			}

			const entityTypeName = this.editor.entityTypeName;
			const companyKey = CRM_COMPANY.toUpperCase();
			const companyValue = this.getValueByType(CRM_COMPANY);
			const contactKey = CRM_CONTACT.toUpperCase();
			const contactValue = this.getValueByType(CRM_CONTACT);

			switch (entityTypeName)
			{
				case companyKey:
					return {
						[`${contactKey}_ID`]: contactValue,
					};

				case contactKey:
					return {
						[`${companyKey}_IDS`]: companyValue,
					};

				default:
					return {
						[`${companyKey}_ID`]: companyValue,
						[`${contactKey}_IDS`]: contactValue,
					};
			}
		}

		getValueByType(type)
		{
			const entityValue = get(this.state, ['value', type], []);
			const entityIds = entityValue.length ? entityValue.map(({ id }) => (Number(id))) : [];

			if (this.editor.entityTypeName === 'DEAL' && type === CRM_COMPANY)
			{
				return entityIds.length ? entityIds[0] : '';
			}

			return entityIds;
		}

		getValueFromModel(defaultValue = '')
		{
			if (this.model)
			{
				return this.prepareEntityList();
			}

			return defaultValue;
		}

		prepareEntityList()
		{
			return {
				[CRM_CONTACT]: this.getModelDataByType(CRM_CONTACT),
				[CRM_COMPANY]: this.getModelDataByType(CRM_COMPANY),
			};
		}

		hasValue()
		{
			const {
				[CRM_CONTACT]: contacts,
				[CRM_COMPANY]: companies,
			} = this.getValue();

			return (
				Array.isArray(contacts) && contacts.length
				|| Array.isArray(companies) && companies.length
			);
		}

		getSolidBorderContainerColor()
		{
			if (this.hasValue())
			{
				return super.getSolidBorderContainerColor();
			}

			return '#7fdefc';
		}
	}

	module.exports = { EntityEditorClient };

});