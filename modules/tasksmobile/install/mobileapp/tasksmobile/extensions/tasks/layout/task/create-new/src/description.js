/**
 * @module tasks/layout/task/create-new/description
 */
jn.define('tasks/layout/task/create-new/description', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Loc } = require('loc');
	const { TaskField } = require('tasks/enum');
	const { TextEditor } = require('text-editor');
	const { PlainTextFormatter } = require('bbcode/formatter/plain-text-formatter');

	class Description extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				description: props.description,
				files: props.files,
			};

			this.onLayout = this.onLayout.bind(this);
			this.onChange = this.onChange.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				description: props.description,
				files: props.files,
			};
		}

		render()
		{
			return View(
				{
					testId: `${TaskField.DESCRIPTION}_FIELD`,
					style: this.props.style,
					onLayout: this.onLayout,
					onClick: () => {
						void TextEditor.edit({
							title: Loc.getMessage('TASKSMOBILE_TASK_CREATE_FIELD_DESCRIPTION_EDITOR_TITLE'),
							value: this.state.description,
							parentWidget: this.props.parentWidget,
							allowFiles: false,
							closeOnSave: true,
							onSave: ({ bbcode }) => this.onChange(bbcode),
						});
					},
				},
				BBCodeText({
					testId: `${TaskField.DESCRIPTION}_CONTENT`,
					style: {
						fontSize: 14,
						fontWeight: '400',
						color: Color.base2.toHex(),
					},
					ellipsize: 'end',
					numberOfLines: 4,
					value: this.getTextToShow(),
				}),
			);
		}

		onLayout(params)
		{
			if (this.props.onLayout)
			{
				this.props.onLayout(params);
			}
		}

		onChange(description)
		{
			if (this.props.onChange)
			{
				this.props.onChange(description);
			}
			this.setState({ description });
		}

		getTextToShow()
		{
			const { description } = this.state;

			if (description.length === 0)
			{
				const text = Loc.getMessage('TASKSMOBILE_TASK_CREATE_FIELD_DESCRIPTION_PLACEHOLDER');

				return `[color=${Color.base5}]${text}[/color]`;
			}

			const plaintTextFormatter = new PlainTextFormatter();
			const plainAst = plaintTextFormatter.format({ source: description });

			return plainAst.toString();
		}
	}

	module.exports = { Description };
});
