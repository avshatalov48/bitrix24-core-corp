/**
 * @module tasks/layout/fields/result/view-redux-content
 */
jn.define('tasks/layout/fields/result/view-redux-content', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { TextEditor } = require('text-editor');
	const { Loc } = require('loc');
	const { Indent, Color } = require('tokens');
	const { Chip } = require('ui-system/blocks/chips/chip');
	const { Text5 } = require('ui-system/typography/text');
	const { ButtonSize, ButtonDesign, Button } = require('ui-system/form/buttons/button');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { ActionId, ActionMeta } = require('tasks/layout/action-menu/actions');
	const { Date } = require('tasks/layout/fields/result/date');
	const { dayMonth, longDate, shortTime } = require('utils/date/formats');

	const { connect } = require('statemanager/redux/connect');
	const { selectById: selectTaskById, selectActions } = require('tasks/statemanager/redux/slices/tasks');
	const { selectByTaskId, selectById } = require('tasks/statemanager/redux/slices/tasks-results');

	class TaskResultViewReduxContent extends PureComponent
	{
		constructor(props)
		{
			super(props);

			/** @type {null|TextEditor} */
			this.textEditorRef = null;

			/** @type {null|ScrollView} */
			this.scrollRef = null;

			/** @type {Map<number, Chip>} */
			this.chipsRefs = new Map();

			/** @type {Map<number, number>} */
			this.chipsWidth = new Map();

			this.state = {
				isFocused: props.isFocused,
				files: this.#result.files,
			};
		}

		componentDidMount()
		{
			this.props.onRightButtonsUpdate(this.#result, this.textEditorRef);
			this.#scrollToResult(this.#result.id);

			if (this.state.isFocused)
			{
				this.textEditorRef.getTextInput().focus();
			}
		}

		componentWillReceiveProps(props)
		{
			const { result } = props;
			if (result)
			{
				this.props.onRightButtonsUpdate(result, this.textEditorRef);
				this.#scrollToResult(result.id);
				this.state.files = result.files;
			}
		}

		#scrollToResult(resultId)
		{
			setTimeout(() => {
				const position = this.scrollRef?.getPosition(this.chipsRefs.get(resultId));
				if (position)
				{
					this.scrollRef?.scrollTo({
						x: position.x - device.screen.width / 2 + (this.chipsWidth.get(resultId) || 0) / 2,
						y: position.y,
						animated: true,
					});
				}
			}, 100);
		}

		get #testId()
		{
			return `TASK_RESULT_VIEW_${this.#result.id}`;
		}

		get #result()
		{
			return this.props.result;
		}

		get #isCreator()
		{
			return Number(this.#result.createdBy) === Number(env.userId);
		}

		render()
		{
			if (!this.#result)
			{
				return null;
			}

			return View(
				{
					style: {
						flex: 1,
					},
					testId: this.#testId,
				},
				this.#renderTextEditor(),
				this.#renderAnotherResults(),
				this.#renderActionButton(),
			);
		}

		#renderTextEditor()
		{
			return View(
				{
					style: {
						flex: 1,
					},
					testId: `${this.#testId}_EDITOR`,
				},
				new TextEditor({
					ref: (ref) => {
						this.textEditorRef = ref;
					},
					view: {
						style: {
							flex: 1,
						},
					},
					textInput: {
						style: {
							flex: 1,
						},
						placeholder: Loc.getMessage('TASKS_FIELDS_RESULT_ADD_WIDGET_PLACEHOLDER'),
					},
					readOnly: !this.#isCreator,
					value: this.#result.text,
					fileField: {
						config: {
							controller: {
								endpoint: 'tasks.FileUploader.TaskResultController',
								options: {
									taskId: this.props.taskId,
									commentId: this.#result.commentId,
								},
							},
							disk: {
								isDiskModuleInstalled: true,
								isWebDavModuleInstalled: true,
								fileAttachPath: `/mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId=${env.userId}`,
							},
							parentWidget: this.props.parentWidget,
						},
						value: this.state.files,
						onChange: (files) => this.setState({ files }),
					},
					onFocus: () => this.setState({ isFocused: true }),
					onBlur: () => this.setState({ isFocused: false }),
					onSave: ({ bbcode, files }) => this.props.onSave(this.#result, bbcode, files),
				}),
			);
		}

		#renderAnotherResults()
		{
			if (this.props.results.length <= 1)
			{
				return null;
			}

			return View(
				{
					style: {
						display: (this.state.isFocused ? 'none' : 'flex'),
						paddingVertical: Indent.L.toNumber(),
					},
				},
				Text5({
					style: {
						marginLeft: Indent.XL4.toNumber(),
					},
					color: Color.base3,
					text: Loc.getMessage('TASKS_FIELDS_RESULT_VIEW_ANOTHER_RESULTS'),
				}),
				ScrollView(
					{
						style: {
							flexDirection: 'row',
							height: 32,
							marginTop: Indent.XL.toNumber(),
						},
						horizontal: true,
						showsHorizontalScrollIndicator: false,
						ref: (ref) => {
							this.scrollRef = ref;
						},
					},
					View(
						{
							style: {
								flexDirection: 'row',
								paddingHorizontal: Indent.XL4.toNumber(),
							},
							testId: `${this.#testId}_ANOTHER_RESULTS`,
						},
						...this.props.results.map((result) => this.#renderResultChip(result)),
					),
				),
			);
		}

		#renderResultChip(result)
		{
			const isSelected = (result.id === this.#result.id);

			return Chip({
				style: {
					height: 32,
					marginRight: Indent.L.toNumber(),
				},
				indent: Indent.L,
				borderColor: (isSelected ? Color.accentSoftBorderBlue : Color.bgSeparatorPrimary),
				children: [
					Avatar({
						id: result.createdBy,
						size: 20,
						testId: `${this.#testId}_ANOTHER_RESULTS_CHIP_${result.id}_AVATAR`,
						withRedux: true,
					}),
					new Date({
						style: {
							marginLeft: Indent.XS.toNumber(),
							color: (isSelected ? Color.accentMainPrimary.toHex() : Color.base3.toHex()),
						},
						defaultFormat: (moment) => Loc.getMessage(
							'TASKS_FIELDS_RESULT_DATE_FORMAT',
							{
								'#DATE#': moment.format(moment.inThisYear ? dayMonth() : longDate()),
								'#TIME#': moment.format(shortTime),
							},
						),
						timeSeparator: '',
						showTime: true,
						useTimeAgo: true,
						timestamp: result.createdAt,
						testId: `${this.#testId}_ANOTHER_RESULTS_CHIP_${result.id}_DATE`,
					}),
				],
				testId: `${this.#testId}_ANOTHER_RESULTS_CHIP_${result.id}`,
				onClick: () => {
					if (result.id !== this.#result.id)
					{
						this.props.onResultSelectionChanged(result.id);
					}
				},
				onLayout: ({ width }) => this.chipsWidth.set(result.id, width),
				ref: (ref) => this.chipsRefs.set(result.id, ref),
			});
		}

		#renderActionButton()
		{
			const { taskId, taskActions, taskViewWidget, onClose } = this.props;

			const completeActionMeta = ActionMeta[ActionId.COMPLETE];
			const approveActionMeta = ActionMeta[ActionId.APPROVE];
			const disapproveActionMeta = ActionMeta[ActionId.DISAPPROVE];

			return View(
				{
					style: {
						display: (this.state.isFocused ? 'none' : 'flex'),
						flexDirection: 'row',
						paddingVertical: Indent.XL.toNumber(),
						paddingHorizontal: Indent.XL4.toNumber(),
					},
				},
				taskActions[ActionId.COMPLETE] && Button({
					text: Loc.getMessage('TASKS_FIELDS_RESULT_VIEW_ACTION_COMPLETE'),
					size: ButtonSize.L,
					design: ButtonDesign.FILLED,
					leftIcon: (completeActionMeta.getData().outlineIconContent ?? ''),
					stretched: true,
					testId: `${this.#testId}_ACTION_BUTTON_COMPLETE`,
					onClick: async () => {
						await completeActionMeta.handleAction({ taskId, layout: taskViewWidget });
						onClose?.();
					},
				}),
				taskActions[ActionId.APPROVE] && Button({
					style: {
						flex: 1,
					},
					text: approveActionMeta.title(),
					size: ButtonSize.M,
					design: ButtonDesign.OUTLINE_ACCENT_2,
					leftIcon: (approveActionMeta.getData().outlineIconContent ?? ''),
					leftIconColor: Color.accentMainSuccess,
					color: Color.accentMainSuccess,
					borderColor: Color.accentMainSuccess,
					stretched: true,
					testId: `${this.#testId}_ACTION_BUTTON_APPROVE`,
					onClick: () => {
						approveActionMeta.handleAction({ taskId, layout: taskViewWidget });
						onClose?.();
					},
				}),
				taskActions[ActionId.DISAPPROVE] && Button({
					style: {
						flex: 1,
						marginLeft: Indent.L.toNumber(),
					},
					text: disapproveActionMeta.title(),
					size: ButtonSize.M,
					design: ButtonDesign.OUTLINE_NO_ACCENT,
					leftIcon: (disapproveActionMeta.getData().outlineIconContent ?? ''),
					stretched: true,
					testId: `${this.#testId}_ACTION_BUTTON_DISAPPROVE`,
					onClick: () => {
						disapproveActionMeta.handleAction({ taskId, layout: taskViewWidget });
						onClose?.();
					},
				}),
			);
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const result = selectById(state, ownProps.resultId);
		const actions = selectActions(selectTaskById(state, ownProps.taskId));
		const taskActions = {
			[ActionId.COMPLETE]: actions.complete,
			[ActionId.APPROVE]: actions.approve,
			[ActionId.DISAPPROVE]: actions.disapprove,
		};

		if (!result)
		{
			return {
				taskActions,
				result,
				results: [],
			};
		}

		const {
			id,
			commentId,
			createdBy,
			createdAt,
			status,
			text,
			files,
		} = result;

		return {
			taskActions,
			result: {
				id,
				commentId,
				createdBy,
				createdAt,
				status,
				text,
				files,
			},
			results: selectByTaskId(state, ownProps.taskId),
		};
	};

	module.exports = {
		TaskResultViewContent: connect(mapStateToProps)(TaskResultViewReduxContent),
	};
});
