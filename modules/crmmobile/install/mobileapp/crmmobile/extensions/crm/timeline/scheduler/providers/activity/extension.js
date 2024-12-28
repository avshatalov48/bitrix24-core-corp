/**
 * @module crm/timeline/scheduler/providers/activity
 */
jn.define('crm/timeline/scheduler/providers/activity', (require, exports, module) => {
	include('InAppNotifier');

	const { Loc } = require('loc');
	const { Haptics } = require('haptics');
	const {
		dateTimeOutline: dateTimeOutlineSvg,
		clipOutline,
		clockOutline: clockOutlineSvg,
	} = require('assets/common');
	const { Icon } = require('assets/icons');
	const AppTheme = require('apptheme');
	const { TimelineSchedulerBaseProvider } = require('crm/timeline/scheduler/providers/base');
	const { ResponsibleSelector } = require('crm/timeline/services/responsible-selector');
	const { Toolbar, ToolbarIcon, ToolbarButton } = require('crm/timeline/ui/toolbar');
	const { WidgetHeaderButton } = require('layout/ui/widget-header-button');
	const { Textarea } = require('layout/ui/textarea');
	const { FileField } = require('layout/ui/fields/file');
	const { Moment } = require('utils/date');
	const { datetime } = require('utils/date/formats');
	const { WorkTimeMoment } = require('crm/work-time');
	const { get } = require('utils/object');
	const { ItemSelector } = require('layout/ui/item-selector');
	const { DatePill } = require('layout/ui/date-pill');
	const { debounce } = require('utils/function');
	const { Avatar } = require('ui-system/blocks/avatar');

	const INITIAL_HEIGHT = 1000;

	const isAndroid = Application.getPlatform() === 'android';

	/**
	 * @class TimelineSchedulerActivityProvider
	 */
	class TimelineSchedulerActivityProvider extends TimelineSchedulerBaseProvider
	{
		constructor(props)
		{
			super(props);

			this.state = {
				text: this.getInitialText(),
				files: [],
				user: props.user,
				maxHeight: INITIAL_HEIGHT,
				selectedReminders: this.entity.reminders?.selectedValues,
			};

			this.deadline = this.initDeadline();

			this.mounted = false;

			this.textInputRef = null;

			/** @type {FileField|null} */
			this.fileFieldRef = null;

			this.saveButton = new WidgetHeaderButton({
				widget: this.layout,
				text: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_ACTIVITY_SAVE'),
				loadingText: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_ACTIVITY_SAVE_PROGRESS'),
				disabled: !this.isSaveAllowed(),
				onClick: () => this.save(),
			});

			this.deadlineRef = null;
			/** @type {ToolbarButton|null} */
			this.createButtonRef = null;

			this.openResponsibleUserSelector = this.openResponsibleUserSelector.bind(this);
			this.updateResponsibleUser = this.updateResponsibleUser.bind(this);
			this.updateDeadline = this.updateDeadline.bind(this);
			this.onChangeSelectedReminders = this.onChangeSelectedReminders.bind(this);
			this.refreshSaveButtonDebounced = debounce(this.refreshSaveButton, 500, this);
		}

		componentDidMount()
		{
			super.componentDidMount();

			this.mounted = true;

			this.focus();
		}

		static getId()
		{
			return 'todo';
		}

		static getTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_ACTIVITY_TITLE');
		}

		static getMenuTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_ACTIVITY_MENU_FULL_TITLE');
		}

		static getMenuShortTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_ACTIVITY_MENU_TITLE');
		}

		static getMenuIcon()
		{
			return Icon.TASK;
		}

		static getDefaultPosition()
		{
			return 1;
		}

		/**
		 * @return {object}
		 */
		static getBackdropParams()
		{
			return {
				showOnTop: false,
				onlyMediumPosition: true,
				mediumPositionPercent: 80,
			};
		}

		static isSupported(context = {})
		{
			return true;
		}

		initDeadline()
		{
			const { scheduleTs } = this.context;
			let deadline;
			if (scheduleTs)
			{
				deadline = Moment.createFromTimestamp(scheduleTs);
			}
			else
			{
				const workTimeMoment = new WorkTimeMoment();
				deadline = workTimeMoment.getNextWorkingDay(3).moment;
				deadline = deadline.addHours(1).startOfHour;
			}

			return deadline;
		}

		getInitialText()
		{
			return '';
		}

		hasUploadingFiles()
		{
			if (!this.fileFieldRef)
			{
				return false;
			}

			return this.fileFieldRef.hasUploadingFiles();
		}

		isFilesFieldValid()
		{
			if (!this.fileFieldRef)
			{
				return true;
			}

			return this.fileFieldRef.validate();
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						flex: 1,
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
							maxHeight: this.state.maxHeight,
						},
						onLayout: ({ height }) => this.setMaxHeight(height),
					},
					this.renderTextField(),
					this.renderAttachments(),
					View(
						{
							style: {
								flexDirection: 'row',
								alignItems: 'center',
								paddingHorizontal: isAndroid ? 16 : 12,
								paddingBottom: 14,
							},
						},
						this.renderReminders(),
					),
					this.renderBottom(),
				),
				// this.renderToolbar(),
			);
		}

		setMaxHeight(height)
		{
			const { maxHeight } = this.state;
			const newMaxHeight = Math.ceil(Math.min(height, maxHeight));

			if (newMaxHeight < maxHeight)
			{
				this.setState({ maxHeight: newMaxHeight });
			}
		}

		renderTextField()
		{
			const isIOS = Application.getPlatform() === 'ios';

			return View(
				{
					style: {
						flex: 1,
						paddingLeft: isIOS ? 8 : 0,
					},
				},
				Textarea({
					testId: 'TimelineProviderActivityTextarea',
					ref: (ref) => {
						this.textInputRef = ref;
					},
					text: this.state.text,
					placeholder: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_ACTIVITY_PLACEHOLDER'),
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
						paddingHorizontal: isAndroid ? 16 : 12,
						display: 'none',
						paddingBottom: 12,
					},
				},
				FileField({
					testId: 'TimelineProviderActivityFileField',
					ref: (ref) => this.fileFieldRef = ref,
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
							endpoint: 'crm.FileUploader.TodoActivityUploaderController',
							options: {
								entityTypeId: this.entity.typeId,
								entityId: this.entity.id,
								activityId: null,
							},
						},
					},
					readOnly: false,
					onChange: (files) => {
						files = Array.isArray(files) ? files : [];
						this.setState({ files }, () => {
							this.refreshSaveButtonDebounced();
						});
					},
				}),
			);
		}

		renderReminders()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				View(
					{
						style: {
							marginRight: 4,
							justifyContent: 'center',
						},
					},
					Image(
						{
							tintColor: AppTheme.colors.base4,
							svg: {
								content: clockOutlineSvg(),
							},
							style: {
								width: 24,
								height: 24,
							},
						},
					),
				),
				new ItemSelector({
					fontSize: 14,
					imageSize: 14,
					value: this.entity.reminders?.selectedValues,
					ref: (ref) => {
						this.selectorRef = ref;
					},
					inline: true,
					valuesList: this.entity.reminders.valuesList,
					emptyState: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_REMINDERS_EMPTY'),
					layout: this.layout,
					selectorTitle: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_REMINDERS_TITLE_MSGVER_1'),
				}),
			);
		}

		onChangeSelectedReminders(selectedValues)
		{
			this.setState({ selectedReminders: selectedValues.map((item) => Number(item)) });
		}

		renderDeadline()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Image({
					tintColor: AppTheme.colors.base4,
					svg: {
						content: dateTimeOutlineSvg(),
					},
					style: {
						width: 14.67,
						height: 14.73,
						alignSelf: 'center',
					},
				}),
				View(
					{
						testId: 'TimelineProviderActivityDeadline',
						style: {
							flexDirection: 'row',
							justifyContent: 'center',
							alignItems: 'center',
							marginLeft: 9,
						},
					},
					new DatePill({
						textColor: AppTheme.colors.base3,
						backgroundColor: AppTheme.colors.bgContentTertiary,
						fontSize: 14,
						imageSize: 14,
						fontWeight: 500,
						isReadonly: false,
						value: this.deadline.timestamp,
						withTime: true,
						onChange: this.updateDeadline,
						ref: (ref) => {
							this.deadlineRef = ref;
						},
					}),
				),
			);
		}

		renderBottom()
		{
			if (!this.deadline)
			{
				return null;
			}

			return View(
				{
					style: {
						alignItems: 'center',
						flexDirection: 'row',
						justifyContent: 'space-between',
						paddingHorizontal: 16,
						paddingBottom: 14,
					},
				},
				this.renderDeadline(),
				View(
					{
						style: {
							flexDirection: 'row',
						},
					},
					this.renderAttachButton(),
					this.renderResponsibleButton(),
				),
			);
		}

		updateDeadline()
		{
			this.deadline = this.deadlineRef.getMoment();
		}

		renderMenuButton()
		{
			return null;
		}

		renderAttachButton()
		{
			const attachedFilesCount = this.state.files.length;

			return View(
				{
					style: {
						alignSelf: 'center',
						justifyContent: 'center',
						alignItems: 'center',
						paddingHorizontal: 8,
						paddingVertical: 4,
						flexDirection: 'row',
					},
					testId: 'TimelineProviderActivityAttachButton',
					onClick: () => {
						if (this.fileFieldRef)
						{
							if (attachedFilesCount === 0)
							{
								this.fileFieldRef.openFilePicker();
							}
							else
							{
								this.fileFieldRef.onOpenAttachmentList();
							}
						}
					},
				},
				View(
					{
						testId: 'TimelineProviderActivityAttachButtonCounter',
						style: {
							borderRadius: 500,
							backgroundColor: AppTheme.colors.accentBrandBlue,
							position: attachedFilesCount === 0 ? 'relative' : 'absolute',
							display: attachedFilesCount === 0 ? 'none' : 'flex',
							alignItems: 'center',
							justifyContent: 'center',
							minWidth: 16,
							height: 16,
							paddingHorizontal: 3,
							paddingVertical: 0,
							top: 0,
							right: -12,
							marginRight: 12,
						},
					},
					Text(
						{
							style: {
								color: AppTheme.colors.baseWhiteFixed,
								fontSize: 11,
								fontWeight: 500,
								textAlign: 'center',
							},
							text: attachedFilesCount.toString(),
						},
					),
				),
				Image({
					style: {
						width: 26,
						height: 27,
					},
					tintColor: AppTheme.colors.base3,
					resizeMode: 'contain',
					svg: {
						content: clipOutline(),
					},
				}),
			);
		}

		renderVerticalSeparator()
		{
			return View(
				{
					style: {
						width: 1,
						paddingVertical: 3,
						marginHorizontal: 4,
					},
				},
				View(
					{
						style: {
							backgroundColor: AppTheme.colors.bgSeparatorPrimary,
							flex: 1,
						},
					},
				),
			);
		}

		renderResponsibleButton()
		{
			const { user } = this.state;
			if (!user)
			{
				return null;
			}

			const { userId, title, imageUrl } = user;
			const testId = 'TimelineSchedulerActivityResponsibleUser';

			return Avatar({
				id: userId,
				testId,
				size: 26,
				name: title,
				uri: imageUrl,
				withRedux: true,
				style: {
					alignSelf: 'center',
					paddingHorizontal: 8,
					paddingVertical: 4,
				},
				onClick: this.openResponsibleUserSelector,
			});
		}

		openResponsibleUserSelector()
		{
			ResponsibleSelector.show({
				onSelectedUsers: this.updateResponsibleUser,
				onSelectorHidden: () => this.focus(),
				responsibleId: this.userId,
				layout: this.layout,
			});
		}

		updateResponsibleUser(selectedUsers)
		{
			const selectedUser = selectedUsers[0];

			/** @type {TimelineUserProps} */
			const user = {
				imageUrl: encodeURI(selectedUser.imageUrl),
				title: selectedUser.title,
				userId: selectedUser.id,
			};

			this.setState({ user });
		}

		get userId()
		{
			return get(this.state, 'user.userId', null);
		}

		renderToolbar()
		{
			return Toolbar({
				right: () => new ToolbarButton({
					ref: (ref) => {
						this.createButtonRef = ref;
					},
					text: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_ACTIVITY_SAVE'),
					loadingText: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_ACTIVITY_SAVE_PROGRESS'),
					disabled: !this.isSaveAllowed(),
					onClick: () => this.save(),
				}),
				center: () => View(
					{
						style: { flexDirection: 'row' },
					},
					ToolbarIcon({
						svg: clipOutline(),
						width: 17,
						height: 19,
						onClick: () => {
							if (this.fileFieldRef)
							{
								this.fileFieldRef.openFilePicker();
							}
							else
							{
								InAppNotifier.showNotification({
									title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_ATTACHMENTS_TITLE'),
									message: `${Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_ATTACHMENTS_BODY')} 😉`,
									time: 3,
									backgroundColor: AppTheme.colors.accentSoftElementBlue1,
									blur: true,
									code: 'attach_hint',
								});
							}
						},
					}),
					ToolbarIcon({
						svg: `<svg width="14" height="20" viewBox="0 0 14 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.8209 8.6302C13.4064 8.61776 13.8912 9.08233 13.9036 9.66785C13.9607 12.3582 11.9789 15.5172 8.13976 16.039L8.13894 17.4335L8.59835 17.4339C9.12608 17.4339 9.55389 17.8617 9.55389 18.3894C9.55389 18.9171 9.12608 19.3449 8.59835 19.3449H5.40111C4.87338 19.3449 4.44557 18.9171 4.44557 18.3894C4.44557 17.8617 4.87338 17.4339 5.40111 17.4339L5.85906 17.4335L5.85898 16.0381C2.02549 15.5205 0.0658277 12.4368 0.0956103 9.67891C0.101934 9.0933 0.581793 8.62369 1.1674 8.62989C1.71398 8.63592 2.15949 9.05432 2.21132 9.58621L2.2163 9.70181C2.21036 10.2519 2.51762 11.3083 3.08472 12.1298C3.8989 13.3093 5.15055 13.9929 7.01102 13.9929C8.86116 13.9929 10.1084 13.2962 10.924 12.0939C11.4448 11.3263 11.7482 10.351 11.7806 9.82579L11.7833 9.71288C11.7708 9.12737 12.2354 8.64263 12.8209 8.6302ZM6.99973 0.29834C8.59695 0.29834 9.89175 1.59314 9.89175 3.19036V9.28705C9.89175 10.8843 8.59695 12.1791 6.99973 12.1791C5.40252 12.1791 4.10772 10.8843 4.10772 9.28705V3.19036C4.10772 1.59314 5.40252 0.29834 6.99973 0.29834Z" fill="${AppTheme.colors.base6}"/></svg>`,
						width: 14,
						height: 20,
						onClick: () => {
							InAppNotifier.showNotification({
								title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_VOICE_NOTES_TITLE'),
								message: `${Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_VOICE_NOTES_BODY')} 😉`,
								time: 3,
								backgroundColor: AppTheme.colors.accentSoftElementBlue1,
								blur: true,
								code: 'voice_hint',
							});
						},
					}),
				),
			});
		}

		focus()
		{
			if (this.textInputRef)
			{
				this.textInputRef.focus();
			}
		}

		isSaveAllowed()
		{
			return this.state.text.length > 0 && !this.hasUploadingFiles() && this.isFilesFieldValid();
		}

		refreshSaveButton()
		{
			if (this.isSaveAllowed())
			{
				this.saveButton.enable();
			}
			else
			{
				this.saveButton.disable();
			}
		}

		save()
		{
			return new Promise((resolve, reject) => {
				const { text, files, user } = this.state;
				const { activityId } = this.context;

				let responsibleId = null;

				if (user && user.userId)
				{
					responsibleId = user.userId;
				}

				const data = {
					responsibleId,
					ownerTypeId: this.entity.typeId,
					ownerId: this.entity.id,
					description: text.trim(),
					deadline: this.deadlineRef?.getMoment().format(datetime()),
					fileTokens: files.map((file) => file.token).filter(Boolean),
					parentActivityId: activityId || null,
					pingOffsets: this.selectorRef.getSelectedValues(),
				};

				BX.ajax.runAction('crm.activity.todo.add', { data })
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
	}

	module.exports = { TimelineSchedulerActivityProvider };
});
