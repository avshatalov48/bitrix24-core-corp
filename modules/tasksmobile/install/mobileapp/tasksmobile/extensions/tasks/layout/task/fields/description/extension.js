/**
 * @module tasks/layout/task/fields/description
 */
jn.define('tasks/layout/task/fields/description', (require, exports, module) => {
	const { Loc } = require('loc');
	const { inAppUrl } = require('in-app-url');
	const { ReadOnlyElementType } = require('layout/ui/fields/string');
	const { TextAreaField } = require('layout/ui/fields/textarea');

	class Description extends LayoutComponent
	{
		static openFileViewer({ fileType, url, name })
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

		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				description: props.description,
				parsedDescription: props.parsedDescription,
			};

			this.handleOnChange = this.handleOnChange.bind(this);
			this.handleOnLinkClick = this.handleOnLinkClick.bind(this);
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

		handleOnChange(text)
		{
			this.setState({ description: text });
			const { onChange } = this.props;

			if (onChange)
			{
				onChange(text);
			}
		}

		render()
		{
			return View(
				{
					style: (this.props.style || {}),
					onLongClick: (
						this.state.readOnly && Application.getPlatform() === 'android' && Application.getApiVersion() < 51
							? () => this.copyDescription()
							: () => {}
					),
				},
				TextAreaField({
					readOnly: this.state.readOnly,
					showTitle: false,
					placeholder: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_DESCRIPTION_PLACEHOLDER'),
					config: {
						deepMergeStyles: this.getDeepMergeStyles(),
						readOnlyElementType: ReadOnlyElementType.BB_CODE_TEXT,
						onLinkClick: this.handleOnLinkClick,
					},
					value: (this.state.readOnly ? this.state.parsedDescription : this.state.description),
					testId: 'description',
					onChange: this.handleOnChange,
				}),
			);
		}

		handleOnLinkClick({ url })
		{
			const files = this.props.task.files.reduce((accumulator, file) => {
				const result = accumulator;
				result[file.id] = file;
				result[`n${file.objectId}`] = file;

				return result;
			}, {});
			const fileMatch = url.match(/\/\?openFile&fileId=(\d+)/);
			if (fileMatch && files[fileMatch[1]])
			{
				const file = files[fileMatch[1]];
				file.fileType = UI.File.getType(UI.File.getFileMimeType(file.type, file.name));
				Description.openFileViewer(file);

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

			inAppUrl.open(url);
		}

		openWebViewer({ type, id })
		{
			PageManager.openPage({
				url: `${env.siteDir}mobile/tasks/snmrouter/?routePage=fragmentrenderer&FRAGMENT_TYPE=${type}&FRAGMENT_ID=${id}&TASK_ID=${this.props.task.id}`,
				title: Loc.getMessage(
					`TASKSMOBILE_LAYOUT_TASK_FIELDS_DESCRIPTION_CONTENT_${type.toUpperCase()}`,
					{ '#INDEX#': Number(id) },
				),
				backgroundColor: AppTheme.colors.bgSecondary,
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
			Notify.showMessage(
				'',
				Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_DESCRIPTION_COPIED'),
				{ time: 1 },
			);
			Application.copyToClipboard(this.state.description);
		}
	}

	module.exports = { Description };
});
