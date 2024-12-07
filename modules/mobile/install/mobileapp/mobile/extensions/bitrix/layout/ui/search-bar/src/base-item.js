/**
 * @module layout/ui/search-bar/base-item
 */
jn.define('layout/ui/search-bar/base-item', (require, exports, module) => {
	const { Indent } = require('tokens');
	const { Haptics } = require('haptics');
	const { Loc } = require('loc');
	const { ChipFilter, BadgeCounterDesign } = require('ui-system/blocks/chips/chip-filter');

	class BaseItem extends LayoutComponent
	{
		/**
		 * @private
		 * @return {object}
		 */
		render()
		{
			const { active, last, id, value } = this.props;

			return ChipFilter(
				{
					testId: id,
					text: this.props.name,
					selected: active,
					counterValue: value,
					counterDesign: BadgeCounterDesign.ALERT,
					onClick: () => this.onClick(),
					style: {
						marginRight: (last) ? 0 : Indent.M.toNumber(),
						flexShrink: null,
						flexGrow: 2,
					},
					textStyles: {
						maxWidth: 250,
					},
				},
			);
		}

		/**
		 * @protected
		 * @return {string|null}
		 */
		getSearchButtonBackgroundColor()
		{
			this.abstract();

			return null;
		}

		/**
		 * @private
		 */
		onClick()
		{
			if (this.isDisabled())
			{
				Haptics.notifyWarning();
				Notify.showUniqueMessage(
					this.getUnsupportedFieldsNamesMessage(),
					Loc.getMessage('M_CRM_ET_SEARCH_ITEM_IS_DISABLED_TITLE'),
					{ time: 4 },
				);

				return;
			}

			Haptics.impactLight();

			const params = this.getOnClickParams();
			const active = !this.props.active;

			this.props.onClick(params, active);
		}

		/**
		 * @private
		 * @return {boolean}
		 */
		isDisabled()
		{
			return BX.prop.getBoolean(this.props, 'disabled', false);
		}

		/**
		 * @private
		 * @return {boolean}
		 */
		isDefault()
		{
			return Boolean(this.props.default);
		}

		/**
		 * @private
		 * @return {string}
		 */
		getUnsupportedFieldsNamesMessage()
		{
			const fields = this.getUnsupportedFields();
			if (fields.length === 0)
			{
				return '';
			}

			const fieldNameTemplate = Loc.getMessage('M_CRM_ET_SEARCH_ITEM_IS_DISABLED_MESSAGE_FILED_TEMPLATE');
			const fieldNames = fields.map((field) => fieldNameTemplate.replace('#FIELD_NAME#', field.name));
			const messageCode = (
				fields.length === 1
					? 'M_CRM_ET_SEARCH_ITEM_IS_DISABLED_MESSAGE_ONE_FIELD'
					: 'M_CRM_ET_SEARCH_ITEM_IS_DISABLED_MESSAGE_MANY_FIELDS'
			);

			return Loc.getMessage(messageCode)
				.replace('#FIELD_NAME_FORMATTED#', fieldNames.join(', '))
			;
		}

		/**
		 * @private
		 * @return {{name: string}[]}
		 */
		getUnsupportedFields()
		{
			const { unsupportedFields = [] } = this.props;

			return unsupportedFields;
		}

		/**
		 * @protected
		 * @return {object}
		 */
		getOnClickParams()
		{
			const params = {};
			const buttonBackgroundColor = this.getSearchButtonBackgroundColor();
			if (buttonBackgroundColor)
			{
				params.searchButtonBackgroundColor = buttonBackgroundColor;

				// backward compatibility
				params.data = {
					background: buttonBackgroundColor,
				};
			}

			return params;
		}

		/**
		 * @private
		 * @param msg
		 */
		abstract(msg)
		{
			throw new Error(msg || 'Abstract method must be implemented in child class');
		}
	}

	module.exports = { BaseItem };
});
