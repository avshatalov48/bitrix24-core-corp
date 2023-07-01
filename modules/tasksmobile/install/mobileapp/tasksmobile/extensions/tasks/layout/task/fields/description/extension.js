/**
 * @module tasks/layout/task/fields/description
 */
jn.define('tasks/layout/task/fields/description', (require, exports, module) => {
	const {Loc} = require('loc');
	const {TextAreaField} = require('layout/ui/fields/textarea');
	const {inAppUrl} = require('in-app-url');

	class Description extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				description: props.description,
				parsedDescription: props.parsedDescription,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				description: props.description,
				parsedDescription: props.parsedDescription,
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				description: newState.description,
				parsedDescription: newState.parsedDescription,
			});
		}

		getDeepMergeStyles()
		{
			return {
				...this.props.deepMergeStyles,
				externalWrapper: {
					...this.props.deepMergeStyles.externalWrapper,
					marginHorizontal: 10,
				},
				editableValue: {
					minHeight: 82,
				},
			};
		}

		render()
		{
			return View(
				{
					style: (this.props.style || {}),
					onLongClick: (this.state.readOnly ? () => this.copyDescription() : () => {}),
				},
				TextAreaField({
					readOnly: this.state.readOnly,
					showTitle: false,
					placeholder: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_DESCRIPTION_PLACEHOLDER'),
					config: {
						deepMergeStyles: this.getDeepMergeStyles(),
						readOnlyElementType: 'BBCodeText',
						onLinkClick: ({url}) => this.onLinkClick(url),
					},
					value: (this.state.readOnly ? this.state.parsedDescription : this.state.description),
					testId: 'description',
					onChange: (text) => {
						this.setState({description: text});
						this.props.onChange(text);
					},
				}),
			);
		}

		onLinkClick(url)
		{
			const files = this.props.task.files.reduce((result, file) => {
				result[file.id] = file;
				result[`n${file.objectId}`] = file;
				return result;
			}, {});
			const fileMatch = url.match(/\/\?openFile&fileId=(\d+)/);
			if (fileMatch && files[fileMatch[1]])
			{
				const file = files[fileMatch[1]];
				file.fileType = UI.File.getType(UI.File.getFileMimeType(file.type, file.name));
				this.openFileViewer(file);

				return;
			}

			const webMatch = url.match(/\/\?openWeb&type=(table|video)&id=(\d+)/);
			if (webMatch)
			{
				this.openWebViewer({
					type: webMatch[1],
					id: webMatch[2],
				});

				return;
			}

			const diskMatch = url.match(/\/bitrix\/tools\/disk\/focus.php\?.*(folderId|objectId)=(\d+)/i);
			if (diskMatch)
			{
				BX.postComponentEvent('onDiskFolderOpen', [{folderId: diskMatch[2]}], 'background');

				return;
			}

			inAppUrl.open(url);
		}

		openFileViewer({fileType, url, name})
		{
			if (!url)
			{
				return;
			}

			switch (fileType)
			{
				case 'video':
					viewer.openVideo(url);
					break;

				case 'image':
					viewer.openImage(url, name);
					break;

				default:
					viewer.openDocument(url, name);
					break;
			}
		}

		openWebViewer({type, id})
		{
			PageManager.openPage({
				url: `${env.siteDir}mobile/tasks/snmrouter/?routePage=fragmentrenderer&FRAGMENT_TYPE=${type}&FRAGMENT_ID=${id}&TASK_ID=${this.props.task.id}`,
				title: Loc.getMessage(
					`TASKSMOBILE_LAYOUT_TASK_FIELDS_DESCRIPTION_CONTENT_${type.toUpperCase()}`,
					{'#INDEX#': Number(id)}
				),
				backdrop: {
					bounceEnable: false,
					swipeAllowed: false,
					showOnTop: true,
					hideNavigationBar: false,
					horizontalSwipeAllowed: false,
				},
			});
		}

		copyDescription()
		{
			Application.copyToClipboard(this.state.description);
			Notify.showIndicatorSuccess({
				text: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_DESCRIPTION_COPIED'),
				hideAfter: 1200,
			});
		}
	}

	module.exports = {Description};
});