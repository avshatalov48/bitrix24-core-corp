/**
 * @module tasks/layout/checklist/list/src/checkbox/checkbox-counter
 */
jn.define('tasks/layout/checklist/list/src/checkbox/checkbox-counter', (require, exports, module) => {
	const { useCallback } = require('utils/function');
	// const { CheckBox } = require('layout/ui/checkbox');
	const { Checkbox } = require('ui-system/form/checkbox');
	const { PureComponent } = require('layout/pure-component');
	const { ChecklistImportant } = require('tasks/layout/checklist/list/src/checkbox/checkbox-counter/important');
	const { ChecklistCheckboxProgress } = require('tasks/layout/checklist/list/src/checkbox/checkbox-counter/progress');
	const { CHECKBOX_SIZE } = require('tasks/layout/checklist/list/src/constants');

	const CHECKBOX_LAYOUT_SIZE = 28;

	/**
	 * @class CheckBoxCounter
	 */
	class CheckBoxCounter extends PureComponent
	{
		constructor(props)
		{
			super(props);

			/** @type {ChecklistImportant} */
			this.importantRef = null;

			this.handleOnClick = this.handleOnClick.bind(this);

			this.initialState(props);
		}

		componentWillReceiveProps(props)
		{
			this.initialState(props);
		}

		initialState(props)
		{
			const { checked } = props;

			this.state = { checked };
		}

		/**
		 * @param {boolean} important
		 * @returns {Promise<void>}
		 */
		toggleAnimateImportant(important)
		{
			if (this.importantRef)
			{
				return this.importantRef.toggleAnimateImportant(important);
			}

			return Promise.resolve();
		}

		/**
		 * @private
		 * @returns {View}
		 */
		render()
		{
			const { important } = this.props;

			return View(
				{
					style: {
						width: CHECKBOX_LAYOUT_SIZE,
						height: CHECKBOX_LAYOUT_SIZE,
						alignItems: 'center',
						justifyContent: 'center',
					},
					onClick: this.handleOnClick,
				},
				this.renderCheckbox(),
				new ChecklistImportant({
					ref: useCallback((ref) => {
						this.importantRef = ref;
					}),
					important,
				}),
			);
		}

		renderCheckbox()
		{
			const { checked } = this.state;

			return new Checkbox({
				checked,
				useState: false,
				size: 18,
			});
		}

		/**
		 * @private
		 * @returns {ChecklistCheckboxProgress|CheckBox}
		 */
		renderProgressCheckbox()
		{
			const { checked } = this.state;
			const { progressMode, totalCount, completedCount, disabled } = this.props;
			const isShowProgress = progressMode && !checked;

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'center',
						width: CHECKBOX_SIZE,
						height: CHECKBOX_SIZE,
						borderRadius: 11.5,
						opacity: disabled ? 0.5 : 1,
					},
					clickable: false,
				},
				isShowProgress
					? new ChecklistCheckboxProgress({ totalCount, completedCount })
					: new Checkbox({ checked, isDisabled: true }),
			);
		}

		/**
		 * @public
		 */
		executeCheckbox()
		{
			const { checked } = this.state;

			return new Promise((resolve) => {
				this.setState({ checked: !checked }, resolve);
			});
		}

		/**
		 * @private
		 */
		handleOnClick()
		{
			const { disabled, checked, onClick } = this.props;
			if (disabled)
			{
				return;
			}

			this.executeCheckbox().then(() => {
				if (onClick)
				{
					onClick(!checked);
				}
			}).catch(console.error);
		}
	}

	module.exports = { CheckBoxCounter };
});
