(() => {
	const Type = {
		COLUMN: 'column',
		SECTION: 'section',
		PRODUCT_ROW_SUMMARY: 'product_row_summary',
		ENTITY_SELECTOR: 'entity-selector',
		USER: 'user',
		MONEY: 'money',
		FILE: 'file'
	};

	/**
	 * @function EntityEditorControl
	 */
	function EntityEditorControl({ref, type, id, settings, data})
	{
		if (type === Type.COLUMN)
		{
			return new EntityEditorColumn({ref, id, settings, type});
		}
		else if (type === Type.SECTION)
		{
			return new EntityEditorSection({ref, id, settings, type});
		}
		else if (type === Type.PRODUCT_ROW_SUMMARY)
		{
			return new ProductSummarySection({ref, id, settings, type});
		}
		else if (type === Type.ENTITY_SELECTOR || type === Type.USER)
		{
			return new EntitySelectorField({ref, id, settings, type});
		}
		else if (type === Type.MONEY)
		{
			return new EntityEditorMoneyField({ref, id, settings, type});
		}
		else if (type === Type.FILE)
		{
			return new EntityEditorFileField({ref, id, settings, type});
		}
		else if (FieldFactory.has(type))
		{
			return new EntityEditorField({ref, id, settings, type, data});
		}

		return null;
	}

	jnexport(EntityEditorControl)
})();