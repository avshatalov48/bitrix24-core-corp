/**
 * @module crm/receive-payment/steps/payment-systems/main-block-layout
 */
jn.define('crm/receive-payment/steps/payment-systems/main-block-layout', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { ExpandableList } = require('crm/receive-payment/steps/payment-systems/expandable-list');
	const { EventEmitter } = require('event-emitter');

	const pathToExtension = `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/receive-payment/steps/payment-systems`;

	/**
	 * @class MainBlockLayout
	 */
	class MainBlockLayout extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				itemList: BX.prop.getArray(this.props, 'itemList', []),
			};

			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
			this.customEventEmitter.on('ReceivePayment::onSwitchPaySystem', this.handleSwitchPaySystem.bind(this));
		}

		get isFilledList()
		{
			return this.state.itemList.length > 0;
		}

		shouldComponentUpdate(nextProps, nextState)
		{
			return true;
		}

		handleSwitchPaySystem(data)
		{
			const { id, active, name } = data;

			const paySystems = this.state.itemList;
			const index = paySystems.findIndex((item) => parseInt(item.ID, 10) === id);
			if (active)
			{
				paySystems.push({
					ID: id,
					NAME: name,
				});
			}
			else
			{
				paySystems.splice(index, 1);
			}

			this.setState(
				{
					itemList: paySystems,
				},
				() => this.customEventEmitter.emit('ReceivePayment::onPreparedPaySystems', { paySystems }),
			);
		}

		render()
		{
			const { icons, titles, description, onClick, webRedirect, additionalStyle } = this.props;
			const icon = this.isFilledList ? icons.enabled : icons.disabled;
			const title = this.isFilledList ? titles.enabled : titles.disabled;

			return View(
				{
					style: {
						...styles.container(this.isFilledList),
						...additionalStyle.container,
					},
				},
				View(
					{
						style: {
							width: 77,
						},
					},
					this.renderIconImageLayout(icon, additionalStyle.iconImage, !this.isFilledList),
				),
				View(
					{
						style: {
							flexShrink: 1,
							alignItems: 'flex-start',
						},
					},
					this.renderDescriptionLayout(title, description),
					this.state.itemList && new ExpandableList({ list: this.state.itemList }),
					this.renderSettingsLayout(onClick, webRedirect),
				),
			);
		}

		renderIconImageLayout(imageName, additionalStyle, disabled)
		{
			return Image({
				tintColor: disabled ? AppTheme.colors.base3 : null,
				style: {
					width: imageName === 'cashbox.svg' ? 44 : 43,
					height: 45,
					marginRight: imageName === 'cashbox.svg' ? 0 : 1,
					...additionalStyle,
				},
				svg: {
					uri: `${pathToExtension}/images/${imageName}`,
				},
			});
		}

		renderDescriptionLayout(title, description)
		{
			return View(
				{
					style: {
						flexShrink: 1,
					},
				},
				Text({
					style: styles.description(this.isFilledList),
					text: title,
				}),
				!this.isFilledList && Text({
					style: {
						fontSize: 13,
						color: AppTheme.colors.base3,
						lineHeightMultiple: 1.05,
					},
					text: description,
				}),
			);
		}

		renderSettingsLayout(onClick, webRedirect)
		{
			return this.isFilledList
				? this.renderSettingsLinkLayout(onClick, webRedirect)
				: this.renderSettingsButtonLayout(onClick, webRedirect);
		}

		renderSettingsLinkLayout(onClick, webRedirect)
		{
			return View(
				{
					style: styles.linkContainer,
					onClick,
				},
				View(
					{
						style: styles.linkText,
					},
					Text({
						style: {
							fontSize: 13,
							color: AppTheme.colors.base4,
						},
						text: Loc.getMessage('M_RP_PS_SETTINGS_LINK_TITLE'),
					}),
				),
				webRedirect && Image({
					tintColor: AppTheme.colors.base3,
					style: {
						width: 10,
						height: 10,
						marginTop: 2,
						marginLeft: 7,
					},
					svg: { uri: `${pathToExtension}/images/link.svg` },
				}),
			);
		}

		renderSettingsButtonLayout(onClick, webRedirect)
		{
			return View(
				{
					style: styles.buttonContainer,
					onClick,
				},
				Text({
					style: {
						fontSize: 14,
						color: AppTheme.colors.base1,
					},
					text: Loc.getMessage('M_RP_PS_SETTINGS_BUTTON_TITLE'),
				}),
				Image({
					tintColor: AppTheme.colors.base3,
					style: {
						width: 15,
						height: 15,
						marginTop: 7,
						marginLeft: 4,
						marginRight: 4,
					},
					svg: { uri: `${pathToExtension}/images/link-button.svg` },
				}),
			);
		}
	}

	const styles = {
		container: (isFilledList) => {
			return {
				backgroundColor: isFilledList
					? AppTheme.colors.bgContentPrimary
					: AppTheme.colors.bgContentTertiary,
				borderRadius: 12,
				marginTop: 12,
				alignItems: 'flex-start',
				flexDirection: 'row',
				paddingRight: 11,
				justifyContent: 'flex-start',
				width: '100%',
			};
		},
		description: (isFilledList) => {
			return {
				fontSize: 16,
				fontWeight: '500',
				color: isFilledList ? AppTheme.colors.base1 : AppTheme.colors.base3,
				marginTop: 18,
				marginBottom: 5,
			};
		},
		linkContainer: {
			flexDirection: 'row',
			marginTop: 12,
			marginBottom: 20,
			height: 16,
			justifyContent: 'center',
			alignItems: 'center',
		},
		linkText: {
			borderBottomColor: AppTheme.colors.bgSeparatorPrimary,
			borderBottomWidth: 1,
			borderStyle: 'dash',
			borderDashSegmentLength: 3,
			borderDashGapLength: 3,
		},
		buttonContainer: {
			marginTop: 13,
			marginBottom: 23,
			borderWidth: 1,
			borderColor: AppTheme.colors.bgSeparatorPrimary,
			borderRadius: 6,
			flexDirection: 'row',
			height: 29,
			paddingLeft: 15,
			paddingRight: 15,
		},
	};

	module.exports = { MainBlockLayout };
});
