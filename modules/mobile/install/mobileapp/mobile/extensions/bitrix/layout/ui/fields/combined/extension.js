(() => {
	/**
	 * @class Fields.CombinedField
	 */
	class CombinedField extends LayoutComponent
	{
		getStyles()
		{
			return {
				...this.getDefaultStyles(),
				...(this.props.config && this.props.config.styles ? this.props.config.styles : {})
			};
		}

		render()
		{
			this.styles = this.getStyles();

			if (this.props.primaryField)
			{
				this.props.primaryField.setAdditionalStyles({
					wrapper: {...this.styles.primaryFieldWrapper},
					readOnlyWrapper: {...this.styles.primaryFieldWrapper},
					title: {...this.styles.primaryFieldTitle}
				});
			}

			if (this.props.secondaryField)
			{
				this.props.secondaryField.setAdditionalStyles({
					wrapper: {...this.styles.secondaryFieldWrapper},
					readOnlyWrapper: {...this.styles.secondaryFieldWrapper},
					title: {...this.styles.secondaryFieldTitle}
				});
			}

			return View(
				{
					style: this.styles.combinedContainer
				},
				this.props.primaryField && View(
					{
						style: this.styles.primaryFieldContainer
					},
					this.props.primaryField
				)
				,
				this.props.secondaryField && View(
					{
						style: this.styles.secondaryFieldContainer
					},
					View({
						style: {
							width: 0.5,
							backgroundColor: '#DBDDE0'
						}
					}),
					this.props.secondaryField
				)
			)
		}

		hasEditableFields()
		{
			return (
				(this.props.primaryField && !this.props.primaryField.isReadOnly())
				|| (this.props.secondaryField && !this.props.secondaryField.isReadOnly())
			);
		}

		focus()
		{
			if (this.props.primaryField && !this.props.primaryField.isReadOnly())
			{
				this.props.primaryField.focus();
			}
			else if (this.props.secondaryField && !this.props.secondaryField.isReadOnly())
			{
				this.props.secondaryField.focus();
			}
		}

		validate()
		{
			let result = true;

			if (this.props.primaryField)
			{
				result &= this.props.primaryField.validate();
			}

			if (this.props.secondaryField)
			{
				result &= this.props.secondaryField.validate();
			}

			return Boolean(result);
		}

		getDefaultStyles()
		{
			const hasEditableFields = this.hasEditableFields();

			return {
				combinedContainer: {
					flexWrap: 'wrap',
					justifyContent: 'center',
					alignItems: 'center',
					flexDirection: 'row'
				},
				primaryFieldContainer: {
					marginRight: 9,
					maxWidth: '100%',
					flex: 1
				},
				secondaryFieldContainer: {
					width: 111,
					flexDirection: 'row',
					paddingTop: 8,
					paddingBottom: hasEditableFields ? 13 : 12,
				},
				primaryFieldWrapper: {
					paddingBottom: hasEditableFields ? 13 : 12
				},
				primaryFieldTitle: {
					marginBottom: hasEditableFields ? 4 : 1
				},
				secondaryFieldWrapper: {
					paddingTop: 0,
					paddingBottom: 0,
					paddingLeft: 12,
					paddingRight: 12
				},
				secondaryFieldTitle: {
					marginBottom: hasEditableFields ? 4 : 1
				}
			};
		}
	}

	this.Fields = this.Fields || {};
	this.Fields.CombinedField = CombinedField;
})();