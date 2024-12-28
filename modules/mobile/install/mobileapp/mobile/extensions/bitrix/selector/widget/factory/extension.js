/**
 * @module selector/widget/factory
 */
jn.define('selector/widget/factory', (require, exports, module) => {
	const { ProjectSelector } = require('selector/widget/entity/socialnetwork/project');
	const { WarnLogger } = require('utils/logger/warn-logger');
	const { SocialNetworkUserSelector } = require('selector/widget/entity/socialnetwork/user');
	const { DepartmentSelector } = require('selector/widget/entity/intranet/department');

	let TaskTagSelector = null;
	let TaskFlowSelector = null;

	try
	{
		TaskTagSelector = require('selector/widget/entity/tasks/task-tag').TaskTagSelector;
		TaskFlowSelector = require('tasks/entity-selector/flow').TaskFlowSelector;
	}
	catch (e)
	{
		(new WarnLogger()).warn(e);
	}

	let CrmContactSelector = null;
	let CrmCompanySelector = null;
	let CrmElementSelector = null;
	let DocumentGeneratorTemplateSelector = null;

	try
	{
		CrmContactSelector = require('crm/selector/entity/contact').CrmContactSelector;
		CrmCompanySelector = require('crm/selector/entity/company').CrmCompanySelector;
		CrmElementSelector = require('crm/selector/entity/element').CrmElementSelector;
		DocumentGeneratorTemplateSelector = require('crm/selector/documentgenerator/template').DocumentGeneratorTemplateSelector;
	}
	catch (e)
	{
		(new WarnLogger()).warn(e);
	}

	let TaskSelector = null;
	try
	{
		TaskSelector = require('tasks/selector/task').TaskSelector;
	}
	catch (e)
	{
		(new WarnLogger()).warn(e);
	}

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
		TASK_FLOW: 'task_flow',
		MAIL_CONTACT: 'mail_contact',
		IBLOCK_PROPERTY_ELEMENT: 'iblock-property-element',
		IBLOCK_PROPERTY_SECTION: 'iblock-property-section',
		IBLOCK_ELEMENT_USER_FIELD: 'iblock-element-user-field',
		IBLOCK_SECTION_USER_FIELD: 'iblock-section-user-field',
		DEPARTMENT: 'department',
		TASK: 'task',
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

			if (type === Type.TASK)
			{
				return TaskSelector.make(data);
			}

			if (type === Type.TASK_TAG)
			{
				return TaskTagSelector.make(data);
			}

			if (type === Type.TASK_FLOW && TaskFlowSelector)
			{
				return TaskFlowSelector.make(data);
			}

			if (type === Type.DEPARTMENT)
			{
				return DepartmentSelector.make(data);
			}

			if (type === Type.IBLOCK_PROPERTY_ELEMENT)
			{
				return IblockElementSelector.make(data);
			}

			if (type === Type.IBLOCK_PROPERTY_SECTION)
			{
				return IblockSectionSelector.make(data);
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

	module.exports = {
		EntitySelectorFactory,
		/** @type EntitySelectorFactory.Type */
		EntitySelectorFactoryType: Type,
	};
});

(() => {
	const require = (ext) => jn.require(ext);
	const { EntitySelectorFactory, EntitySelectorFactoryType } = require('selector/widget/factory');

	this.EntitySelectorFactory = EntitySelectorFactory;
	this.EntitySelectorFactory.Type = EntitySelectorFactoryType;
})();
