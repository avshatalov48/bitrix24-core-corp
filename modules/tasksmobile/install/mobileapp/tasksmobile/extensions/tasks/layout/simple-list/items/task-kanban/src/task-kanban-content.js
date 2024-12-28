/**
 * @module tasks/layout/simple-list/items/task-kanban/src/task-kanban-content
 */
jn.define('tasks/layout/simple-list/items/task-kanban/src/task-kanban-content', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');
	const { CounterView } = require('layout/ui/counter-view');
	const { FileField } = require('layout/ui/fields/file');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { connect } = require('statemanager/redux/connect');
	const store = require('statemanager/redux/store');
	const { dispatch } = store;
	const { usersSelector } = require('statemanager/redux/slices/users');
	const { DeadlinePill } = require('tasks/layout/deadline-pill');
	const {
		selectByTaskIdOrGuid,
		selectCounter,
		selectIsCompleted,
		selectActions,
		updateDeadline,
	} = require('tasks/statemanager/redux/slices/tasks');
	const {
		getUniqId,
		selectStages: selectStageIds,
	} = require('tasks/statemanager/redux/slices/kanban-settings');
	const { selectById: selectStageById } = require('tasks/statemanager/redux/slices/stage-settings');
	const { selectTaskStageByTaskIdOrGuid, setTaskStage } = require('tasks/statemanager/redux/slices/tasks-stages');
	const { selectGroupById } = require('tasks/statemanager/redux/slices/groups');
	const { get } = require('utils/object');
	const { Moment } = require('utils/date');
	const { date, shortTime } = require('utils/date/formats');
	const { TextField, IconField } = require('tasks/layout/simple-list/items/task-kanban/src/field');
	const { Loc } = require('tasks/loc');
	const { TasksStageSelector } = require('tasks/layout/stage-selector');
	const { Crm } = require('tasks/layout/task/fields/crm');
	const { Mark } = require('tasks/layout/task/fields/mark');
	const { Tags } = require('tasks/layout/task/fields/tags');
	const { Project } = require('tasks/layout/task/fields/project');
	const { Accomplices } = require('tasks/layout/task/fields/accomplices');
	const { Auditors } = require('tasks/layout/task/fields/auditors');
	const { ViewMode, TaskCounter } = require('tasks/enum');
	const { getStageByDeadline } = require('tasks/utils/stages');
	const { Color, Indent } = require('tokens');
	const { Text5 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');

	class TaskKanbanContent extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.onChangeStage = this.onChangeStage.bind(this);
			this.onChangeStageAnimationCompleted = this.onChangeStageAnimationCompleted.bind(this);
			this.onContextMenuClick = this.onContextMenuClick.bind(this);
			this.onChangeDeadline = this.onChangeDeadline.bind(this);
			this.prepareHiddenFilesCounterText = this.prepareHiddenFilesCounterText.bind(this);
			this.bindStageSelectorRef = this.bindStageSelectorRef.bind(this);

			/** @type {TasksStageSelector|undefined} */
			this.stageSelectorRef = undefined;
		}

		get task()
		{
			return this.props.task;
		}

		/**
		 * @private
		 * @param {string} code
		 * @return {{ code: string, title: string, visible: boolean }}
		 */
		getField(code)
		{
			const defaultField = { code, visible: false, title: code };

			return get(this.props, `itemLayoutOptions.displayFields.${code}`, defaultField);
		}

		/**
		 * @private
		 * @param {number} count
		 * @return {?string}
		 */
		prepareHiddenFilesCounterText(count)
		{
			return Loc.getMessage('M_TASKS_KANBAN_ITEM_FIELD_VALUE_SHOW_MORE', {
				'#COUNT#': count,
			});
		}

		/**
		 * @private
		 * @return {{
		 * 	deadline: number|null,
		 * 	leftBorder: number|null,
		 * 	rightBorder: number|null,
		 * 	statusId: string,
		 * 	id: number}[]}
		 */
		getDeadlineViewStages()
		{
			const state = store.getState();
			const kanbanSettingsId = getUniqId(this.props.view, this.props.projectId, this.props.ownerId);
			const stageIds = selectStageIds(state, kanbanSettingsId) || [];

			return stageIds.map((id) => selectStageById(state, id)).filter(Boolean);
		}

		/**
		 * @private
		 * @param {TasksStageSelector|undefined} ref
		 */
		bindStageSelectorRef(ref)
		{
			this.stageSelectorRef = ref;
		}

		// region ui interactions

		onContextMenuClick()
		{
			if (this.task.isCreationErrorExist)
			{
				return;
			}

			if (this.props.onContextMenuClick)
			{
				this.props.onContextMenuClick();
			}
		}

		onChangeStage(stageId)
		{
			if (this.props.view === ViewMode.DEADLINE)
			{
				const { deadline } = selectStageById(store.getState(), stageId) || {};

				const ts = Number.isInteger(deadline) ? deadline * 1000 : null;

				dispatch(updateDeadline({
					taskId: this.task.id,
					deadline: ts,
				}));
			}

			return Promise.resolve({});
		}

		onChangeDeadline(ts)
		{
			if (this.props.view !== ViewMode.DEADLINE)
			{
				return;
			}

			const nextStage = getStageByDeadline(ts, this.getDeadlineViewStages());

			if (!nextStage || !this.stageSelectorRef)
			{
				return;
			}

			// eslint-disable-next-line promise/catch-or-return
			this.stageSelectorRef.scrollTo(nextStage.id).finally(() => {
				this.dispatchStageChanged(nextStage.id);
			});
		}

		onChangeStageAnimationCompleted({ columnId: nextStageId })
		{
			if (this.props.view === ViewMode.DEADLINE)
			{
				const { deadline } = selectStageById(store.getState(), nextStageId) || {};

				const nextStageByDeadline = Number.isInteger(deadline)
					? getStageByDeadline(deadline * 1000, this.getDeadlineViewStages())
					: null;

				if (nextStageByDeadline && nextStageByDeadline.id !== nextStageId && this.stageSelectorRef)
				{
					// eslint-disable-next-line promise/catch-or-return
					this.stageSelectorRef.scrollTo(nextStageByDeadline.id).finally(() => {
						this.dispatchStageChanged(nextStageByDeadline.id);
					});

					return;
				}
			}

			this.dispatchStageChanged(nextStageId);
		}

		/**
		 * @private
		 * @param {number} nextStageId
		 */
		dispatchStageChanged(nextStageId)
		{
			const prevStageId = this.task.stageId;

			dispatch(setTaskStage({
				prevStageId,
				nextStageId,
				projectId: this.props.projectId,
				taskId: this.task.id,
				viewMode: this.props.view,
				userId: this.props.ownerId,
			}));

			if (this.props.onChangeItemStage)
			{
				this.props.onChangeItemStage(nextStageId, {}, {
					prevStageId,
					nextStageId,
					itemId: this.task.id,
				});
			}
		}

		// endregion

		// region render

		render()
		{
			if (this.task?.isCreationErrorExist)
			{
				return View(
					{
						style: {
							flexDirection: 'column',
							backgroundColor: AppTheme.colors.bgContentPrimary,
						},
					},
					this.renderHeader(),
					View(
						{
							style: Styles.itemContent,
						},
						View(
							{
								style: Styles.body(this.task),
							},
							this.renderResponsible(),
							this.renderCreationError(),
						),
					),
				);
			}

			return View(
				{
					style: {
						flexDirection: 'column',
						backgroundColor: AppTheme.colors.bgContentPrimary,
					},
				},
				this.renderHeader(),
				View(
					{
						style: Styles.itemContent,
					},
					this.renderBody(),
				),
				this.renderStageSelector(),
				View(
					{
						style: Styles.itemContent,
					},
					this.renderFields(),
				),
			);
		}

		renderHeader()
		{
			return View(
				{
					testId: `${this.props.testId}_SECTION`,
					style: Styles.header(this.task),
				},
				Text({
					testId: `${this.props.testId}_SECTION_TITLE`,
					style: Styles.title(this.task?.isCompleted),
					text: this.task?.name || this.props.id,
					numberOfLines: 2,
					ellipsize: 'end',
				}),
				View(
					{
						style: Styles.contextMenuClickableZone,
						onClick: this.onContextMenuClick,
						ref: this.props.menuViewRef,
					},
					this.renderImportantIcon(),
					this.renderMuteIcon(),
					this.renderContextMenu(),
				),
			);
		}

		renderImportantIcon()
		{
			return this.renderStateTopIcon(
				Icons.important,
				`${this.props.testId}_IMPORTANT`,
				this.task?.priority === 2,
			);
		}

		renderMuteIcon()
		{
			return this.renderStateTopIcon(Icons.mute, `${this.props.testId}_MUTE`, this.task?.isMuted);
		}

		renderStateTopIcon(iconContent, testId, shouldShow = true)
		{
			return View(
				{
					testId,
					style: Styles.stateIconWrapper(shouldShow),
					onClick: this.onContextMenuClick,
				},
				Image({
					style: Styles.iconSmaller,
					svg: {
						content: iconContent,
					},
				}),
			);
		}

		renderContextMenu()
		{
			return View(
				{
					testId: `${this.props.testId}_CONTEXT_MENU_BTN`,
				},
				Image({
					tintColor: AppTheme.colors.base3,
					style: {
						width: 24,
						height: 24,
					},
					svg: {
						content: Icons.more,
					},
				}),
			);
		}

		renderBody()
		{
			return View(
				{
					style: Styles.body(this.task),
				},
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'flex-start',
							flex: 1,
						},
					},
					View(
						{
							style: {
								...Styles.bodySection,
								flexGrow: 1,
							},
						},
						this.renderResponsible(),
						this.renderDeadline(),
					),
				),
				View(
					{
						style: {
							...Styles.bodySection,
							minWidth: 72,
							justifyContent: 'flex-end',
						},
					},
					this.renderCounter(),
				),
			);
		}

		renderResponsible()
		{
			return Avatar({
				id: this.task?.responsible,
				withRedux: true,
				size: 24,
				testId: `${this.testId}_RESPONSIBLE`,
			});
		}

		renderDeadline()
		{
			return DeadlinePill({
				id: this.task?.id,
				testId: `${this.props.testId}_DEADLINE`,
				backgroundColor: AppTheme.colors.bgContentPrimary,
				onChange: this.onChangeDeadline,
			});
		}

		renderCreationError()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				Text5({
					style: {
						color: Color.accentMainAlert.toHex(),
					},
					text: Loc.getMessage('M_TASKS_TASK_ITEM_ERROR'),
				}),
				IconView({
					style: {
						marginLeft: Indent.XS.toNumber(),
					},
					icon: Icon.ALERT,
					color: Color.accentMainAlert,
				}),
			);
		}

		renderCounter()
		{
			const counter = this.task?.counter;

			if (counter && counter.value > 0)
			{
				let counterColor = AppTheme.colors.base4;

				if (counter.type === TaskCounter.ALERT)
				{
					counterColor = AppTheme.colors.accentMainAlert;
				}
				else if (counter.type === TaskCounter.SUCCESS)
				{
					counterColor = AppTheme.colors.accentMainSuccess;
				}

				return View(
					{},
					CounterView(
						counter.value,
						{
							isDouble: counter.isDouble,
							firstColor: counterColor,
							secondColor: AppTheme.colors.accentMainSuccess,
						},
					),
				);
			}

			return null;
		}

		renderStageSelector()
		{
			if (!Number.isInteger(this.task?.stageId))
			{
				return null;
			}

			const isDeadline = this.props.view === ViewMode.DEADLINE;

			const isEditable = isDeadline
				? (this.task.canMoveStage && this.task.canChangeDeadline)
				: this.task.canMoveStage;

			const readonlyNotificationMessage = (isDeadline && !this.task.canChangeDeadline)
				? Loc.getMessage('M_TASKS_DENIED_UPDATEDEADLINE')
				: undefined;

			return TasksStageSelector({
				showTitle: false,
				value: this.task?.stageId,
				view: this.props.view,
				projectId: this.props.projectId,
				ownerId: this.props.ownerId,
				readOnly: !isEditable,
				config: {
					readonlyNotificationMessage,
					isReversed: isDeadline,
					useStageChangeMenu: true,
					showReadonlyNotification: true,
					animationMode: 'animateBeforeUpdate',
					parentWidget: this.props.layout,
				},
				showReadonlyNotification: true,
				onChange: this.onChangeStage,
				forceUpdate: this.onChangeStageAnimationCompleted,
				forwardedRef: this.bindStageSelectorRef,
			});
		}

		renderFields()
		{
			return View(
				{},
				this.renderId(),
				this.renderSpace(),
				this.renderAccomplices(),
				this.renderAuditors(),
				this.renderChecklist(),
				this.renderFiles(),
				this.renderStartDate(),
				this.renderEndDate(),
				this.renderElapsedTime(),
				this.renderCrmElements(),
				this.renderTags(),
				this.renderMark(),
			);
		}

		renderId()
		{
			const { title, visible } = this.getField('ID');

			if (!visible)
			{
				return null;
			}

			return TextField({
				title,
				value: this.task?.id,
				testId: `${this.props.testId}_ID`,
			});
		}

		renderSpace()
		{
			const { visible } = this.getField('PROJECT');

			if (!this.task?.groupId || !visible)
			{
				return null;
			}

			return new Project({
				readOnly: true,
				groupId: this.task.groupId,
				groupData: this.task.group,
				deepMergeStyles: {
					title: {
						color: AppTheme.colors.base3,
						marginBottom: 4,
					},
					projectText: {
						fontSize: 14,
					},
				},
			});
		}

		renderAccomplices()
		{
			const { visible } = this.getField('ACCOMPLICES');

			const userIds = this.task?.accomplices || [];

			if (userIds.length === 0 || !visible)
			{
				return null;
			}

			return this.renderUsersSet(Accomplices, userIds, 'accomplices');
		}

		renderAuditors()
		{
			const { visible } = this.getField('AUDITORS');

			const userIds = this.task?.auditors || [];

			if (userIds.length === 0 || !visible)
			{
				return null;
			}

			return this.renderUsersSet(Auditors, userIds, 'auditors');
		}

		renderUsersSet(Renderer, userIds, dataKey)
		{
			const users = {};
			userIds.forEach((userId) => {
				const {
					id,
					link,
					workPosition,
					fullName: name,
					avatarSize100: icon,
				} = usersSelector.selectById(store.getState(), Number(userId)) || {};

				users[id] = { id, link, name, icon, workPosition };
			});

			return new Renderer({
				[dataKey]: users,
				readOnly: true,
				parentWidget: this.props.layout,
				deepMergeStyles: {
					title: {
						color: AppTheme.colors.base3,
						marginBottom: 4,
					},
					userImage: {
						marginRight: 8,
					},
				},
			});
		}

		renderChecklist()
		{
			const { title, visible } = this.getField('CHECKLIST');

			if (!this.task?.checklist || !visible)
			{
				return null;
			}

			const { completed = 0, uncompleted = 0 } = this.task.checklist;
			const total = completed + uncompleted;

			if (total === 0)
			{
				return null;
			}

			const stringify = (value) => (value > 99 ? '99+' : String(value));

			return IconField({
				title,
				icon: Icons.checklist,
				testId: `${this.props.testId}_CHECKLIST`,
				value: Loc.getMessage('M_TASKS_KANBAN_ITEM_FIELD_CHECKLIST_VALUE', {
					'#COMPLETED#': stringify(completed),
					'#TOTAL#': stringify(total),
				}),
			});
		}

		renderFiles()
		{
			const { title, visible } = this.getField('FILES');

			const files = this.task?.files || [];
			if (files.length === 0 || !visible)
			{
				return null;
			}

			return FileField({
				title,
				readOnly: true,
				showAddButton: false,
				showEditIcon: false,
				showFilesName: false,
				hasHiddenEmptyView: true,
				multiple: true,
				value: files,
				onPrepareHiddenFilesCounterText: this.prepareHiddenFilesCounterText,
				config: {
					deepMergeStyles: {
						title: {
							color: AppTheme.colors.base3,
						},
						hiddenFilesCounterWrapper: {
							borderWidth: 0,
							marginLeft: 0,
							width: 'auto',
						},
						hiddenFilesCounterText: {
							fontSize: 14,
							color: AppTheme.colors.base4,
						},
					},
				},
				testId: `${this.props.testId}_FILES`,
			});
		}

		renderStartDate()
		{
			const { title, visible } = this.getField('DATE_STARTED');

			if (!this.task?.startDate || !visible)
			{
				return null;
			}

			const moment = Moment.createFromTimestamp(this.task?.startDate);

			return TextField({
				title,
				value: moment.format(`${date()}, ${shortTime()}`),
				testId: `${this.props.testId}_START_DATE`,
			});
		}

		renderEndDate()
		{
			const { title, visible } = this.getField('DATE_FINISHED');

			if (!this.task?.endDate || !visible)
			{
				return null;
			}

			const moment = Moment.createFromTimestamp(this.task?.endDate);

			return TextField({
				title,
				value: moment.format(`${date()}, ${shortTime()}`),
				testId: `${this.props.testId}_END_DATE`,
			});
		}

		renderElapsedTime()
		{
			const { title, visible } = this.getField('TIME_SPENT');

			if (!this.task?.timeElapsed || !visible)
			{
				return null;
			}

			const clockify = (seconds) => {
				const hours = Math.floor(seconds / 3600);
				const minutes = Math.floor((seconds % 3600) / 60);
				const secondsLeft = seconds % 60;

				const pad = (value) => String(value).padStart(2, '0');

				return `${pad(hours)}:${pad(minutes)}:${pad(secondsLeft)}`;
			};

			return TextField({
				title,
				value: clockify(this.task.timeElapsed),
				testId: `${this.props.testId}_ELAPSED_TIME`,
			});
		}

		renderCrmElements()
		{
			const { visible } = this.getField('CRM');

			const elements = this.task?.crm || [];
			if (elements.length === 0 || !visible)
			{
				return null;
			}

			return new Crm({
				readOnly: true,
				crm: elements,
				deepMergeStyles: {
					title: {
						color: AppTheme.colors.base3,
						marginBottom: 4,
					},
					entityTitle: (clickable) => ({
						color: clickable ? AppTheme.colors.accentMainLinks : AppTheme.colors.base1,
						fontSize: 14,
						flexShrink: 2,
					}),
				},
			});
		}

		renderTags()
		{
			const { visible } = this.getField('TAGS');

			const tags = this.task?.tags || [];
			if (tags.length === 0 || !visible)
			{
				return null;
			}

			return new Tags({
				readOnly: true,
				tags: Object.fromEntries(tags.map((tag) => [tag.id, { ...tag, title: tag.name }])),
				deepMergeStyles: {
					title: {
						color: AppTheme.colors.base3,
						marginBottom: 4,
					},
					tag: {
						backgroundColor: AppTheme.colors.bgContentTertiary,
					},
					numberSign: {
						color: AppTheme.colors.base4,
						fontSize: 13,
					},
					tagTitle: {
						color: AppTheme.colors.base2,
						fontSize: 13,
					},
				},
			});
		}

		renderMark()
		{
			const { visible } = this.getField('MARK');

			const allowedValues = ['N', 'P'];
			if (!allowedValues.includes(this.task?.mark) || !visible)
			{
				return null;
			}

			return new Mark({
				readOnly: true,
				mark: this.task.mark,
				deepMergeStyles: {
					title: {
						color: AppTheme.colors.base3,
						marginBottom: 4,
					},
					value: {
						color: AppTheme.colors.base2,
						fontSize: 14,
					},
				},
			});
		}

		// endregion
	}

	const Styles = {
		itemContent: {
			marginLeft: 24,
			marginRight: 18,
			paddingTop: 4,
			flexGrow: 1,
		},
		header: (shouldShow) => ({
			display: shouldShow ? 'flex' : 'none',
			flexDirection: 'row',
			alignItems: 'flex-start',
			flexGrow: 1,
		}),
		title: (isCompleted) => ({
			flex: 1,
			color: isCompleted ? AppTheme.colors.base4 : AppTheme.colors.base1,
			textDecorationLine: isCompleted ? 'line-through' : 'none',
			fontWeight: '500',
			fontSize: 16,
			marginTop: 20,
			marginBottom: 14,
			marginLeft: 24,
		}),
		contextMenuClickableZone: {
			flexDirection: 'row',
			alignItems: 'center',
			paddingTop: 18,
			paddingRight: 18,
			paddingBottom: 14,
			paddingLeft: 10,
		},
		iconSmall: {
			width: 18,
			height: 18,
		},
		iconSmaller: {
			height: 16,
			width: 16,
		},
		stateIconWrapper: (shouldShow) => ({
			display: shouldShow ? 'flex' : 'none',
			marginRight: 9,
		}),
		date: {
			color: AppTheme.colors.base3,
			textAlign: 'right',
			fontSize: 12,
			marginLeft: 2,
			minWidth: 32,
			marginBottom: 0,
			paddingTop: 0,
			marginTop: 2,
		},
		body: (shouldShow) => ({
			display: shouldShow ? 'flex' : 'none',
			flexDirection: 'row',
			justifyContent: 'space-between',
			alignItems: 'center',
			flexGrow: 1,
			marginBottom: 8,
		}),
		bodySection: {
			flexDirection: 'row',
			alignItems: 'center',
		},
	};

	const Icons = {
		mute: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.65046 3.87501C9.98206 3.64529 10.4352 3.88262 10.4352 4.28602V15.3834C10.4352 15.784 9.98751 16.022 9.65543 15.7978L6.05556 13.3676V13.1663C6.02762 13.1698 5.99913 13.1717 5.97022 13.1717H3.33195C2.96389 13.1717 2.66553 12.8733 2.66553 12.5053V7.15028C2.66553 6.78223 2.96389 6.48386 3.33195 6.48386H5.97022C5.99913 6.48386 6.02762 6.4857 6.05556 6.48927V6.36545L9.65046 3.87501ZM16.5559 12.4443L17.4837 11.5166L15.6282 9.66113L17.4837 7.80566L16.5559 6.87792L14.7005 8.7334L12.845 6.87792L11.9172 7.80566L13.7727 9.66113L11.9172 11.5166L12.845 12.4443L14.7005 10.5889L16.5559 12.4443Z" fill="#A7A7A7"/></svg>',
		important: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M10.8333 16.6673C11.8805 15.8787 12.4974 14.6449 12.5 13.334C12.5 11.5412 11.2888 11.7789 10.0819 9.33722C10.0377 9.2479 9.92551 9.21323 9.84271 9.26866C8.47548 10.184 7.60697 11.6851 7.5 13.334C7.58028 14.6252 8.18185 15.8284 9.16667 16.6673H8.575C6.46795 15.8951 5.0492 13.9109 5 11.6673C5 8.18602 8.16809 4.91146 10.3036 3.62326C10.5285 3.48759 10.7996 3.67666 10.8131 3.93896C10.9674 6.94986 15 8.01431 15 12.2923C15 15.5232 11.425 16.6673 11.425 16.6673H10.8333Z" fill="#FFA900"/></svg>',
		pin: '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M13.4756 5.53159C13.7704 5.82703 13.7551 6.32027 13.4415 6.63326C13.1278 6.94626 12.6346 6.96049 12.3399 6.66505L11.79 6.11519L8.29195 10.7015L8.82877 11.2383C9.10414 11.5368 9.08082 12.0141 8.77598 12.3189C8.47114 12.6237 7.99385 12.6471 7.69538 12.3717L6.38413 11.0629L3.00569 13.6073C2.95545 13.6634 2.87984 13.6889 2.80936 13.6734C2.73888 13.6579 2.68516 13.604 2.66987 13.5335C2.65459 13.463 2.68026 13.3874 2.73653 13.3373L5.24338 9.92051L3.95827 8.63621C3.6773 8.33855 3.69834 7.85666 4.00573 7.54948C4.31312 7.2423 4.79501 7.2216 5.09245 7.50279L5.62928 8.03962L10.2164 4.54153L9.67032 3.9955C9.38935 3.69784 9.4104 3.21595 9.71779 2.90877C10.0252 2.60159 10.5071 2.58089 10.8045 2.86208L13.4756 5.53159Z" fill="#A7A7A7"/></svg>',
		repetition: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.25675 9.07471L8.01789 11.8359L6.10394 11.8356L6.10464 13.039C6.10464 13.4214 6.39075 13.7369 6.76056 13.7832L6.85464 13.789H12.9333C13.3156 13.789 13.6312 13.5029 13.6774 13.1331L13.6833 13.039L13.6832 12.619L15.5574 12.7823L15.558 12.9971C15.558 14.4699 14.3641 15.6638 12.8914 15.6638H6.89655C5.42379 15.6638 4.22989 14.4699 4.22989 12.9971L4.22977 11.8356L2.49561 11.8359L5.25675 9.07471ZM12.8914 4.33594C14.3641 4.33594 15.558 5.52984 15.558 7.0026L15.5573 8.18677L17.4007 8.18746L14.6395 10.9486L11.8784 8.18746L13.6831 8.18677L13.6833 6.96069C13.6833 6.54647 13.3475 6.21069 12.9333 6.21069H6.85464C6.47228 6.21069 6.15676 6.4968 6.11048 6.86661L6.10464 6.96069L6.10405 7.27594L4.22989 7.11177V7.0026C4.22989 5.52984 5.42379 4.33594 6.89655 4.33594H12.8914Z" fill="#A8ADB4"/></svg>',
		checklist: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M17.3202 6.3255C17.276 6.3424 17.2272 6.34036 17.1823 6.32536C17.0429 6.27882 16.8937 6.25361 16.7387 6.25361H7.15898C6.38579 6.25361 5.75898 6.88041 5.75898 7.65361V16.2989C5.75898 17.0721 6.38579 17.6989 7.15898 17.6989H16.7387C17.5119 17.6989 18.1387 17.0721 18.1387 16.2989V13.2535C18.1387 13.2039 18.1571 13.1562 18.1903 13.1194L19.0903 12.1237C19.2131 11.9879 19.4387 12.0747 19.4387 12.2578V16.2989C19.4387 17.7901 18.2298 18.9989 16.7387 18.9989H7.15898C5.66782 18.9989 4.45898 17.7901 4.45898 16.2989V7.65361C4.45898 6.16244 5.66782 4.95361 7.15898 4.95361H16.7387C17.2683 4.95361 17.7622 5.10609 18.1791 5.36954C18.2769 5.43135 18.2913 5.56523 18.2159 5.65303L18.1242 5.75984C17.9049 6.01525 17.6277 6.20808 17.3202 6.3255Z" fill="#828B95"/><path fill-rule="evenodd" clip-rule="evenodd" d="M7.94742 14.2522C7.94742 13.9208 8.21605 13.6522 8.54742 13.6522H13.9678C14.2992 13.6522 14.5678 13.9208 14.5678 14.2522C14.5678 14.5835 14.2992 14.8522 13.9678 14.8522H8.54742C8.21605 14.8522 7.94742 14.5835 7.94742 14.2522Z" fill="#828B95"/><path fill-rule="evenodd" clip-rule="evenodd" d="M7.94742 10.7542C7.94742 10.4229 8.21605 10.1542 8.54742 10.1542H12.6127C12.9441 10.1542 13.2127 10.4229 13.2127 10.7542C13.2127 11.0856 12.9441 11.3542 12.6127 11.3542H8.54742C8.21605 11.3542 7.94742 11.0856 7.94742 10.7542Z" fill="#828B95"/><path fill-rule="evenodd" clip-rule="evenodd" d="M21.68 5.80221C21.9273 6.02284 21.9489 6.40212 21.7283 6.64937L17.8411 11.0056C17.6215 11.2517 17.2445 11.2744 16.9969 11.0564L14.5233 8.87831C14.2746 8.65932 14.2505 8.28019 14.4695 8.03149C14.6885 7.78279 15.0676 7.7587 15.3163 7.97769L17.3426 9.76189L20.8329 5.85042C21.0535 5.60317 21.4328 5.58159 21.68 5.80221Z" fill="#828B95"/></svg>',
		more: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8.35672 12.2976C8.35672 13.2408 7.59212 14.0054 6.64894 14.0054C5.70576 14.0054 4.94116 13.2408 4.94116 12.2976C4.94116 11.3544 5.70576 10.5898 6.64894 10.5898C7.59212 10.5898 8.35672 11.3544 8.35672 12.2976Z" fill="#C9CCD0"/><path d="M12.1138 14.0054C13.057 14.0054 13.8216 13.2408 13.8216 12.2976C13.8216 11.3544 13.057 10.5898 12.1138 10.5898C11.1707 10.5898 10.4061 11.3544 10.4061 12.2976C10.4061 13.2408 11.1707 14.0054 12.1138 14.0054Z" fill="#C9CCD0"/><path d="M17.5787 14.0054C18.5219 14.0054 19.2865 13.2408 19.2865 12.2976C19.2865 11.3544 18.5219 10.5898 17.5787 10.5898C16.6356 10.5898 15.871 11.3544 15.871 12.2976C15.871 13.2408 16.6356 14.0054 17.5787 14.0054Z" fill="#C9CCD0"/></svg>',
	};

	const mapStateToProps = (state, ownProps) => {
		const taskId = ownProps.id;
		const task = selectByTaskIdOrGuid(state, taskId);

		if (!task)
		{
			return { task };
		}

		const {
			id,
			guid,
			name,
			responsible,
			priority,
			isPinned,
			isMuted,
			isRepeatable,
			checklist,
			files,
			activityDate,
			startDate,
			endDate,
			groupId,
			accomplices,
			auditors,
			tags,
			mark,
			crm,
			timeElapsed,
			isCreationErrorExist,
		} = task;

		const { stageId, canMoveStage } = selectTaskStageByTaskIdOrGuid(
			state,
			id,
			guid,
			ownProps.view,
			ownProps.ownerId,
		) || {};

		return {
			task: {
				id,
				name,
				responsible,
				priority,
				isPinned,
				isMuted,
				isRepeatable,
				checklist,
				files,
				groupId,
				group: selectGroupById(state, groupId),
				accomplices,
				auditors,
				tags,
				mark,
				crm,
				timeElapsed,
				startDate,
				endDate,
				isCreationErrorExist,
				activityDate: activityDate - (activityDate % 60),
				counter: selectCounter(task),
				isCompleted: selectIsCompleted(task),
				canChangeDeadline: selectActions(task).updateDeadline,
				stageId,
				canMoveStage,
			},
		};
	};

	module.exports = {
		TaskContentView: connect(mapStateToProps)(TaskKanbanContent),
	};
});
