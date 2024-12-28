/**
 * @module tasks/layout/fields/user-fields
 */
jn.define('tasks/layout/fields/user-fields', (require, exports, module) => {
	const { Loc } = require('tasks/loc');
	const { showOfflineToast } = require('toast');
	const { Color } = require('tokens');
	const { BottomSheet } = require('bottom-sheet');
	const { isOnline } = require('device/connection');
	const { UserFieldsEdit } = require('tasks/layout/fields/user-fields/edit');
	const { getFeatureRestriction } = require('tariff-plan-restriction');
	const { FeatureId } = require('tasks/enum');

	/**
	 * @class UserFieldsField
	 */
	class UserFieldsField extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.bindContainerRef = this.bindContainerRef.bind(this);
			this.onContentClick = this.onContentClick.bind(this);
		}

		render()
		{
			if (this.props.ThemeComponent)
			{
				return this.props.ThemeComponent(this);
			}

			return null;
		}

		onContentClick()
		{
			if (this.isRestricted())
			{
				this.#showTariffPlanRestriction();

				return;
			}

			this.props.onContentClick?.(this);

			if (this.isReadOnly())
			{
				return;
			}

			if (!isOnline())
			{
				showOfflineToast({}, this.parentWidget);

				return;
			}

			this.openUserFieldsEdit();
		}

		#showTariffPlanRestriction()
		{
			getFeatureRestriction(FeatureId.USER_FIELDS).showRestriction({
				parentWidget: this.parentWidget,
				onHidden: () => this.props.onEditWidgetClose?.(),
			});
		}

		openUserFieldsEdit(shouldShowErrors = false)
		{
			new BottomSheet({
				titleParams: {
					text: Loc.getMessage('M_TASKS_FIELDS_USER_FIELDS'),
					type: 'dialog',
				},
				component: (layout) => UserFieldsEdit({
					layout,
					shouldShowErrors,
					taskId: this.taskId,
					userFields: this.userFields,
					testId: this.testId,
					onChange: this.props.onChange,
				}),
			})
				.setParentWidget(this.parentWidget || PageManager)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.alwaysOnTop()
				.open()
				.then((layout) => layout.on('onViewHidden', () => this.props.onEditWidgetClose?.()))
				.catch(console.error)
			;
		}

		/**
		 * @public
		 * @returns {number|string}
		 */
		get taskId()
		{
			return this.props.taskId;
		}

		/**
		 * @return {array}
		 */
		get userFields()
		{
			return this.props.userFields;
		}

		/**
		 * @public
		 * @returns {string}
		 */
		get testId()
		{
			return this.props.testId;
		}

		/**
		 * @public
		 * @returns {PageManager}
		 */
		get parentWidget()
		{
			return this.props.config.parentWidget;
		}

		/**
		 * @public
		 * @returns {number}
		 */
		getFilledUserFieldsCount()
		{
			if (!this.isLoaded())
			{
				return 0;
			}

			let count = 0;

			this.userFields.forEach((userField) => {
				count += (
					userField.isMultiple
						? userField.value.some((value) => value !== '')
						: userField.value !== ''
				);
			});

			return count;
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isLoaded()
		{
			return this.props.areUserFieldsLoaded;
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		isEmpty()
		{
			return this.isLoaded() && this.getFilledUserFieldsCount() === 0;
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		isReadOnly()
		{
			return this.props.readOnly;
		}

		isRestricted()
		{
			return getFeatureRestriction(FeatureId.USER_FIELDS).isRestricted();
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		validate()
		{
			return true;
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		isValid()
		{
			return true;
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		isRequired()
		{
			return false;
		}

		/**
		 * @public
		 * @returns {string}
		 */
		getId()
		{
			return this.props.id;
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		hasUploadingFiles()
		{
			return false;
		}

		/**
		 * @public
		 * @param ref
		 */
		bindContainerRef(ref)
		{
			this.fieldContainerRef = ref;
		}

		/**
		 * @return {boolean}
		 */
		getCustomContentClickHandler()
		{
			return false;
		}
	}

	module.exports = {
		UserFieldsField: (props) => new UserFieldsField(props),
		UserFieldsFieldClass: UserFieldsField,
	};
});
