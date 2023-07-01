/**
 * @module imconnector/connectors/telegram/view/edit
 */
jn.define('imconnector/connectors/telegram/view/edit', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Alert, makeCancelButton, makeDestructiveButton } = require('alert');
	const { QueueField } = require('imconnector/connector/telegram/layout-components/queue-field');
	const { Banner } = require('imconnector/lib/ui/banner');
	const { ButtonSwitcher } = require('imconnector/lib/ui/button-switcher');
	const { CopyButton } = require('imconnector/lib/ui/buttons/copy');
	const { CompleteButton } = require('imconnector/lib/ui/buttons/complete');
	const { QrButton } = require('imconnector/lib/ui/buttons/qr');
	const { SettingStep } = require('imconnector/lib/ui/setting-step');
	const { withPressed } = require('utils/color');

	class EditView extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			/** @type {ButtonSwitcher} */
			this.buttonSwitcher = null;

			this.state.queue = props.connectorSettings.users;
			this.url = props.connectorSettings.url;
			this.qr = props.connectorSettings.qr;
			this.botName = props.connectorSettings.botName;
		}

		render()
		{
			if (this.props.withScroll)
			{
				return ScrollView(
					{
						style: {
							backgroundColor: '#eef2f4',
						},
						showsVerticalScrollIndicator: false,
						showsHorizontalScrollIndicator: false,
					},
					this.getContent(),
				);
			}

			return this.getContent();
		}

		getContent()
		{
			const bannerTitle = Type.isStringFilled(this.botName) ? ` ${this.botName.trim()}` : '';

			return View(
				{
					style: {
						flexDirection: 'column',
						backgroundColor: '#eef2f4',
					},
				},
				Banner({
					title: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_EDIT_TITLE'),
					description: Loc
						.getMessage('IMCONNECTORMOBILE_TELEGRAM_EDIT_BANNER_DESCRIPTION')
						.replace('#BOT_NAME#', bannerTitle),
					iconUri: `${currentDomain}/bitrix/mobileapp/imconnectormobile/extensions/imconnector/assets/telegram.png`,
					style: {
						backgroundColor: '#D9F5FF',
					},
					isComplete: true,
				}),
				View(
					{
						style: {
							flexDirection: 'column',
							alignItems: 'center',
							justifyContent: 'center',
							paddingTop: 22,
							paddingLeft: 24,
							paddingRight: 24,
							paddingBottom: 26,
							backgroundColor: '#fff',
							borderRadius: 12,
							marginBottom: 12,
						},
					},
					View(
						{
							style: {
								marginBottom: 20,
							},
						},
						Text({
							style: {
								color: '#333333',
								fontSize: 15,
								fontWeight: '400',
								textAlign: 'center',
								numberOfLines: 0,
								ellipsize: 'end',
							},
							text: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_EDIT_SHARE_TITLE'),
						}),
					),
					this.getSwitcher(),

					QrButton({
						image: this.qr,
						parentWidget: this.props.parentWidget,
						style: {
							borderRadius: 20,
							width: 282,
							height: 45,
						},
					}),
				),

				new SettingStep({
					withStep: false,
					icon: queueIcon,
					title: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_EDIT_QUEUE_TITLE'),
					description: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_EDIT_QUEUE_DESCRIPTION'),
					additionalComponents: [
						QueueField({
							queue: this.state.queue,
							parentWidget: this.props.parentWidget,
							onChange: this.props.onQueueChange,
							readOnly: this.props.connectorSettings.canEditLine === false,
						}),
						this.getAdvancedSettingsLink(),
					],
				}),
				this.props.connectorSettings.canEditConnector
					? this.getDisableButton()
					: null
				,
			);
		}

		getSwitcher()
		{
			return View(
				{
					style: {
						marginBottom: 16,
					},
				},
				this.buttonSwitcher = new ButtonSwitcher({
					states: {
						link: CopyButton({
							text: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_EDIT_COPY_LINK'),
							copyText: this.url,
							onClick: () => {
								this.buttonSwitcher.switchTo('complete');
							},
							style: {
								borderRadius: 20,
								width: 282,
								height: 45,
							},
						}),
						complete: CompleteButton({
							text: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_EDIT_COPY_LINK_COMPLETE'),
							style: {
								borderRadius: 20,
								width: 282,
								height: 45,
							},
						}),
					},
					startingState: 'link',
				}),
			);
		}

		getAdvancedSettingsLink()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'flex-start',
						alignItems: 'center',
						height: 28,
						alignSelf: 'flex-start',
					},
					onClick: () => {
						QRCodeAuthComponent.open(this.props.parentWidget, {
							title: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_EDIT_GO_TO_WEB_TITLE_MSGVER_1'),
							redirectUrl: currentDomain,
							showHint: true,
							hintText: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_EDIT_GO_TO_WEB_HINT_MSGVER_1'),
						});
					},
				},
				Text({
					text: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_EDIT_QUEUE_ADVANCED_SETTINGS'),
					textDecorationLine: 'underline',
					style: {
						color: '#A8ADB4',
						fontSize: 13,
						fontWeight: '400',
						borderBottomWidth: 1,
						borderBottomColor: '#d6d8db',
						borderStyle: 'dash',
						borderDashSegmentLength: 3,
						borderDashGapLength: 3,
						marginRight: 3,
					},
				}),
				Image({
					style: {
						width: 16,
						height: 16,
					},
					svg: {
						content: goToWebIcon,
					},
				}),
			);
		}

		getDisableButton()
		{
			return View(
				{
					style: {
						backgroundColor: withPressed('#FFFFFF'),
						borderRadius: 12,
						height: 46,
						justifyContent: 'center',
						alignItems: 'center',
						marginBottom: 20,
					},
					onClick: () => {
						Alert.confirm(
							Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_EDIT_DISABLE_ALERT_TITLE'),
							'',
							[
								makeCancelButton(),
								makeDestructiveButton(
									Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_EDIT_DISABLE_ALERT_BUTTON'),
									() => this.props.onConnectorDisable(this.props.connectorSettings),
								),
							],
						);
					},
				},
				Text({
					style: {
						color: '#959CA4',
						fontWeight: '400',
						fontSize: 15,
					},
					text: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_EDIT_DISABLE'),
				}),
			);
		}
	}

	const queueIcon = `<svg width="43" height="42" viewBox="0 0 43 42" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M30.2413 30.7276C30.6189 30.6604 30.8666 30.2983 30.7885 29.9228C30.3677 27.8995 29.6097 25.1126 29.6097 25.1126C29.6097 24.3321 28.5638 23.4401 26.5028 22.9207C25.8028 22.73 25.1386 22.4351 24.5345 22.0471C24.4377 21.8067 24.3994 21.5483 24.4225 21.2916L23.7619 21.1929C23.7619 21.1376 23.7054 20.3198 23.7054 20.3198C24.4993 20.0596 24.4176 18.5246 24.4176 18.5246C24.9218 18.7972 25.2502 17.5841 25.2502 17.5841C25.8465 15.8974 24.9532 15.9988 24.9532 15.9988C25.1094 14.9684 25.1094 13.9219 24.9532 12.8914C24.5562 9.47406 18.5761 10.4007 19.2855 11.5178C17.5379 11.2023 17.9368 15.0823 17.9368 15.0823L18.3158 16.0855C17.7908 16.4171 17.8937 16.7986 18.0086 17.2248L18.0086 17.2248C18.0565 17.4027 18.1066 17.5884 18.114 17.7815C18.1507 18.7502 18.7567 18.5491 18.7567 18.5491C18.7944 20.1463 19.6013 20.3562 19.6013 20.3562C19.7529 21.3594 19.6588 21.1865 19.6588 21.1865L18.9402 21.2713C18.95 21.4993 18.9311 21.7276 18.8837 21.9512C18.4664 22.133 18.2109 22.2772 17.9587 22.4196C17.6991 22.5662 17.4428 22.7108 17.0169 22.8926C15.3943 23.5845 13.6308 24.488 13.3179 25.702C13.1266 26.4444 12.703 28.5273 12.4028 30.0339C12.3262 30.4184 12.5808 30.7903 12.9678 30.8528C15.5671 31.2727 18.3468 31.5 21.2355 31.5C24.3995 31.5 27.4326 31.2273 30.2413 30.7276ZM38.0689 27.561C38.6169 27.4156 38.975 26.8999 38.8372 26.35C38.5819 25.3319 38.0554 24.0385 37.4267 23.6575C36.8865 23.3301 36.716 23.2511 36.5303 23.1651C36.4205 23.1142 36.3053 23.0609 36.1052 22.9522C35.7494 22.7509 35.374 22.583 34.9843 22.451C34.7845 22.2794 34.6298 22.0653 34.5327 21.8261C34.3278 21.577 34.0585 21.3833 33.7513 21.2641L33.7284 20.5163L35.3443 20.0234C35.3443 20.0234 35.7611 19.8342 35.8018 19.8342C35.7372 19.7024 35.6627 19.5752 35.579 19.4535C35.5484 19.374 35.396 18.8286 35.396 18.8286C35.6316 19.1227 35.9088 19.3842 36.2196 19.6056C35.9443 19.1035 35.7088 18.5825 35.515 18.0468C35.3854 17.5509 35.2953 17.0463 35.2455 16.5374C35.1345 15.5916 34.9616 14.6534 34.7281 13.7283C34.562 13.2686 34.2662 12.8616 33.8734 12.5523C33.2975 12.1619 32.6216 11.9278 31.9175 11.8749H31.8365C31.1324 11.9276 30.4565 12.1617 29.8807 12.5523C29.4884 12.862 29.1928 13.2689 29.0265 13.7283C28.7924 14.6534 28.6194 15.5916 28.5086 16.5374C28.4659 17.0577 28.38 17.574 28.2519 18.0813C28.0579 18.6075 27.8181 19.1173 27.535 19.6052C27.844 19.3827 28.1195 19.1205 28.3535 18.826C28.3535 18.826 28.1581 19.4243 28.127 19.5042C28.0558 19.6075 27.9959 19.7175 27.9481 19.8324C27.9884 19.8324 28.4056 20.0217 28.4056 20.0217L30.0211 20.5163L29.9978 21.2641C29.6907 21.3838 29.4215 21.5776 29.2163 21.8265C29.1386 21.9923 29.0432 22.15 28.9318 22.2972C30.4036 22.8801 31.2651 23.7336 31.2999 24.6885C31.3067 24.737 31.3843 25.0273 31.493 25.4335L31.493 25.4338C31.7647 26.4493 32.2301 28.1892 32.268 28.6923H32.4008C34.4512 28.3976 36.3555 28.0156 38.0689 27.561ZM10.1534 28.6876H10.7396C10.7776 28.183 11.2455 26.434 11.5168 25.4199L11.5168 25.4198L11.5169 25.4197C11.6243 25.0182 11.7008 24.732 11.7077 24.6837C11.742 23.7289 12.6035 22.8754 14.0754 22.2924C13.9639 22.1453 13.8685 21.9876 13.7908 21.8218C13.5857 21.5729 13.3164 21.3791 13.0094 21.2593L12.986 20.5116L14.602 20.0174C14.602 20.0174 15.0192 19.8281 15.0595 19.8281C15.0117 19.7132 14.9518 19.6033 14.8806 19.4999C14.8499 19.4209 14.6586 18.8354 14.6542 18.8219C14.8879 19.1161 15.163 19.3781 15.4717 19.6005C15.1886 19.1125 14.9488 18.6028 14.7548 18.0766C14.6266 17.5693 14.5408 17.053 14.4981 16.5327C14.3873 15.5868 14.2143 14.6486 13.9802 13.7236C13.8138 13.2641 13.5183 12.8573 13.126 12.5476C12.5502 12.157 11.8742 11.9229 11.1701 11.8702H11.0892C10.3851 11.9231 9.7092 12.1572 9.13327 12.5476C8.74045 12.8569 8.4447 13.2639 8.27862 13.7236C8.04505 14.6487 7.87223 15.5869 7.76117 16.5327C7.71138 17.0416 7.6213 17.5462 7.49169 18.0421C7.29788 18.5778 7.06236 19.0988 6.78712 19.6009C7.09791 19.3795 7.37507 19.118 7.61065 18.8239C7.61065 18.8239 7.4583 19.3693 7.42764 19.4488C7.34411 19.5705 7.2698 19.6977 7.20529 19.8294C7.24601 19.8294 7.66281 20.0187 7.66281 20.0187L9.27876 20.5116L9.25542 21.2593C8.94822 21.3786 8.67888 21.5723 8.47398 21.8213C8.37686 22.0606 8.22217 22.2747 8.02241 22.4463C7.63268 22.5783 7.25728 22.7462 6.9015 22.9475C6.70764 23.0528 6.55573 23.1277 6.41654 23.1964L6.41653 23.1964C6.16902 23.3185 5.96175 23.4208 5.6305 23.6382C4.83201 24.1622 4.35728 25.4487 4.13987 26.4598C4.01939 27.0201 4.39198 27.5398 4.94772 27.6798C6.53968 28.0807 8.28616 28.4203 10.1534 28.6876Z" fill="#2FC6F6"/>
</svg>
`;

	const goToWebIcon = `<svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M7.1269 4.98005V3.3335H5.38856C4.52945 3.3335 3.83301 4.02994 3.83301 4.88905V11.1113C3.83301 11.9704 4.52945 12.6668 5.38856 12.6668H11.6108C12.4699 12.6668 13.1663 11.9704 13.1663 11.1113V9.37216H11.519L11.5193 10.242L11.514 10.3327C11.4691 10.7195 11.1404 11.0198 10.7415 11.0198H6.25784L6.16714 11.0145C5.78032 10.9696 5.48007 10.6409 5.48007 10.242V5.75833L5.4853 5.66763C5.53023 5.28081 5.85897 4.98055 6.25784 4.98055L7.1269 4.98005Z" fill="#BDC1C6"/>
<path d="M13.1664 3.60016V7.79185C13.1664 7.97003 12.9509 8.05926 12.8249 7.93327L11.2973 6.40572L9.14304 8.56045C9.06493 8.63857 8.93829 8.63858 8.86018 8.56047L7.97837 7.67866C7.90027 7.60056 7.90026 7.47395 7.97835 7.39584L10.133 5.24061L8.56675 3.67494C8.44072 3.54896 8.52995 3.3335 8.70815 3.3335H12.8997C13.047 3.3335 13.1664 3.45289 13.1664 3.60016Z" fill="#BDC1C6"/>
</svg>
`;

	module.exports = { EditView };
});
