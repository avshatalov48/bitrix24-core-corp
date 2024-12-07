/**
 * @module bizproc/workflow/list/simple-list/item
 */
jn.define('bizproc/workflow/list/simple-list/item', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Alert } = require('alert');
	const { EventEmitter } = require('event-emitter');
	const { Loc } = require('loc');
	const { mergeImmutable, get } = require('utils/object');
	const { useCallback } = require('utils/function');
	const { shortTime, dayMonth, longDate } = require('utils/date/formats');

	const { FriendlyDate } = require('layout/ui/friendly-date');
	const { Base } = require('layout/ui/simple-list/items/base');
	const { Checkbox } = require('ui-system/form/checkbox');

	const { WorkflowFaces } = require('bizproc/workflow/faces');
	const { TaskErrorCode } = require('bizproc/task/task-constants');
	const { TaskButtons } = require('bizproc/task/buttons');
	const { ViewMode } = require('bizproc/workflow/list/view-mode');
	const { CounterView } = require('layout/ui/counter-view');

	class WorkflowSimpleListItem extends Base
	{
		constructor(props)
		{
			super(props);

			this.uid = props.item.uid || 'bizproc';
			this.eventEmitter = EventEmitter.createWithUid(this.uid);
			this.styles = mergeImmutable(this.styles, styles); // todo: override render()

			this.isLayoutHidden = false;
			if (this.layout)
			{
				this.layout
					.on('onViewWillHidden', () => {
						this.isLayoutHidden = true;
					})
					.on('onViewRemoved', () => {
						this.isLayoutHidden = true;
					})
				;
			}
		}

		get viewMode()
		{
			return this.props.item.viewMode;
		}

		get isSelected()
		{
			return this.props.item.selected;
		}

		get isChecked()
		{
			return this.isSelected || (!this.task && this.viewMode === ViewMode.MULTISELECT);
		}

		get isCheckboxDisabled()
		{
			return !this.task && this.viewMode === ViewMode.MULTISELECT;
		}

		get layout()
		{
			return this.props.layout;
		}

		get task()
		{
			const { data } = this.props.item;

			return data.tasks.at(0);
		}

		get workflowId()
		{
			const { data } = this.props.item;

			return data.id;
		}

		get typeName()
		{
			return get(this.props, 'item.data.typeName', '');
		}

		/**
		 * @private
		 * @return View
		 */
		renderItemContent()
		{
			return View(
				{
					style: this.styles.itemContent,
				},
				this.renderHeader(),
				this.renderMenuIcon(),
				this.renderCounters(),
				this.renderBody(),
			);
		}

		/**
		 * @private
		 * @returns View
		 */
		renderMenuIcon()
		{
			return View(
				{
					style: this.styles.menuContainer,
					testId: `${this.testId}_CONTEXT_MENU_BTN`,
					onClick: this.onCheckboxClicked.bind(this),
				},
				new Checkbox({
					testId: `${this.testId}_CHECKBOX_${this.workflowId}`,
					size: 24,
					disabled: this.isCheckboxDisabled,
					checked: this.isChecked,
					useState: false,
				}),
			);
		}

		onCheckboxClicked()
		{
			const isSelected = !this.isSelected;

			this.eventEmitter.emit(
				isSelected ? 'Task:onSelect' : 'Task:onDeselect',
				{
					task: this.task,
					workflowId: this.workflowId,
					typeName: this.typeName,
					item: this.props.item,
				},
			);
		}

		renderCounters()
		{
			const { data } = this.props.item;
			const tasksCnt = data.tasks.length;
			const commentsCnt = data.newCommentsCounter;
			const useWorkflowCounter = data.useWorkflowCounter || false;

			if (!tasksCnt && !commentsCnt)
			{
				return null;
			}

			return View(
				{
					style: this.styles.tasksCounter,
				},
				CounterView(
					useWorkflowCounter ? (tasksCnt || 0) + (commentsCnt || 0) : (tasksCnt || commentsCnt),
					{
						firstColor: tasksCnt > 0 ? AppTheme.colors.accentMainAlert : AppTheme.colors.accentMainSuccess,
						isDouble: tasksCnt && commentsCnt,
					},
				),
			);
		}

		/**
		 * @private
		 * @returns View
		 */
		renderHeader()
		{
			return this.renderItemName();
		}

		/**
		 * Implement this method in child class if you need to change item body layout
		 *
		 * @private
		 * @returns View
		 */
		renderBody()
		{
			const { data } = this.props.item;
			const hasTasks = data.tasks.length > 0;

			return View(
				{},
				this.renderTypeName(),
				this.renderTime(),
				this.renderWorkflowFaces(data.id, data.faces),
				!hasTasks && this.renderStatus(),
				hasTasks && this.renderTaskButtons(),
			);
		}

		renderItemName()
		{
			const { data } = this.props.item;

			return View(
				{},
				Text({
					testId: `${this.testId}_ITEM_NAME`,
					text: jnComponent.convertHtmlEntities(data.itemName),
					style: this.styles.itemName,
					numberOfLines: 2,
					ellipsize: 'end',
				}),
			);
		}

		renderTypeName()
		{
			const { data } = this.props.item;

			return View(
				{
					testId: `${this.testId}_TYPE`,
					style: this.styles.header,
				},
				Text({
					testId: `${this.testId}_TYPE_NAME`,
					style: this.styles.title,
					text: (data.typeName || '').toLocaleUpperCase(env.languageId),
					numberOfLines: 1,
					ellipsize: 'end',
				}),
			);
		}

		renderTime()
		{
			const { data } = this.props.item;

			if (!data.itemTime)
			{
				return null;
			}

			return new FriendlyDate({
				timestamp: data.itemTime,
				timeSeparator: ' ',
				defaultFormat: (moment) => {
					const day = moment.format(moment.inThisYear ? dayMonth() : longDate());
					const time = moment.format(shortTime);

					return `${day} ${time}`;
				},
				showTime: true,
				useTimeAgo: false,
				style: {
					fontWeight: '400',
					fontSize: 13,
					color: AppTheme.colors.base4,
					marginRight: 60,
					marginLeft: 13,
				},
			});
		}

		renderWorkflowFaces(workflowId, faces)
		{
			return View(
				{
					style: this.styles.workflowFaces,
					onClick: () => {
						void requireLazy('bizproc:workflow/timeline').then(({ WorkflowTimeline }) => {
							void WorkflowTimeline.open(
								this.props.layout,
								{
									workflowId,
									taskId: this.props.item.data.tasks.at(0)?.id,
									readOnly: BX.prop.getBoolean(this.props.item.data, 'readOnlyTimeline', false),
								},
							);
						});
					},
				},
				new WorkflowFaces({
					layout: this.props.layout,
					workflowId,
					faces,
				}),
			);
		}

		renderStatus()
		{
			const { data } = this.props.item;

			return View(
				{
					style: this.styles.statusWrap,
				},
				Text({
					text: Loc.getMessage('BPMOBILE_WORKFLOW_SIMPLE_LIST_STATUS'),
					style: this.styles.statusTitle,
				}),
				Text({
					testId: `${this.testId}_STATUS_TEXT`,
					text: data.statusText,
					style: this.styles.statusText,
					numberOfLines: 1,
					ellipsize: 'end',
				}),
			);
		}

		renderTaskButtons()
		{
			const { data } = this.props.item;
			const task = data.tasks[0];

			return View(
				{
					style: this.styles.taskButtons,
				},
				new TaskButtons({
					layout: this.layout,
					uid: this.uid,
					task: { ...task, workflowId: data.id },
					isInline: true,
					title: data.typeName,
					onFail: useCallback(this.onDoTaskFailed.bind(this)),
					ref: useCallback((ref) => {
						this.taskButtonsRef = ref;
					}),
				}),
			);
		}

		onDoTaskFailed(errors)
		{
			if (Array.isArray(errors) && errors.length > 0 && !this.isLayoutHidden)
			{
				const error = errors.pop();
				if (!TaskErrorCode.isTaskNotFoundErrorCode(error.code))
				{
					Alert.alert(error.message);
				}
			}
		}

		setIsDoing(flag)
		{
			if (this.taskButtonsRef)
			{
				this.taskButtonsRef.setIsDoing(flag);
			}
		}
	}

	const styles = {
		wrapper: {
			backgroundColor: AppTheme.colors.bgPrimary,
			paddingTop: 12,
		},
		item: {
			position: 'relative',
			backgroundColor: AppTheme.colors.bgContentPrimary,
			borderRadius: 12,
			paddingHorizontal: 12,
		},
		itemContent: {
			paddingTop: 17,
			paddingBottom: 8,
		},
		header: {
			flexDirection: 'column',
			marginRight: 70,
			marginLeft: 13,
		},
		title: {
			color: AppTheme.colors.base4,
			fontWeight: '400',
			fontSize: 12,
			height: 18,
			marginRight: 48,
			marginBottom: 8,
		},
		itemName: {
			fontWeight: '600',
			fontSize: 17,
			color: AppTheme.colors.base1,
			marginLeft: 13,
			marginRight: 60,
			marginBottom: 6,
			lineHeightMultiple: 1.15,
		},
		workflowFaces: {
			marginVertical: 8,
		},
		menu: {
			width: 24,
			height: 24,
		},
		menuContainer: {
			position: 'absolute',
			width: 64,
			height: 60,
			justifyContent: 'flex-start',
			alignItems: 'flex-end',
			right: 0,
			top: 0,
			paddingTop: 19.5,
			paddingRight: 14.5,
		},
		tasksCounter: {
			position: 'absolute',
			top: 69,
			right: 17,
			width: 28,
			height: 28,
		},
		taskButtons: {
			marginHorizontal: 13,
			paddingTop: 17,
			paddingBottom: 14,
		},
		statusWrap: {
			marginHorizontal: 13,
			paddingTop: 17,
			paddingBottom: 9,
		},
		statusTitle: {
			fontSize: 11,
			height: 14,
			fontWeight: '400',
			color: AppTheme.colors.base4,
		},
		statusText: {
			fontSize: 14,
			height: 19,
			fontWeight: '400',
			color: AppTheme.colors.base1,
			marginBottom: 8,
		},
	};

	module.exports = { WorkflowSimpleListItem };
});
