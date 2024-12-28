/**
 * @module sign/dialog/banners/processing
 */
jn.define('sign/dialog/banners/processing', (require, exports, module) => {
	const { Loc } = require('loc');
	const {
		ButtonSize,
		ButtonDesign,
		Button,
	} = require('ui-system/form/buttons/button');
	const { Color } = require('tokens');
	const { BannerTemplate } = require('sign/dialog/banners/template');
	const { InitiatedByType } = require('sign/type/initiated-by-type');

	class Processing extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const {
				layoutWidget,
				initiatedByType,
			} = props;

			this.layoutWidget = layoutWidget;
			this.initiatedByType = initiatedByType;
		}

		closeLayout()
		{
			this.layoutWidget.close();
		}

		render()
		{
			const descriptionCode  = InitiatedByType.isInitiatedByEmployee(this.initiatedByType)
				? 'SIGN_MOBILE_DIALOG_PROCESSING_BY_EMPLOYEE_DESCRIPTION'
				: 'SIGN_MOBILE_DIALOG_PROCESSING_DESCRIPTION_MSGVER_1'
			;

			return BannerTemplate({
				iconPathName: 'signed.svg',
				title: Loc.getMessage(
					'SIGN_MOBILE_DIALOG_PROCESSING_TITLE_MSGVER_1',
					{ '#DOCUMENT_TITLE#': String(this.props.documentTitle) }
				),
				description: Loc.getMessage(
					descriptionCode,
					{ '#COLOR_OF_HIGHLIGHTED_TEXT#': Color.base1.toHex() },
				),
				buttonsView: View(
					{},
					Button({
						text: Loc.getMessage('SIGN_MOBILE_DIALOG_PROCESSING_BUTTON_CLOSE'),
						size: ButtonSize.XL,
						design: ButtonDesign.PLAN_ACCENT,
						disabled: false,
						badge: false,
						stretched: true,
						onClick: () => {
							this.closeLayout();
						},
					}),
				),
			});
		}
	}

	module.exports = { Processing };
});