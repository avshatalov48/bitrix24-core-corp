/**
 * @module calendar/layout/sharing-panel
 */
jn.define('calendar/layout/sharing-panel', (require, exports, module) => {

	const { Loc } = require('loc');
	const { withPressed } = require('utils/color');
	const { sharingIOS } = require('calendar/assets/common');

	/**
	 * @class SharingPanel
	 */
	class SharingPanel extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const { stage1 } = this.props.layoutConfigDidMount().opacity

			this.opacity1 = stage1;
		}


		render()
		{
			const isIOS = Application.getPlatform() === 'ios';

			return View( // .meeting-slots__access
				{
					testId: 'SharingPanelDescriptionHeader',
					style: {
						paddingTop: 24,
						paddingLeft: isIOS
							? 10
							: 32,
						paddingRight: isIOS
							? 10
							: 32,
						display: 'flex',
						flexDirection: 'column',
						alignItems: 'center',
						justifyContent: 'flex-start',
						opacity: this.opacity1,
					},
					ref: (ref) => this.props.setRefLayout1(ref),
				},
				Text(
					{
						style: {
							fontSize: 15,
							lineHeight: 18,
							color: '#333',
							textAlign: 'center',
						},
						text: Loc.getMessage('L_ML_DESCRIPTION_1'),
					}
				),
				View(
					{
						testId: 'SharingPanelSharingDialog',
						style: {
							flexDirection: 'row',
							justifyContent: 'center',
							alignItems: 'center',
							marginTop: 8,
							width: 282,
							height: 48,
							borderRadius: 12,
							backgroundColor: withPressed('#9DCF00'),
						},
						onClick: () => {
							if( this.props.isOn )
							{
								dialogs.showSharingDialog({ message: Loc.getMessage('L_ML_SHARE_LINK_MESSAGE') + '\r\n' + this.props.publicShortUrl })
							}
						}
					},
					Image({
						style: {
							width: 28,
							height: 28,
							alignSelf: 'center',
						},
						svg: {
							content: sharingIOS
						},
					}),
					Text({
						style: {
							ellipsize: 'end',
							numberOfLines: 1,
							color: '#fff',
						},
						text: Loc.getMessage('L_ML_BUTTON_SHARE'),
					})
				),
				View({
						testId: 'SharingPanelViewAsGuest',
					},
					Button({
						style: {
							marginTop: 8,
							width: 282,
							height: 48,
							backgroundColor: 'transparent',
							borderRadius: 12,
							borderWidth: 1,
							borderStyle: 'solid',
							borderColor: '#DFE0E3',
						},
						text: Loc.getMessage('L_ML_BUTTON_VIEW_AS_GUEST'),
						onClick:() => {
							if( this.props.isOn )
							{
								Application.openUrl(this.props.publicShortUrl)
							}
						}
					})
				),
				Text(
					{
						testId: 'SharingPanelDescriptionFooter',
						style: {
							marginTop: 10,
							fontSize: 14,
							lineHeight: 17,
							color: '#A8ADB4',
							textAlign: 'center',
						},
						text: Loc.getMessage('L_ML_DESCRIPTION_2'),
					}
				),
			);
		}
	}

	module.exports = { SharingPanel };
});