/**
 * @module crm/timeline/scheduler/providers/comment
 */
jn.define('crm/timeline/scheduler/providers/comment', (require, exports, module) => {
	include('InAppNotifier');

	const { Loc } = require('loc');
	const { Haptics } = require('haptics');
	const { clip } = require('assets/common');
	const AppTheme = require('apptheme');
	const { TimelineSchedulerBaseProvider } = require('crm/timeline/scheduler/providers/base');
	const {
		Toolbar,
		ToolbarIcon,
		ToolbarButton,
	} = require('crm/timeline/ui/toolbar');
	const { Icon } = require('assets/icons');
	const { Textarea } = require('layout/ui/textarea');
	const { FileField } = require('layout/ui/fields/file');
	const { EntitySelectorFactory } = require('selector/widget/factory');

	const isAndroid = Application.getPlatform() === 'android';

	/**
	 * @class TimelineSchedulerCommentProvider
	 */
	class TimelineSchedulerCommentProvider extends TimelineSchedulerBaseProvider
	{
		constructor(props)
		{
			super(props);

			this.state = {
				text: '',
				files: [],
			};

			this.textInputRef = null;

			/** @type {Fields.FileField|null} */
			this.fileFieldRef = null;

			/** @type {ToolbarButton|null} */
			this.createButtonRef = null;
		}

		static getId()
		{
			return 'comment';
		}

		static getTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_COMMENT_TITLE');
		}

		static getMenuTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_COMMENT_MENU_FULL_TITLE');
		}

		static getMenuShortTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_COMMENT_MENU_TITLE');
		}

		/**
		 * @returns {Icon}
		 */
		static getMenuIcon()
		{
			return Icon.MESSAGE;
		}

		static getDefaultPosition()
		{
			return 2;
		}

		static isSupported(context = {})
		{
			return true;
		}

		componentDidMount()
		{
			super.componentDidMount();

			this.focus();
		}

		hasUploadingFiles()
		{
			if (!this.fileFieldRef)
			{
				return false;
			}

			return this.fileFieldRef.hasUploadingFiles();
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'column',
					},
					resizableByKeyboard: true,
				},
				View(
					{
						style: {
							flex: 1,
							backgroundColor: AppTheme.colors.bgContentPrimary,
							borderTopLeftRadius: 12,
							borderTopRightRadius: 12,
						},
					},
					this.renderTextField(),
					this.renderAttachments(),
					this.renderToolbar(),
				),
			);
		}

		renderTextField()
		{
			return View(
				{
					style: {
						flex: 1,
						paddingLeft: isAndroid ? 0 : 8,
					},
				},
				Textarea({
					ref: (ref) => {
						this.textInputRef = ref;
					},
					text: this.state.text,
					placeholder: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_COMMENT_PLACEHOLDER_2'),
					onChange: (text) => {
						this.state.text = text;
						this.refreshSaveButton();
					},
				}),
			);
		}

		renderAttachments()
		{
			return View(
				{
					style: {
						paddingHorizontal: 20,
						display: this.state.files.length === 0 ? 'none' : 'flex',
						flexShrink: 1,
					},
				},
				FileField({
					ref: (ref) => {
						this.fileFieldRef = ref;
					},
					showTitle: false,
					showAddButton: false,
					hasHiddenEmptyView: true,
					multiple: true,
					value: [],
					config: {
						fileInfo: {},
						mediaType: 'file',
						parentWidget: this.layout,
						controller: {
							endpoint: 'crm.FileUploader.CommentUploaderController',
							options: {
								entityTypeId: this.entity.typeId,
								entityId: this.entity.id,
							},
						},
					},
					readOnly: false,
					onChange: (files) => {
						files = Array.isArray(files) ? files : [];
						this.setState({ files }, () => this.refreshSaveButton());
					},
				}),
			);
		}

		renderToolbar()
		{
			return Toolbar({
				right: () => new ToolbarButton({
					ref: (ref) => {
						this.createButtonRef = ref;
					},
					text: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_COMMENT_CREATE'),
					loadingText: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_COMMENT_CREATE_PROGRESS'),
					disabled: !this.isSaveAllowed(),
					onClick: () => this.save(),
				}),
				center: () => View(
					{
						style: { flexDirection: 'row' },
					},
					ToolbarIcon({
						tintColor: AppTheme.colors.base5,
						svg: clip,
						width: 17,
						height: 19,
						onClick: () => this.fileFieldRef && this.fileFieldRef.openFilePicker(),
					}),
					ToolbarIcon({
						tintColor: AppTheme.colors.base5,
						svg: `<svg width="18" height="19" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.16738 19C6.45494 19 4.24893 18.1952 2.54936 16.5856C0.849785 14.9586 0 12.7104 0 9.84116C0 6.98941 0.909871 4.63628 2.72961 2.78177C4.54936 0.927256 6.78112 0 9.42489 0C11.9828 0 14.0515 0.76105 15.6309 2.28315C17.2103 3.80525 18 5.67726 18 7.89917C18 9.75368 17.5193 11.3108 16.5579 12.5704C15.5966 13.8126 14.4549 14.4337 13.133 14.4337C12.6695 14.4337 12.2575 14.2937 11.897 14.0138C11.5365 13.7339 11.3219 13.3577 11.2532 12.8854C10.5665 13.9876 9.56223 14.5387 8.24034 14.5387C7.14163 14.5387 6.24893 14.1275 5.56223 13.3052C4.87554 12.483 4.53219 11.3982 4.53219 10.0511C4.53219 8.66897 4.96996 7.41805 5.84549 6.29834C6.7382 5.17864 7.85408 4.61878 9.19313 4.61878C10.3777 4.61878 11.2275 5.12615 11.7425 6.14088L11.9485 4.95994H14.2403L13.4163 9.39503C13.176 10.7072 13.0558 11.512 13.0558 11.8094C13.0558 12.2468 13.2017 12.4655 13.4936 12.4655C14.0773 12.4655 14.6094 12.0106 15.0901 11.1008C15.588 10.1911 15.8369 9.1326 15.8369 7.92541C15.8369 6.26335 15.2704 4.88996 14.1373 3.80525C13.0043 2.70304 11.4678 2.15193 9.5279 2.15193C7.43348 2.15193 5.71674 2.89549 4.37768 4.3826C3.03863 5.85221 2.3691 7.68048 2.3691 9.8674C2.3691 12.0718 2.97854 13.7864 4.19742 15.011C5.41631 16.2357 7.1073 16.8481 9.27039 16.8481C10.3863 16.8481 11.4421 16.7256 12.4378 16.4807L11.897 18.8163C11.1245 18.9388 10.2146 19 9.16738 19ZM8.62661 12.2818C9.36481 12.2818 9.96566 11.9494 10.4292 11.2845C10.9099 10.6022 11.1502 9.85865 11.1502 9.05387C11.1502 8.38904 10.9957 7.86418 10.6867 7.47928C10.3777 7.07689 9.9485 6.87569 9.39914 6.87569C8.66094 6.87569 8.0515 7.19061 7.57081 7.82044C7.09013 8.45027 6.84978 9.20258 6.84978 10.0773C6.84978 10.7422 7.00429 11.2758 7.3133 11.6782C7.63948 12.0806 8.07725 12.2818 8.62661 12.2818Z" fill="${AppTheme.colors.base5}"/></svg>`,
						width: 18,
						height: 19,
						onClick: () => this.openUserSelector(),
					}),
				),
			});
		}

		openUserSelector()
		{
			const selector = EntitySelectorFactory.createByType(EntitySelectorFactory.Type.USER, {
				provider: {},
				createOptions: {
					enableCreation: false,
				},
				allowMultipleSelection: false,
				closeOnSelect: true,
				events: {
					onClose: (selectedUsers) => this.insertMention(selectedUsers),
				},
				widgetParams: {
					title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_MENTION_USER_TITLE'),
					backgroundColor: AppTheme.colors.bgSecondary,
					backdrop: {
						mediumPositionPercent: 70,
						horizontalSwipeAllowed: false,
						navigationBarColor: AppTheme.colors.bgSecondary,
					},
				},
			});

			return selector.show({}, this.layout);
		}

		insertMention(users)
		{
			if (Array.isArray(users) && users.length > 0)
			{
				const { id, title } = users[0];
				const mention = `[USER=${id}]${title}[/USER]`;

				const text = this.endsWithWhitespace(this.state.text)
					? `${this.state.text}${mention} `.trimStart()
					: `${this.state.text} ${mention} `.trimStart()
				;

				this.setState({ text }, () => {
					this.setCursorPosition(text.length);
					setTimeout(() => {
						this.focus();
					}, 500);
				});
			}
			else
			{
				this.focus();
			}
		}

		endsWithWhitespace(str)
		{
			const trimmed = str.trimEnd();

			return trimmed !== str;
		}

		focus()
		{
			if (this.textInputRef)
			{
				this.textInputRef.focus();
			}
		}

		setCursorPosition(pos)
		{
			if (this.textInputRef)
			{
				this.textInputRef.setSelection(pos, pos);
			}
		}

		isSaveAllowed()
		{
			return this.state.text.length > 0 && !this.hasUploadingFiles();
		}

		refreshSaveButton()
		{
			if (!this.createButtonRef)
			{
				return;
			}

			if (this.isSaveAllowed())
			{
				this.createButtonRef.enable();
			}
			else
			{
				this.createButtonRef.disable();
			}
		}

		save()
		{
			return new Promise((resolve, reject) => {
				const data = {
					fields: {
						ENTITY_TYPE_ID: this.entity.typeId,
						ENTITY_ID: this.entity.id,
						COMMENT: this.comment,
						// AUTHOR_ID:
						FILES: this.files,
					},
				};

				BX.ajax.runAction('crm.timeline.comment.add', { data })
					.then((response) => {
						resolve(response);
						Haptics.notifySuccess();
						this.onActivityCreate(response);
						this.close();
					})
					.catch((response) => {
						void ErrorNotifier.showError(response.errors[0].message);
						reject(response);
					});
			});
		}

		get comment()
		{
			const { text } = this.state;
			const regExp = new RegExp(
				`\\[URL=data:\\/\\/user=([0-9]+)]\\[COLOR=${AppTheme.colors.accentMainLinks}]\\[B](.*)\\[\\/B]\\[\\/COLOR]\\[\\/URL]`,
				'gm',
			);

			return text.replaceAll(
				regExp,
				'[USER=$1]$2[/USER]',
			);
		}

		get files()
		{
			return this.state.files.map((file) => file.token);
		}
	}

	module.exports = { TimelineSchedulerCommentProvider };
});
