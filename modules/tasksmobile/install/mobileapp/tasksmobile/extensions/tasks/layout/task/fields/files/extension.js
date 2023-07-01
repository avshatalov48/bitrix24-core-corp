/**
 * @module tasks/layout/task/fields/files
 */
jn.define('tasks/layout/task/fields/files', (require, exports, module) => {
	const {Loc} = require('loc');
	const {FileField} = require('layout/ui/fields/file');

	class Files extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				files: props.files,
				showAddButton: props.showAddButton,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				files: props.files,
				showAddButton: props.showAddButton,
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				files: newState.files,
				showAddButton: newState.showAddButton,
			});
		}

		render()
		{
			return View(
				{
					style: {
						...(this.props.style || {}),
						height: (this.props.isAlwaysShowed ? undefined : 0),
					},
					ref: ref => (this.props.onViewRef && this.props.onViewRef(ref)),
				},
				FileField({
					ref: ref => this.props.onInnerRef(ref),
					readOnly: this.state.readOnly,
					showEditIcon: true,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_FILES'),
					showAddButton: this.state.showAddButton,
					multiple: true,
					value: this.state.files,
					config: {
						deepMergeStyles: this.props.deepMergeStyles,
						controller: {
							endpoint: 'tasks.FileUploader.TaskController',
							options: {
								taskId: this.props.taskId,
							},
						},
						disk: {
							isDiskModuleInstalled: true,
							isWebDavModuleInstalled: true,
							fileAttachPath: `/mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId=${this.props.userId}`,
						},
						parentWidget: this.props.parentWidget,
						emptyEditableButtonStyle: {
							borderColor: '#c9ccd0',
							backgroundColor: '#ffffff',
							iconColor: '#bdc1c6',
							textColor: '#a8adb4',
						},
					},
					testId: 'files',
					onChange: (files) => {
						this.setState({files});
						this.props.onChange(files);
					},
				}),
			);
		}
	}

	module.exports = {Files};
});