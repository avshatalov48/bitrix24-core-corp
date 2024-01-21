/**
 * @module tasks/layout/task/fields/files
 */
jn.define('tasks/layout/task/fields/files', (require, exports, module) => {
	const { Loc } = require('loc');
	const { FileField } = require('layout/ui/fields/file');

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

			this.handleOnChange = this.handleOnChange.bind(this);
			this.handleOnInnerRef = this.handleOnInnerRef.bind(this);
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

		handleOnChange(files)
		{
			const { onChange } = this.props;

			this.setState({ files });

			if (onChange)
			{
				onChange(files);
			}
		}

		handleOnInnerRef(ref)
		{
			const { onInnerRef } = this.props;

			if (onInnerRef)
			{
				onInnerRef(ref);
			}
		}

		render()
		{
			const { readOnly, files, showAddButton } = this.state;
			const {
				userId,
				taskId,
				onViewRef,
				isAlwaysShowed,
				parentWidget,
				style = {},
				deepMergeStyles = {},
			} = this.props;

			return View(
				{
					ref: (ref) => {
						if (onViewRef)
						{
							onViewRef(ref);
						}
					},
					style: {
						...style,
						height: isAlwaysShowed ? undefined : 0,
					},
				},
				FileField({
					ref: this.handleOnInnerRef,
					readOnly,
					showAddButton,
					showEditIcon: true,
					hasHiddenEmptyView: true,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_FILES'),
					multiple: true,
					value: files,
					config: {
						parentWidget,
						deepMergeStyles,
						controller: {
							endpoint: 'tasks.FileUploader.TaskController',
							options: {
								taskId,
							},
						},
						disk: {
							isDiskModuleInstalled: true,
							isWebDavModuleInstalled: true,
							fileAttachPath: `/mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId=${userId}`,
						},
					},
					testId: 'files',
					onChange: this.handleOnChange,
				}),
			);
		}
	}

	module.exports = { Files };
});
