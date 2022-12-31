/**
 * @module layout/ui/entity-editor/control
 */
jn.define('layout/ui/entity-editor/control', (require, exports, module) => {

	const { FieldFactory, EntitySelectorType, UserType, CrmElementType } = require('layout/ui/fields');
	const { EntityEditorClient } = require('layout/ui/entity-editor/control/client');
	const { EntityEditorCombined } = require('layout/ui/entity-editor/control/combined');

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
	};

	/**
	 * @function EntityEditorControlFactory
	 */
	function EntityEditorControlFactory(props)
	{
		const { type } = props;

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
		else if (FieldFactory.has(type))
		{
			return new EntityEditorField(props);
		}

		return null;
	}

	module.exports = { EntityEditorControlFactory };

});