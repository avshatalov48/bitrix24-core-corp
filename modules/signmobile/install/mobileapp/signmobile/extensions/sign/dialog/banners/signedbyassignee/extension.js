/**
 * @module sign/dialog/banners/signedbyassignee
 */
jn.define('sign/dialog/banners/signedbyassignee', (require, exports, module) => {
	const { Loc } = require('loc');
	const {
		ButtonSize,
		ButtonDesign,
		Button,
	} = require('ui-system/form/buttons/button');
	const { BannerTemplate } = require('sign/dialog/banners/template');
	const { Color } = require('tokens');
	const { BBCodeParser } = require('bbcode/parser');
	const { InitiatedByType } = require('sign/type/initiated-by-type');
	const parser = new BBCodeParser();

	class SignedByAssignee extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const {
				layoutWidget,
				documentTitle,
				initiatedByType,
			} = props;

			this.documentTitle = documentTitle;
			this.layoutWidget = layoutWidget;
			this.initiatedByType = initiatedByType;
		}

		closeLayout()
		{
			this.layoutWidget.close();
		}

		render()
		{
			const { titleCode, descriptionCode } = InitiatedByType.isInitiatedByEmployee(this.initiatedByType)
				? { titleCode: 'SIGN_MOBILE_DIALOG_SIGNED_BY_ASSIGNEE_BY_EMPLOYEE_TITLE',  descriptionCode: 'SIGN_MOBILE_DIALOG_SIGNED_BY_ASSIGNEE_BY_EMPLOYEE_DESCRIPTION'}
				: { titleCode: 'SIGN_MOBILE_DIALOG_SIGNED_BY_ASSIGNEE_TITLE',  descriptionCode: 'SIGN_MOBILE_DIALOG_SIGNED_BY_ASSIGNEE_DESCRIPTION'}
			;

			return BannerTemplate({
				iconPathName: 'signed.svg',
				title: Loc.getMessage(titleCode),
				description: Loc.getMessage(
					descriptionCode,
					{
						'#DOCUMENT_TITLE#': parser.parse(this.documentTitle).toPlainText(),
						'#COLOR_OF_HIGHLIGHTED_TEXT#': Color.base1.toHex(),
					},
				),
				buttonsView: View(
					{},
					Button({
						text: Loc.getMessage('SIGN_MOBILE_DIALOG_SIGNED_SIGNED_BY_ASSIGNEE_BUTTON_CLOSE'),
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

	module.exports = { SignedByAssignee };
});