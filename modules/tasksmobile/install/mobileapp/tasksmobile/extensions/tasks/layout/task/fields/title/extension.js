/**
 * @module tasks/layout/task/fields/title
 */
jn.define('tasks/layout/task/fields/title', (require, exports, module) => {
	const {Loc} = require('loc');
	const {TextAreaField} = require('layout/ui/fields/textarea');

	class Title extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				title: props.title,
			};
			this.flag = false;
		}

		componentDidUpdate(prevProps, prevState)
		{
			//temporary fix for bug with auto height
			if (!this.flag) {
				this.flag = true;
				this.setState({});
			}
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

		render()
		{
			return View(
				{
					ref: ref => (this.props.onViewRef && this.props.onViewRef(ref)),
					style: (this.props.style || {}),
				},
				TextAreaField({
					readOnly: this.state.readOnly,
					required: true,
					focus: this.props.focus,
					showTitle: false,
					placeholder: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_TITLE_PLACEHOLDER'),
					config: {
						showAll: true,
						deepMergeStyles: {
							...this.props.deepMergeStyles,
							editableValue: {
								fontSize: 17,
								fontWeight: '700',
							},
						},
						onSubmitEditing: () => {},
					},
					value: this.state.title,
					testId: 'title',
					onChange: (text) => {
						this.setState({title: text});
						this.props.onChange(text);
					},
				}),
			);
		}
	}

	module.exports = {Title};
});