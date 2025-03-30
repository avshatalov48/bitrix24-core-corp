/**
 * @module sign/dialog/banners/refusedjustnow
 */
jn.define('sign/dialog/banners/refusedjustnow', (require, exports, module) => {
	const { Loc } = require('loc');
	const {
		ButtonSize,
		ButtonDesign,
		Button,
	} = require('ui-system/form/buttons/button');
	const { BannerTemplate } = require('sign/dialog/banners/template');
	const { InitiatedByType } = require('sign/type/initiated-by-type');
	const { MemberRole } = require('sign/type/member-role');
	const { BBCodeParser } = require('bbcode/parser');
	const { Color } = require('tokens');
	const parser = new BBCodeParser();

	class RefusedJustNow extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const {
				documentTitle,
				layoutWidget,
				initiatedByType,
				role,
			} = props;

			this.documentTitle = documentTitle;
			this.layoutWidget = layoutWidget;
			this.initiatedByType = initiatedByType;
			this.role = role;
		}

		closeLayout()
		{
			this.layoutWidget.close();
		}

		render()
		{
			let title = Loc.getMessage(
				MemberRole.isReviewerRole(this.role)
					? 'SIGN_MOBILE_DIALOG_REFUSED_JUST_NOW_BY_REVIEWER_TITLE'
					: 'SIGN_MOBILE_DIALOG_REFUSED_JUST_NOW_TITLE'
			);

			let description = Loc.getMessage(
				MemberRole.isReviewerRole(this.role)
					? 'SIGN_MOBILE_DIALOG_REFUSED_JUST_NOW_BY_REVIEWER_DESCRIPTION'
					: 'SIGN_MOBILE_DIALOG_REFUSED_JUST_NOW_DESCRIPTION',
				{
					'#DOCUMENT_TITLE#': parser.parse(this.documentTitle).toPlainText(),
					'#COLOR_OF_HIGHLIGHTED_TEXT#': Color.base1.toHex(),
				},
			);

			if (InitiatedByType.isInitiatedByEmployee(this.initiatedByType) && MemberRole.isSignerRole(this.role))
			{
				title = Loc.getMessage('SIGN_MOBILE_DIALOG_REFUSED_JUST_NOW_BY_EMPLOYEE_TITLE');
				description = Loc.getMessage('SIGN_MOBILE_DIALOG_REFUSED_JUST_NOW_BY_EMPLOYEE_DESCRIPTION');
			}

			return BannerTemplate({
				iconPathName: 'error.svg',
				title,
				description,
				buttonsView: View(
					{},
					Button({
						text: Loc.getMessage('SIGN_MOBILE_DIALOG_REFUSED_JUST_NOW_BUTTON_CLOSE_TITLE'),
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

	module.exports = { RefusedJustNow };
});