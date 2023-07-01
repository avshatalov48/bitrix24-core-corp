/**
 * @module crm/entity-name
 */
jn.define('crm/entity-name', (require, exports, module) => {
	const { TextAreaField } = require('layout/ui/fields/textarea');

	class EntityName extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				name: BX.prop.getString(this.props, 'name', ''),
			};

			this.onChangeHandler = this.onChangeHandler.bind(this);

			/** @type {TextAreaField} */
			this.textAreaField = null;
		}

		get title()
		{
			return BX.prop.getString(this.props, 'title', '');
		}

		get placeholder()
		{
			return BX.prop.getString(this.props, 'placeholder', '');
		}

		get required()
		{
			return BX.prop.getBoolean(this.props, 'required', false);
		}

		get showRequired()
		{
			return BX.prop.getBoolean(this.props, 'showRequired', false);
		}

		get config()
		{
			return BX.prop.get(this.props, 'config', {});
		}

		focus()
		{
			if (this.textAreaField)
			{
				this.textAreaField.focus();
			}
		}

		render()
		{
			return View(
				{
					style: {
						paddingTop: 18,
						paddingBottom: 18,
						paddingLeft: 20,
						paddingRight: 20,
						borderRadius: 12,
						backgroundColor: '#fff',
					},
				},
				TextAreaField({
					ref: (ref) => this.textAreaField = ref,
					title: this.title,
					value: this.state.name,
					placeholder: this.placeholder,
					required: this.required,
					showRequired: this.showRequired,
					config: this.config,
					onChange: this.onChangeHandler,
				}),
			);
		}

		onChangeHandler(name)
		{
			this.setState({ name }, () => this.onAfterChange(name));
		}

		onAfterChange(name)
		{
			const { onChange } = this.props;

			if (onChange)
			{
				onChange(name);
			}
		}
	}

	module.exports = { EntityName };
});
