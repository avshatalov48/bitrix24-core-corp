/**
 * @module calendar/layout/fields/select-field
 */
jn.define('calendar/layout/fields/select-field', (require, exports, module) => {
	const AppTheme = require('apptheme');

	/**
	 * @class SelectField
	 */
	class SelectField extends LayoutComponent
	{
		get item()
		{
			return this.props.currentItem;
		}

		get dialogTitle()
		{
			return String(this.props.title);
		}

		get dialogItems()
		{
			return this.props.items.map((item) => {
				return {
					value: String(item.value),
					name: String(item.name),
					selectedName: String(item.name),
				};
			});
		}

		get style()
		{
			return this.props.style ?? {};
		}

		render()
		{
			return View(
				{
					style: {
						borderRadius: 15,
						backgroundColor: AppTheme.colors.accentSoftBlue3,
						paddingVertical: 5,
						paddingHorizontal: 15,
						alignItems: 'center',
						...(this.style.field || {}),
					},
					clickable: true,
					onClick: this.onFieldClickHandler.bind(this),
				},
				this.props.renderValue && this.props.renderValue(),
				!this.props.renderValue && Text(
					{
						style: {
							fontSize: 15,
							color: AppTheme.colors.accentMainLinks,
							...(this.style.text || {}),
						},
						text: this.item.name,
					},
				),
			);
		}

		onFieldClickHandler()
		{
			dialogs.showPicker({
				title: this.dialogTitle,
				items: this.dialogItems,
				defaultValue: this.item.value,
			}, (event, item) => {
				if (event === 'onPick')
				{
					this.props.onChange(item.value);
				}
			});
		}
	}

	module.exports = { SelectField };
});
