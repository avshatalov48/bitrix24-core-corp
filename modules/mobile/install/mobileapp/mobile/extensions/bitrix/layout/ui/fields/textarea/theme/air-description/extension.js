/**
 * @module layout/ui/fields/textarea/theme/air-description
 */
jn.define('layout/ui/fields/textarea/theme/air-description', (require, exports, module) => {
	const { Color, Indent, Typography } = require('tokens');
	const { withTheme } = require('layout/ui/fields/theme');
	const { TextAreaFieldClass } = require('layout/ui/fields/textarea');
	const { CollapsibleText } = require('layout/ui/collapsible-text');
	const { EditableTextBlock } = require('layout/ui/editable-text-block');
	const { stringify } = require('utils/string');
	const { PlainTextFormatter } = require('bbcode/formatter/plain-text-formatter');

	const descriptionStyle = (isEmpty = false) => ({
		...Typography.getTokenBySize({ size: 4 })?.getStyle(),
		color: isEmpty ? Color.base5.toHex() : Color.base2.toHex(),
	});

	class AirTheme extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				value: stringify(this.props.field.getValue()),
			};

			this.onSave = this.onSave.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				value: stringify(this.props.field.getValue()),
			};
		}

		/**
		 * @private
		 * @param {string} value
		 * @param {array} files
		 */
		onSave(value, files)
		{
			// eslint-disable-next-line no-param-reassign
			value = stringify(value);

			this.field.debouncedChangeValues(value, files);

			this.setState({ value });
		}

		/**
		 * @return {TextAreaField}
		 */
		get field()
		{
			return this.props.field;
		}

		/**
		 * @return {string}
		 */
		get value()
		{
			return this.state.value;
		}

		render()
		{
			const plainTextFormatter = new PlainTextFormatter();
			const plainAst = plainTextFormatter.format({
				source: this.value,
				data: {
					files: this.field.getConfig().fileField?.value ?? [],
				},
			});

			return View(
				{
					testId: `${this.field.testId}_FIELD`,
					ref: this.field.bindContainerRef,
				},
				this.field.isReadOnly()
					? new CollapsibleText({
						value: plainAst.toString(),
						style: descriptionStyle(),
						containerStyle: {
							marginTop: (this.field.isEmpty() ? 0 : Indent.XL3.toNumber()),
						},
						bbCodeMode: false,
						useBBCodeEditor: true,
						onClick: () => this.field.openBBCodeTextEditor(this.value),
						onLongClick: () => this.field.openBBCodeTextEditor(this.value),
						onLinkClick: () => this.field.openBBCodeTextEditor(this.value),
						testId: `${this.field.testId}_CONTENT`,
					})
					: new EditableTextBlock({
						value: this.value,
						placeholder: this.field.getTitleText(),
						onSave: this.onSave,
						textProps: {
							testId: `${this.field.testId}_CONTENT`,
							style: descriptionStyle(this.field.isEmpty()),
							bbCodeMode: false,
							moreButtonColor: Color.accentMainPrimary,
						},
						editorProps: {
							placeholder: this.field.getPlaceholder(),
							title: this.field.getTitleText(),
							textAreaStyle: descriptionStyle(),
							useBBCodeEditor: true,
							bbCodeEditorParams: this.field.getBBCodeTextEditorParams(this.value),
						},
						externalStyles: {
							paddingLeft: 0,
							paddingRight: 0,
							paddingBottom: 0,
							paddingTop: Indent.XL3.toNumber(),
							borderWidth: 0,
							borderRadius: 0,
						},
						showEditIcon: false,
					})
				,
			);
		}
	}

	/**
	 * @type {function(Object): Object}
	 */
	const TextAreaField = withTheme(
		TextAreaFieldClass,
		({ field }) => new AirTheme({ field }),
	);

	module.exports = {
		AirTheme,
		TextAreaField,
	};
});
