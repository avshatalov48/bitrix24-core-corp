/**
 * @module layout/ui/entity-editor/control
 */
jn.define('layout/ui/entity-editor/control', (require, exports, module) => {

	const { FieldFactory, EntitySelectorType, UserType, CrmElementType } = require('layout/ui/fields');
	const { EntityEditorField } = require('layout/ui/entity-editor/control/field');
	const { EntityEditorClient } = require('layout/ui/entity-editor/control/client');
	const { EntityEditorCombined } = require('layout/ui/entity-editor/control/combined');
	const { EntityEditorColumn } = require('layout/ui/entity-editor/control/column');
	const { EntityEditorSection } = require('layout/ui/entity-editor/control/section');
	const { EntitySelectorField } = require('layout/ui/entity-editor/control/entity-selector');
	const { EntityEditorFileField } = require('layout/ui/entity-editor/control/file');
	const { EntityEditorOpportunityField } = require('layout/ui/entity-editor/control/opportunity');
	const { ProductSummarySection } = require('layout/ui/entity-editor/control/product-summary-section');
	const { RequisiteField } = require('layout/ui/entity-editor/control/requisite');
	const { RequisiteAddressField } = require('layout/ui/entity-editor/control/requisite-address');
	const { EntityEditorAddressField } = require('layout/ui/entity-editor/control/address');

	const Type = {
		COLUMN: 'column',
		SECTION: 'section',
		PRODUCT_ROW_SUMMARY: 'product_row_summary',
		ENTITY_SELECTOR: 'entity-selector',
		USER: 'user',
		OPPORTUNITY: 'opportunity',
		FILE: 'file',
		CLIENT: 'client_light',
		COMBINED: 'combined',
		REQUISITE: 'requisite',
		REQUISITE_ADDRESS: 'requisite_address',
		ADDRESS: 'address',
	};

	/**
	 * @class EntityEditorControlFactory
	 */
	class EntityEditorControlFactory
	{
		static has(type)
		{
			const hasInTypes = Object.values(Type).includes(type);
			if (hasInTypes)
			{
				return true;
			}

			return FieldFactory.has(type);
		}

		static create(props)
		{
			const { type } = props;

			if (!EntityEditorControlFactory.has(type))
			{
				return null;
			}

			if (type === Type.COLUMN)
			{
				return new EntityEditorColumn(props);
			}
			else if (type === Type.SECTION)
			{
				return new EntityEditorSection(props);
			}
			else if (type === Type.PRODUCT_ROW_SUMMARY)
			{
				return new ProductSummarySection(props);
			}
			else if (
				type === EntitySelectorType
				|| type === UserType
				|| type === CrmElementType
			)
			{
				return new EntitySelectorField(props);
			}
			else if (type === Type.OPPORTUNITY)
			{
				return new EntityEditorOpportunityField(props);
			}
			else if (type === Type.FILE)
			{
				return new EntityEditorFileField(props);
			}
			else if (type === Type.CLIENT && EntityEditorClient)
			{
				return new EntityEditorClient(props);
			}
			else if (type === Type.COMBINED)
			{
				return new EntityEditorCombined(props);
			}
			else if (type === Type.REQUISITE)
			{
				return new RequisiteField(props);
			}
			else if (type === Type.REQUISITE_ADDRESS)
			{
				return new RequisiteAddressField(props);
			}
			else if (type === Type.ADDRESS)
			{
				return new EntityEditorAddressField(props);
			}
			else if (FieldFactory.has(type))
			{
				return new EntityEditorField(props);
			}

			return null;
		}
	}

	module.exports = { EntityEditorControlFactory };
});