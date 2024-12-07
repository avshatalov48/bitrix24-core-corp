/**
 * @module im/messenger/controller/channel-creator/components/privacy-selector
 */
jn.define('im/messenger/controller/channel-creator/components/privacy-selector', (require, exports, module) => {
	const { Theme } = require('im/lib/theme');
	const { Loc } = require('loc');
	const { CheckBox } = require('im/messenger/lib/ui/base/checkbox');
	/**
	 * @class PrivacySelector
	 * @typedef {LayoutComponent<PrivacySelectorProps, PrivacySelectorState>} PrivacySelector
	 */
	class PrivacySelector extends LayoutComponent
	{
		/**
		 * @param {PrivacySelectorProps} props
		 */
		constructor(props)
		{
			super(props);
			this.state.currentMode = props.defaultMode;
		}

		render()
		{
			return View(
				{
					style: {
						paddingTop: 8,
						paddingBottom: 4,
						borderRadius: 12,
						backgroundColor: Theme.colors.bgContentPrimary,
						flexDirection: 'column',
						minHeight: 235,
					},
				},
				this.getBadge(),
				...this.getSections(),
			);
		}

		getBadge()
		{
			return View(
				{
					style: {
						paddingLeft: 20,
						height: 38,
						justifyContent: 'center',
					},
				},
				Text({
					style: {
						fontSize: 14,
						color: Theme.colors.base2,
					},
					text: this.props.badge,
				}),
			);
		}

		getSections()
		{
			return this.props.firstMode === 'open'
				? [this.getOpenSection(), this.getPrivateSection()]
				: [this.getPrivateSection(), this.getOpenSection()]
			;
		}

		getOpenSection()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
					onClick: () => {
						this.props.onChangeMode('open');
						this.setState({ currentMode: 'open' });
					},
				},
				View(
					{
						style: {
							paddingLeft: 18,
							paddingTop: 14,
							paddingBottom: 15,
						},
					},
					new CheckBox({
						checked: this.state.currentMode === 'open',
						disabled: false,
						readOnly: this.state.currentMode === 'open',
						onClick: () => {
							this.props.onChangeMode('open');
							this.setState({ currentMode: 'open' });
						},
					}),
				),
				View(
					{
						style: {
							paddingTop: 14,
							paddingBottom: 15,
							flexGrow: 3,
							marginLeft: 12,
							paddingRight: 54,
							flexDirection: 'column',
							borderBottomColor: Theme.colors.bgSeparatorPrimary,
							borderBottomWidth: this.props.firstMode === 'open' ? 1 : 0,
						},
					},
					View(
						{},
						Text({
							style: {
								fontSize: 18,
								color: Theme.colors.base1,
								fontWeight: '400',
							},
							text: Loc.getMessage('IMMOBILE_CHANNEL_CREATOR_PRIVACY_SELECTOR_TITLE_OPEN'),
						}),
						View(
							{
								style: {
									marginTop: 2,
								},
							},
							Text({
								style: {
									fontSize: 15,
									color: Theme.colors.base4,
								},
								text: this.props.openModeDescription,
							}),
						),
					),
				),
			);
		}

		getPrivateSection()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
					clickable: true,
					onClick: () => {
						this.props.onChangeMode('private');
						this.setState({ currentMode: 'private' });
					},
				},
				View(
					{
						style: {
							paddingLeft: 18,
							paddingTop: 14,
							paddingBottom: 15,
						},
					},
					new CheckBox({
						checked: this.state.currentMode === 'private',
						disabled: false,
						readOnly: this.state.currentMode === 'private',
						onClick: () => {
							this.props.onChangeMode('private');
							this.setState({ currentMode: 'private' });
						},
					}),
				),
				View(
					{
						style: {
							paddingTop: 14,
							paddingBottom: 15,
							flexGrow: 3,
							marginLeft: 12,
							paddingRight: 54,
							flexDirection: 'column',
							borderBottomColor: Theme.colors.bgSeparatorPrimary,
							borderBottomWidth: this.props.firstMode === 'private' ? 1 : 0,
						},
					},
					View(
						{},
						Text({
							style: {
								fontSize: 18,
								color: Theme.colors.base1,
								fontWeight: '400',
							},
							text: Loc.getMessage('IMMOBILE_CHANNEL_CREATOR_PRIVACY_SELECTOR_TITLE_PRIVATE'),
						}),
						View(
							{
								style: {
									marginTop: 2,
								},
							},
							Text({
								style: {
									fontSize: 15,
									color: Theme.colors.base4,
								},
								text: this.props.privateModeDescription,
							}),
						),
					),
				),
			);
		}
	}

	module.exports = { PrivacySelector };
});
