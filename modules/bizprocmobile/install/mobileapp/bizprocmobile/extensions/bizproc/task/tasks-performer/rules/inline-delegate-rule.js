/**
 * @module bizproc/task/tasks-performer/rules/inline-delegate-rule
 */
jn.define('bizproc/task/tasks-performer/rules/inline-delegate-rule', (require, exports, module) => {
	const { useCallback } = require('utils/function');
	const { Rule } = require('bizproc/task/tasks-performer/rules/rule');
	const { ButtonsWrapper, DelegateButton } = require('bizproc/task/buttons');

	class InlineDelegateRule extends Rule
	{
		/**
		 * @param {[]} tasks
		 * @param {Object} targetTask
		 */
		static isApplicable(tasks, targetTask)
		{
			return true;
		}

		/**
		 * @param props
		 * @param {?boolean} props.compactView
		 */
		constructor(props)
		{
			super(props);

			this.onDelegateButtonClick = this.onDelegateButtonClick.bind(this);
		}

		renderEntryPoint()
		{
			const buttonProps = {
				style: { width: '100%' },
				onCloseSelector: useCallback(this.onDelegateButtonClick),
				testId: 'MBP_TASKS_PERFORMER_RULES_DELEGATE_BUTTON',
			};

			if (BX.prop.getBoolean(this.props, 'compactView', false))
			{
				buttonProps.text = '';
			}

			return ButtonsWrapper({}, new DelegateButton(buttonProps));
		}

		calculateEntryPointButtons()
		{
			return 1;
		}

		/**
		 * @param {?number} toUserId
		 */
		onDelegateButtonClick(toUserId)
		{
			if (toUserId === null)
			{
				return;
			}

			this.delegateTasks(this.tasks, { toUserId, fromUserId: env.userId })
				.then(() => {})
				.catch(() => {})
			;

			this.onFinishRule();
		}
	}

	module.exports = { InlineDelegateRule };
});
