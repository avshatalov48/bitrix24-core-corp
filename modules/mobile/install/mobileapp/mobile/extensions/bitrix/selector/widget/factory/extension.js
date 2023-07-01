(() => {

	const { ProjectSelector } = jn.require('selector/widget/entity/socialnetwork/project');
	const { TaskTagSelector } = jn.require('selector/widget/entity/tasks/task-tag');

	let CrmContactSelector;
	let CrmCompanySelector;
	let CrmElementSelector;
	let DocumentGeneratorTemplateSelector;

	try
	{
		CrmContactSelector = jn.require('crm/selector/entity/contact').CrmContactSelector;
		CrmCompanySelector = jn.require('crm/selector/entity/company').CrmCompanySelector;
		CrmElementSelector = jn.require('crm/selector/entity/element').CrmElementSelector;
		DocumentGeneratorTemplateSelector = jn.require('crm/selector/documentgenerator/template').DocumentGeneratorTemplateSelector;
	}
	catch (e)
	{
		console.warn(e);
	}

	/**
	 * @class EntitySelectorFactory.Type
	 */
	const Type = {
		SECTION: 'section',
		PRODUCT: 'product',
		STORE: 'store',
		CONTRACTOR: 'contractor',
		USER: 'user',
		PROJECT: 'project',
		PROJECT_TAG: 'project_tag',
		CRM_CONTACT: 'contact',
		CRM_COMPANY: 'company',
		CRM_ELEMENT: 'crm-element',
		DOCUMENTGENERATOR_TEMPLATE: 'documentgenerator-template',
		TASK_TAG: 'task_tag',
		IBLOCK_ELEMENT_USER_FIELD: 'iblock-element-user-field',
		IBLOCK_SECTION_USER_FIELD: 'iblock-section-user-field',
	};

	/**
	 * @class EntitySelectorFactory
	 */
	class EntitySelectorFactory
	{
		static createByType(type, data)
		{
			if (type === Type.SECTION)
			{
				return CatalogSectionSelector.make(data);
			}

			if (type === Type.PRODUCT)
			{
				return CatalogProductSelector.make(data);
			}

			if (type === Type.STORE)
			{
				return CatalogStoreSelector.make(data);
			}

			if (type === Type.CONTRACTOR)
			{
				return CatalogContractorSelector.make(data);
			}

			if (type === Type.USER)
			{
				return SocialNetworkUserSelector.make(data);
			}

			if (type === Type.PROJECT)
			{
				return ProjectSelector.make(data);
			}

			if (type === Type.PROJECT_TAG)
			{
				return ProjectTagSelector.make(data);
			}

			if (type === Type.CRM_CONTACT && CrmContactSelector)
			{
				return CrmContactSelector.make(data);
			}

			if (type === Type.CRM_COMPANY && CrmCompanySelector)
			{
				return CrmCompanySelector.make(data);
			}

			if (type === Type.CRM_ELEMENT && CrmElementSelector)
			{
				return CrmElementSelector.make(data);
			}

			if (type === Type.DOCUMENTGENERATOR_TEMPLATE && DocumentGeneratorTemplateSelector)
			{
				return DocumentGeneratorTemplateSelector.make(data);
			}

			if (type === Type.TASK_TAG)
			{
				return TaskTagSelector.make(data);
			}

			if (type === Type.IBLOCK_ELEMENT_USER_FIELD)
			{
				return IblockElementUserFieldSelector.make(data);
			}

			if (type === Type.IBLOCK_SECTION_USER_FIELD)
			{
				return IblockSectionUserFieldSelector.make(data);
			}

			return null;
		}
	}

	this.EntitySelectorFactory = EntitySelectorFactory;
	this.EntitySelectorFactory.Type = Type;
})();
