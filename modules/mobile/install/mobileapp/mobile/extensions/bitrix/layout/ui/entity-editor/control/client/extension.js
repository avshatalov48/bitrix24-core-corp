/**
 * @module layout/ui/entity-editor/control/client
 */
jn.define('layout/ui/entity-editor/control/client', (require, exports, module) => {
	const { EntitySelectorFactoryType } = require('selector/widget/factory');

	const { get, isEmpty } = require('utils/object');
	const { CRM_COMPANY, CRM_CONTACT } = EntitySelectorFactoryType;
	const { EntityEditorField } = require('layout/ui/entity-editor/control/field');

	let SelectorProcessing = null;

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
				compound: this.getDataParam('compound', []),
				clientLayout: Number(get(this.editor, ['settings', 'config', 'options', 'client_layout'], 2)),
				owner: { id: this.editor.entityId },
			};
		}

		getPermissionByType(type)
		{
			return get(
				this.schemeElement.getData(),
				['permissions', type.toUpperCase()],
				false,
			);
		}

		canEditClients()
		{
			const contactPermission = this.getPermissionByType(CRM_CONTACT);
			const canEditContacts = contactPermission.add || contactPermission.update;

			const companyPermission = this.getPermissionByType(CRM_COMPANY);
			const canEditCompanies = companyPermission.add || companyPermission.update;

			return canEditContacts || canEditCompanies;
		}

		isVisible()
		{
			if (!this.hasValue() && !this.canEditClients())
			{
				return false;
			}

			return super.isVisible();
		}

		isMyCompany()
		{
			return this.getDataParam('enableMyCompanyOnly', false);
		}

		getModelDataByType(type)
		{
			const entityInfoName = this.getDataParam('info', `${this.id}_INFO`);
			const modelData = this.model?.data;

			if (isEmpty(modelData))
			{
				return [];
			}

			const entityInfo = get(modelData, [entityInfoName, `${type.toUpperCase()}_DATA`], []);

			const modelMultiFieldData = get(modelData, ['MULTIFIELD_DATA'], []);

			return entityInfo.map((entityData) => SelectorProcessing.prepareContact({
				...entityData,
				modelMultiFieldData,
			}));
		}

		getValuesToSave()
		{
			if (!this.isEditable())
			{
				return {};
			}

			const result = {};
			const compound = this.getDataParam('compound', []);
			for (const entity of compound)
			{
				const { name, type, entityTypeName } = entity;

				result[name] = this.getValueByType(entityTypeName, type);
			}

			return result;
		}

		getValueByType(entityTypeName, type)
		{
			const entityValueName = entityTypeName.toLowerCase();
			const entityValue = get(this.state, ['value', entityValueName], []);
			const entityIds = Array.isArray(entityValue)
				? entityValue
					.filter(({ deleted = false }) => !deleted)
					.map(({ id }) => Number(id))
				: [];

			let isMultiple = true;

			if (entityValueName === CRM_COMPANY)
			{
				isMultiple = type === 'multiple_company';
			}
			else if (entityValueName === CRM_CONTACT)
			{
				isMultiple = type === 'multiple_contact';
			}

			if (isMultiple)
			{
				return entityIds;
			}

			if (this.editor.entityTypeName === 'store_document' && entityValueName === CRM_COMPANY)
			{
				return entityIds;
			}

			return entityIds.length > 0 ? entityIds[0] : '';
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

			return (Array.isArray(contacts) && contacts.length > 0)
				|| (Array.isArray(companies) && companies.length > 0);
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
