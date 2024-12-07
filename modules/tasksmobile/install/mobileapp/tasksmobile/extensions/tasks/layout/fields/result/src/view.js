/**
 * @module tasks/layout/fields/result/view
 */
jn.define('tasks/layout/fields/result/view', (require, exports, module) => {
	const { BottomSheet } = require('bottom-sheet');
	const { Loc } = require('loc');
	const { TaskResultViewContent } = require('tasks/layout/fields/result/view-redux-content');
	const { Menu } = require('tasks/layout/fields/result/menu');
	const { Color } = require('tokens');
	const { confirmDestructiveAction } = require('alert');

	const store = require('statemanager/redux/store');
	const { selectByTaskId } = require('tasks/statemanager/redux/slices/tasks-results');

	class TaskResultView extends LayoutComponent
	{
		/**
		 * @public
		 * @param {object} data
		 * @param {number} data.taskId
		 * @param {number} data.resultId
		 * @param {boolean} data.isFocused
		 * @param {PageManager} data.parentWidget
		 * @param {function} data.onSave
		 * @param {function} data.onRemove
		 */
		static open(data = {})
		{
			const taskResultView = new TaskResultView({
				taskId: data.taskId,
				resultId: data.resultId,
				isFocused: data.isFocused,
				taskViewWidget: data.parentWidget,
				onSave: data.onSave,
				onRemove: data.onRemove,
			});

			void new BottomSheet({
				titleParams: {
					text: Loc.getMessage('TASKS_FIELDS_RESULT_ADD_WIDGET_TITLE'),
					type: 'dialog',
				},
				component: (widget) => {
					taskResultView.parentWidget = widget;

					return taskResultView;
				},
			})
				.setParentWidget(data.parentWidget || PageManager)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.alwaysOnTop()
				.open()
			;
		}

		constructor(props)
		{
			super(props);

			this.parentWidget = null;
			this.menu = new Menu();

			this.state = {
				resultId: props.resultId,
			};

			this.onResultSelectionChanged = this.#onResultSelectionChanged.bind(this);
			this.onRightButtonsUpdate = this.#onRightButtonsUpdate.bind(this);
			this.onClose = this.#onClose.bind(this);
		}

		render()
		{
			return View(
				{
					safeArea: {
						bottom: true,
					},
					resizableByKeyboard: true,
				},
				TaskResultViewContent({
					taskId: this.props.taskId,
					resultId: this.state.resultId,
					isFocused: this.props.isFocused,
					taskViewWidget: this.props.taskViewWidget,
					parentWidget: this.parentWidget,
					onSave: this.props.onSave,
					onResultSelectionChanged: this.onResultSelectionChanged,
					onRightButtonsUpdate: this.onRightButtonsUpdate,
					onClose: this.onClose,
				}),
			);
		}

		/**
		 * @private
		 * @param {number} resultId
		 */
		#onResultSelectionChanged(resultId)
		{
			this.setState({ resultId });
		}

		/**
		 * @private
		 * @param {object} result
		 * @param {TextEditor} textEditorRef
		 */
		#onRightButtonsUpdate(result, textEditorRef)
		{
			const rightButtons = [];

			if (this.#isCreator(result))
			{
				rightButtons.push({
					type: 'more',
					callback: () => {
						this.menu.show({
							onUpdate: () => textEditorRef?.getTextInput().focus(),
							onRemove: () => {
								confirmDestructiveAction({
									title: Loc.getMessage('TASKS_FIELDS_RESULT_REMOVE_CONFIRM_TITLE_V2'),
									description: Loc.getMessage('TASKS_FIELDS_RESULT_REMOVE_CONFIRM_DESCRIPTION'),
									destructionText: Loc.getMessage('TASKS_FIELDS_RESULT_REMOVE_CONFIRM_YES'),
									onDestruct: () => this.#onRemove(result),
								});
							},
						});
					},
				});
			}

			this.parentWidget.setRightButtons(rightButtons);
		}

		/**
		 * @private
		 * @param {object} result
		 */
		#onRemove(result)
		{
			const results = selectByTaskId(store.getState(), this.props.taskId);
			if (results.length === 1)
			{
				this.props.onRemove(result.commentId);
				this.#onClose();
			}
			else
			{
				const resultIndex = results.findIndex((item) => item.id === result.id);
				if (resultIndex !== -1)
				{
					const newResultToSelect = results[resultIndex + (resultIndex === results.length - 1 ? -1 : 1)];
					this.setState(
						{ resultId: newResultToSelect.id },
						() => this.props.onRemove(result.commentId),
					);
				}
			}
		}

		#onClose()
		{
			this.parentWidget?.close();
		}

		/**
		 * @private
		 * @param {object} result
		 * @returns {boolean}
		 */
		#isCreator(result)
		{
			return Number(result?.createdBy) === Number(env.userId);
		}
	}

	module.exports = { TaskResultView };
});
