/**
 * @module crm/timeline/scheduler/providers/sharing/layout
 */
jn.define('crm/timeline/scheduler/providers/sharing/layout', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { withPressed } = require('utils/color');
	const { link } = require('assets/icons/src/outline');

	const { Skeleton } = require('crm/timeline/scheduler/providers/sharing/skeleton');
	const { DialogSharing } = require('calendar/layout/dialog/dialog-sharing');

	class Layout extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				isLoading: true,
			};
		}

		setParams({ readOnly })
		{
			this.setState({
				isLoading: false,
				readOnly,
			});
		}

		render()
		{
			const { isLoading } = this.state;

			return View(
				{},
				isLoading && Skeleton(),
				!isLoading && this.renderSharingLayout(),
				!isLoading && this.renderButtons(),
			);
		}

		renderSharingLayout()
		{
			const { sharing, customEventEmitter, onSettingsClick, layoutWidget } = this.props;
			const { readOnly } = this.state;

			return new DialogSharing({
				onSettingsClick,
				layoutWidget,
				readOnly,
				sharing,
				customEventEmitter,
				onSharing: (fields) => sharing.getModel().setFields(fields),
			});
		}

		renderButtons()
		{
			return View(
				{},
				this.renderSendButton(),
				this.renderCopyLinkButton(),
			);
		}

		renderSendButton()
		{
			return View(
				{
					testId: 'crm-sharing-send-button',
					style: styles.sendButtonContainer,
					onClick: this.props.onSendButtonClick,
				},
				Text({
					style: styles.sendButtonText,
					text: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_SEND_LINK'),
				}),
			);
		}

		renderCopyLinkButton()
		{
			return View(
				{
					testId: 'crm-sharing-copy-link-button',
					style: styles.copyLinkButtonContainer,
					onClick: this.props.onCopyLinkButtonClick,
				},
				Image({
					tintColor: AppTheme.colors.base3,
					svg: {
						content: link(),
					},
					style: {
						height: 24,
						width: 24,
					},
				}),
				Text({
					style: styles.copyLinkButtonText,
					text: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_COPY_LINK'),
				}),
			);
		}
	}

	const styles = {
		sendButtonContainer: {
			alignItems: 'center',
			marginTop: 16,
			marginHorizontal: 45,
			paddingVertical: 14,
			borderRadius: 6,
			backgroundColor: withPressed(AppTheme.colors.accentMainPrimaryalt),
		},
		sendButtonText: {
			fontSize: 17,
			fontWeight: '500',
			ellipsize: 'end',
			numberOfLines: 1,
			color: AppTheme.colors.base8,
		},
		copyLinkButtonContainer: {
			flexDirection: 'row',
			justifyContent: 'center',
			marginTop: 15,
			marginHorizontal: 45,
		},
		copyLinkButtonText: {
			fontSize: 15,
			fontWeight: '400',
			marginLeft: 8,
			color: AppTheme.colors.base2,
		},
	};

	module.exports = { Layout };
});
