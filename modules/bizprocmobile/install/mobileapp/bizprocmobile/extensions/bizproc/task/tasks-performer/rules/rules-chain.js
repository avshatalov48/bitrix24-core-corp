/**
 * @module bizproc/task/tasks-performer/rules/rules-chain
 */
jn.define('bizproc/task/tasks-performer/rules/rules-chain', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Alert } = require('alert');
	const { Loc } = require('loc');
	const { clone } = require('utils/object');
	const { Type } = require('type');

	const { PureComponent } = require('layout/pure-component');

	const { InlineDelegateRule } = require('bizproc/task/tasks-performer/rules/inline-delegate-rule');
	const { InlineTaskRule } = require('bizproc/task/tasks-performer/rules/inline-task-rule');
	const { SequentialTaskRule } = require('bizproc/task/tasks-performer/rules/sequential-task-rule');

	class RulesChain extends PureComponent
	{
		/**
		 * @param props
		 * @param {{}} props.layout
		 * @param {[]} props.tasks
		 * @param {Function} props.onFinishRule
		 * @param props.notifier
		 * @param {?boolean} props.useInlineDelegation
		 */
		constructor(props)
		{
			super(props);

			this.state = {
				width: null,
			};
		}

		get tasks()
		{
			return this.props.tasks ? clone(this.props.tasks) : [];
		}

		get notifier()
		{
			return this.props.notifier;
		}

		get layout()
		{
			return this.props.layout || layout || {};
		}

		render()
		{
			return View(
				{
					style: { height: 42 },
					onLayout: (props) => {
						if (this.state.width === null)
						{
							this.setState({ width: props.width });
						}
					},
				},
				this.state.width && this.renderButtons(),
			);
		}

		renderButtons()
		{
			const width = this.state.width;

			const ruleProps = {
				layout: this.layout,
				tasks: this.tasks,
				onTasksCancel: this.onTasksCancel.bind(this),
				onTasksCompleted: this.onTasksCompleted.bind(this),
				onTasksDelegated: this.onTasksDelegated.bind(this),
				onTaskNotFoundError: this.onTaskNotFoundError.bind(this),
				onFinishRule: this.onFinishRule.bind(this),
				generateExitButton: this.#generateExitButton,
			};

			const doTaskRule = (
				InlineTaskRule.isApplicable(this.tasks)
					? new InlineTaskRule(ruleProps)
					: new SequentialTaskRule(ruleProps)
			);

			const useInlineDelegation = BX.prop.getBoolean(this.props, 'useInlineDelegation', true);

			const oneButton = doTaskRule.calculateEntryPointButtons() === 1;

			const sidePadding = 18;
			const innerPadding = 6;
			const delegateWrapperWidth = (
				useInlineDelegation
					? innerPadding + (8 * 2) + 28 + sidePadding // padding 8, icon 28
					: 0
			);

			return View(
				{ style: { flexDirection: 'row' } },
				View(
					{
						style: {
							maxWidth: oneButton && useInlineDelegation ? width * 0.5 : width - delegateWrapperWidth,
							paddingLeft: sidePadding,
							paddingRight: useInlineDelegation ? innerPadding : sidePadding,
						},
					},
					doTaskRule.renderEntryPoint(),
				),
				useInlineDelegation && View(
					{
						style: {
							maxWidth: oneButton ? width * 0.5 : delegateWrapperWidth,
							paddingLeft: innerPadding,
							paddingRight: sidePadding,
						},
					},
					(new InlineDelegateRule({ ...ruleProps, compactView: !oneButton })).renderEntryPoint(),
				),
			);
		}

		onTaskNotFoundError(tasks)
		{
			this.notifier.decreaseAmountTasks(tasks.length);
		}

		onTasksCancel(tasks)
		{
			this.notifier
				.decreaseAmountTasks(tasks.length)
				.showTasksCanceledToast(tasks.length)
			;
		}

		onTasksCompleted(completedTasks)
		{
			this.notifier
				.increaseCompletedTasks(completedTasks.length)
				.showTasksCompletedToast(completedTasks.length)
			;
		}

		onTasksDelegated(delegatedTasks)
		{
			this.notifier
				.increaseCompletedTasks(delegatedTasks.length)
				.showTasksDelegatedToast(delegatedTasks.length)
			;
		}

		onFinishRule(completedTasks, delegatedTasks, isEarlyFinish)
		{
			if (isEarlyFinish)
			{
				this.notifier.showEarlyFinishToast();
			}

			if (Type.isFunction(this.props.onFinishRule))
			{
				this.props.onFinishRule(completedTasks, delegatedTasks);
			}
		}

		#generateExitButton(onSubmitExit)
		{
			return {
				type: 'text',
				color: AppTheme.colors.accentMainLinks,
				name: Loc.getMessage('MBP_TASK_TASKS_PERFORMER_RULES_FINISH_RULES_BUTTON_EXIT'),
				callback: () => {
					Alert.confirm(
						Loc.getMessage('MBP_TASK_TASKS_PERFORMER_RULES_FINISH_RULES_CONFIRM_TEXT'),
						Loc.getMessage('MBP_TASK_TASKS_PERFORMER_RULES_FINISH_RULES_CONFIRM_DESCRIPTION'),
						[
							{
								text: Loc.getMessage('MBP_TASK_TASKS_PERFORMER_RULES_FINISH_RULES_BUTTON_YES'),
								type: 'default',
								onPress: onSubmitExit,
							},
							{
								text: Loc.getMessage('MBP_TASK_TASKS_PERFORMER_RULES_FINISH_RULES_BUTTON_NO'),
								type: 'default',
								onPress: () => {},
							},
						],
					);
				},
			};
		}
	}

	module.exports = { RulesChain };
});
