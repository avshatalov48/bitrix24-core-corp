/**
 * @module tasks/layout/checklist/list/src/checkbox/checkbox-counter
 */
jn.define('tasks/layout/checklist/list/src/checkbox/checkbox-counter', (require, exports, module) => {
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
					testId: this.getTestId(),
					style: {
						width: CHECKBOX_LAYOUT_SIZE,
						height: CHECKBOX_LAYOUT_SIZE,
						alignItems: 'center',
						justifyContent: 'center',
					},
					onClick: this.#handleOnClick,
				},
				this.renderCheckbox(),
				new ChecklistImportant({
					ref: this.#setRef,
					important,
					onClick: this.#handleOnClick,
				}),
			);
		}

		renderCheckbox()
		{
			const { checked } = this.props;

			return new Checkbox({
				testId: this.getTestId(),
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
			const { checked } = this.props;
			const { progressMode, totalCount, completedCount, disabled } = this.props;
			const isShowProgress = progressMode && !checked;

			return View(
				{
					testId: checked ? 'select_block' : 'unselect_block',
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
		 * @private
		 */
		#handleOnClick = () => {
			const { disabled, checked, onClick, showToastNoRights } = this.props;

			if (disabled)
			{
				showToastNoRights();

				return;
			}

			if (onClick)
			{
				onClick(!checked);
			}
		};

		getTestId(suffix)
		{
			return 'checkbox_block';
		}

		#setRef = (ref) => {
			this.importantRef = ref;
		};
	}

	module.exports = { CheckBoxCounter };
});
