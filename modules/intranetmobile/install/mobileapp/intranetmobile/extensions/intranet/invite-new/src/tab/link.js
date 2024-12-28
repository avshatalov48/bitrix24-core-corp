/**
 * @module intranet/invite-new/src/tab/link
 */
jn.define('intranet/invite-new/src/tab/link', (require, exports, module) => {
	const { BaseTab } = require('intranet/invite-new/src/tab/base');
	const { Loc } = require('loc');
	const { Color, Component, Indent } = require('tokens');
	const { Icon } = require('ui-system/blocks/icon');
	const { Button, ButtonDesign, ButtonSize } = require('ui-system/form/buttons/button');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { Text3 } = require('ui-system/typography/text');

	class LinkTab extends BaseTab
	{
		get sharingMessage()
		{
			return this.props.sharingMessage ?? '';
		}

		get inviteLink()
		{
			return this.props.inviteLink ?? '';
		}

		get adminConfirm()
		{
			return this.props.adminConfirm ?? '';
		}

		get analytics()
		{
			return this.props.analytics ?? {};
		}

		renderTabContent()
		{
			return View(
				{
					style: {
						flex: 1,
						width: '100%',
					},
				},
				this.renderGraphics('link'),
				this.renderShareLinkCard(),
			);
		}

		renderShareLinkCard()
		{
			return Card(
				{
					testId: `${this.testId}-shared-link-card`,
					border: false,
					style: {
						paddingVertical: Component.cardPaddingB.toNumber(),
						paddingHorizontal: Component.cardPaddingLr.toNumber(),
					},
					design: CardDesign.SECONDARY,
				},
				Text3({
					testId: `${this.testId}-shared-link-card-text`,
					text: Loc.getMessage('INTRANET_SHARE_LINK_CARD_TEXT'),
					color: Color.base1,
					numberOfLines: 0,
					ellipsize: 'end',
					style: {
						paddingHorizontal: Indent.L.toNumber(),
						textAlign: 'center',
					},
				}),
			);
		}

		renderButton()
		{
			return View(
				{
					style: {
						width: '100%',
					},
				},
				Button({
					testId: `${this.testId}-share-link-button`,
					text: Loc.getMessage('INTRANET_SHARE_LINK_BUTTON_TEXT'),
					design: ButtonDesign.FILLED,
					size: ButtonSize.L,
					stretched: true,
					style: {
						width: '100%',
						paddingHorizontal: Indent.XL4.toNumber(),
						marginVertical: Indent.XL.toNumber(),
					},
					leftIcon: Icon.SHARE,
					onClick: () => {
						this.analytics.sendShareLinkEvent(this.adminConfirm);
						dialogs.showSharingDialog({
							title: Loc.getMessage('INTRANET_SHARING_LINK_DIALOG_TITLE'),
							message: this.getSharingMessageWithLink(),
						});
					},
				}),
			);
		}

		getSharingMessageWithLink()
		{
			return this.sharingMessage.replaceAll('#link#', this.inviteLink);
		}
	}

	module.exports = { LinkTab };
});
