/**
 * @module tasks/layout/task/fields/title
 */
jn.define('tasks/layout/task/fields/title', (require, exports, module) => {
	const { Loc } = require('loc');
	const { ReadOnlyElementType } = require('layout/ui/fields/string');
	const { TextAreaField } = require('layout/ui/fields/textarea');
	const { copyToClipboard } = require('utils/copy');

	class Title extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				title: props.title,
			};

			this.handleOnChange = this.handleOnChange.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				title: props.title,
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				title: newState.title,
			});
		}

		handleOnChange(text)
		{
			const { onChange } = this.props;
			this.setState({ title: text });

			if (onChange)
			{
				onChange(text);
			}
		}

		render()
		{
			const { onViewRef, focus, onLayout, style = {}, deepMergeStyles = {} } = this.props;
			const { readOnly, title } = this.state;
			const valueStyle = {
				fontSize: 18,
				fontWeight: '400',
			};

			return View(
				{
					ref: (ref) => {
						if (ref && onViewRef)
						{
							onViewRef(ref);
						}
					},
					style,
					onLayout,
				},
				TextAreaField({
					focus,
					readOnly,
					required: true,
					showTitle: false,
					placeholder: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_TITLE_PLACEHOLDER'),
					config: {
						showAll: true,
						deepMergeStyles: {
							...deepMergeStyles,
							value: valueStyle,
							editableValue: valueStyle,
						},
						readOnlyElementType: ReadOnlyElementType.TEXT_INPUT,
					},
					value: title,
					testId: 'title',
					onChange: this.handleOnChange,
				}),
			);
		}

		copyTitle()
		{
			copyToClipboard(this.state.title, Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_TITLE_COPIED'));
		}
	}

	module.exports = { Title };
});
