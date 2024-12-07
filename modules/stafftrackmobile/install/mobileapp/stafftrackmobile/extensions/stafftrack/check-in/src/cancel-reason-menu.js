/**
 * @module stafftrack/check-in/cancel-reason-menu
 */

jn.define('stafftrack/check-in/cancel-reason-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const { Icon } = require('ui-system/blocks/icon');

	const { CancelReasonEnum } = require('stafftrack/model/shift');
	const { BaseMenu, baseSectionType, customSectionType } = require('stafftrack/base-menu');

	const cancelReasonList = jnExtensionData.get('stafftrack:check-in').cancelReasonList;

	/**
	 * @class CancelReasonMenu
	 */
	class CancelReasonMenu extends BaseMenu
	{
		getItems()
		{
			return [
				this.getItem(CancelReasonEnum.ILLNESS.getValue()),
				this.getItem(CancelReasonEnum.SICK_LEAVE.getValue()),
				this.getItem(CancelReasonEnum.TIME_OFF.getValue()),
				this.getItem(CancelReasonEnum.VACATION.getValue()),
				this.getItem(CancelReasonEnum.CUSTOM.getValue(), true),
			];
		}

		getItem(code, customSection = false)
		{
			return {
				id: code,
				testId: `stafftrack-cancel-reason-menu-${code}`,
				sectionCode: customSection ? customSectionType : baseSectionType,
				title: cancelReasonList[code],
				iconName: this.getIconName(code),
				styles: {
					icon: {
						color: Color.base3.toHex(),
					},
				},
			};
		}

		getSections()
		{
			return [
				{
					id: baseSectionType,
					title: Loc.getMessage('M_STAFFTRACK_CHECK_IN_CANCEL_REASON_MENU_TITLE'),
					styles: {
						title: {
							font: {
								size: 16,
								color: Color.base1.toHex(),
								fontStyle: 'bold',
							},
						},
					},
				},
				{
					id: customSectionType,
					title: '',
				},
			];
		}

		onItemSelected(item)
		{
			if (this.props.onItemSelected)
			{
				this.props.onItemSelected(item.id);
			}
		}

		getIconName(code)
		{
			switch (code)
			{
				case CancelReasonEnum.ILLNESS.getValue():
					return Icon.SAD.getIconName();
				case CancelReasonEnum.SICK_LEAVE.getValue():
					return Icon.SICK.getIconName();
				case CancelReasonEnum.TIME_OFF.getValue():
					return Icon.DAY_OFF.getIconName();
				case CancelReasonEnum.VACATION.getValue():
					return Icon.VACATION.getIconName();
				case CancelReasonEnum.CUSTOM.getValue():
					return Icon.CHEVRON_TO_THE_RIGHT.getIconName();
				default:
					return null;
			}
		}
	}

	module.exports = { CancelReasonMenu };
});
