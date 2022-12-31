/**
 * @module layout/ui/fields/textarea
 */
jn.define('layout/ui/fields/textarea', (require, exports, module) => {
	const { StringFieldClass } = require('layout/ui/fields/string');

	/**
	 * @class TextAreaField
	 */
	class TextAreaField extends StringFieldClass
	{
		constructor(props)
		{
			super(props);
			this.state.showAll = this.getValue().length <= 180;
			this.state.height = this.state.focus ? 20 : 1;
		}

		componentDidMount() {
			super.componentDidMount();
			this.initialValue = this.getValue();
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				editableValue: {
					...styles.editableValue,
					flex: 1,
					height: this.getFieldHeight(),
					minHeight: this.state.height ? 20 : 1,
					maxHeight: this.state.showAll ? null : 88,
				},
			};
		}

		getFieldHeight()
		{
			if (Application.getPlatform() === 'ios')
			{
				return this.initialValue !== '' && this.initialValue === this.getValue() ? 'auto' : this.state.height
			}

			return 'auto';
		}

		getEllipsizeParams()
		{
			return this.getConfig().ellipsize ? {
				numberOfLines: 4,
				ellipsize: 'end',
			} : null;
		}

		renderEditableContent()
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'column',
						minHeight: this.state.height ? 20 : 1,
						height: this.getFieldHeight(),
					}
				},
				View(
					{
						style: {
							flexDirection: 'row',
							flexGrow: 2,
						},
						interactable: Application.getPlatform() !== 'ios' && this.state.focus,
					},
					TextInput(this.getFieldInputProps()),
				),
				this.renderShowAllButton(1),
				this.renderHideButton(),
			);
		}

		getFieldInputProps()
		{
			return {
				...super.getFieldInputProps(),
				enable: !(Application.getPlatform() === 'ios' && !this.state.focus),
				multiline: (this.props.multiline || true),
				onSubmitEditing: this.getConfig().onSubmitEditing,
				onContentSizeChange: ({height}) => setTimeout(() => this.resizeContent(height), 50),
			};
		}

		resizeContent(height)
		{
			if (this.state.showAll || !this.state.showAll && !this.state.focus)
			{
				this.setState({height})
			}
		}
	}

	module.exports = {
		TextAreaType: 'textarea',
		TextAreaField: (props) => new TextAreaField(props),
	};
});
