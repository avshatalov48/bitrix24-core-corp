/**
 * @module disk/dialogs/external-link/security-settings
 */
jn.define('disk/dialogs/external-link/security-settings', (require, exports, module) => {
	const { BaseDialog } = require('disk/dialogs/base');

	const { Loc } = require('loc');
	const { Indent, Component, Color } = require('tokens');
	const { showErrorToast } = require('toast');
	const { CardList } = require('ui-system/layout/card-list');
	const { Card } = require('ui-system/layout/card');
	const { SettingSelector } = require('ui-system/blocks/setting-selector');
	const { SwitcherSize } = require('ui-system/blocks/switcher');
	const { StringInput } = require('ui-system/form/inputs/string');
	const { NumberInput } = require('ui-system/form/inputs/number');
	const { Input, InputDesign } = require('ui-system/form/inputs/input');
	const { Icon } = require('ui-system/blocks/icon');
	const { UIMenu } = require('layout/ui/menu');
	const {
		DeathTimeByType,
		DeathTimeType,
		deathTimeTypeNamePlural,
		getDeathTimeTypeByDeathTime,
		getDeathTimeValueByType,
	} = require('disk/dialogs/external-link/death-time-util');

	const { selectById } = require('disk/statemanager/redux/slices/files/selector');
	const store = require('statemanager/redux/store');

	const INPUT_HEIGHT = 42;

	class SecuritySettingsDialog extends BaseDialog
	{
		constructor(props)
		{
			super(props);

			this.objectId = props.objectId ?? '';
			this.object = selectById(store.getState(), Number(this.objectId)) ?? {};
			this.linkObject = this.object?.links?.external ?? null;

			this.DeathTimeTypeMenuTargetRef = null;

			const deathTimeType = getDeathTimeTypeByDeathTime(props.deathTime);

			this.state = {
				hasPassword: Boolean(props.hasPassword),
				showPassword: false,
				deathTimeType,
				deathTimeValue: getDeathTimeValueByType(props.deathTime, deathTimeType),
				deathTimeEnabled: Boolean(props.deathTime),
				canEditDocument: this.isEditAvailable && props.canEditDocument,
				password: props.password ?? null,
				pending: false,
			};
		}

		get isEditAvailable()
		{
			return this.linkObject?.availableEdit ?? null;
		}

		get deathTime()
		{
			return DeathTimeByType[this.state.deathTimeType] * this.state.deathTimeValue;
		}

		getTestId(suffix)
		{
			return `security-settings-dialog-${suffix}`;
		}

		isButtonDisabled()
		{
			return false;
		}

		getButtonText()
		{
			return Loc.getMessage('M_DISK_SECURITY_SETTING_DIALOG_SAVE_BUTTON');
		}

		saveSettings()
		{
			if (this.props.onSave)
			{
				this.props.onSave({
					deathTime: this.state.deathTimeEnabled ? this.deathTime : 0,
					hasPassword: this.state.hasPassword,
					canEditDocument: this.state.canEditDocument,
					password: this.state.hasPassword ? this.state.password : null,
				});
			}
			this.layoutWidget.close();
		}

		save = () => {
			if (this.object?.links?.external?.id)
			{
				this.setState(
					{ pending: true },
					() => {
						BX.ajax.runAction(
							'diskmobile.ExternalLink.updateSecuritySettings',
							this.prepareUpdateSettingsConfig(),
						).then(this.onUpdateSettingsFulfilled).catch(this.onUpdateSettingsRejected);
					},
				);
			}
			else
			{
				this.saveSettings();
			}
		};

		onUpdateSettingsFulfilled = () => {
			this.saveSettings();
		};

		onUpdateSettingsRejected = (e) => {
			console.error(e);
			this.setState({ pending: false });
			showErrorToast(
				{
					message: Loc.getMessage('M_DISK_SECURITY_SETTING_DIALOG_SAVE_ERROR'),
				},
			);
		};

		prepareUpdateSettingsConfig()
		{
			const toSeconds = (miliseconds) => miliseconds / 1000;
			const toMiliseconds = (seconds) => seconds * 1000;

			const deathTimeTimestamp = this.state.deathTimeEnabled
				? (new Date(Date.now() + toMiliseconds(this.deathTime))).getTime()
				: null;

			return {
				data: {
					id: this.object?.links?.external?.id,
					newPassword: this.state.hasPassword ? this.state.password : null,
					disablePassword: this.state.hasPassword ? 0 : 1,
					deathTime: this.state.deathTimeEnabled ? toSeconds(deathTimeTimestamp) : null,
					disableDeathTime: this.state.deathTimeEnabled ? 0 : 1,
					allowEditDocument: this.state.canEditDocument ? 1 : 0,
				},
			};
		}

		getStartingLayoutHeight()
		{
			const TITLE_HEIGHT = 44;
			const TURN_ON_LINK_BLOCK_HEIGHT = 48 + Component.cardPaddingT.toNumber() + Component.cardPaddingB.toNumber();
			const SECURITY_BLOCK_HEIGHT = 24 + Component.cardPaddingT.toNumber() + Component.cardPaddingB.toNumber();
			const BUTTON_HEIGHT = 42 + Indent.XL2.toNumber() * 2;
			const OPENED_OPTIONS_DIFF = (INPUT_HEIGHT + Indent.L.getValue()) * 2;

			return TITLE_HEIGHT
				+ TURN_ON_LINK_BLOCK_HEIGHT
				+ Component.cardListGap.getValue()
				+ SECURITY_BLOCK_HEIGHT
				+ BUTTON_HEIGHT
				+ OPENED_OPTIONS_DIFF;
		}

		getTitleParams()
		{
			return {
				text: Loc.getMessage('M_DISK_SECURITY_SETTING_DIALOG_SECURITY'),
				type: 'dialog',
			};
		}

		/**
		 * @param {Object} data
		 * @param {Function} [data.onSave]
		 */
		static async open(data, parentWidget)
		{
			super.open(data, parentWidget);
		}

		renderContent()
		{
			return View(
				{
					style: { flex: 1 },
					onClick: () => Keyboard.dismiss(),
				},
				CardList(
					{
						divided: true,
					},
					this.renderDeathTimeCard(),
					this.renderPasswordCard(),
					this.isEditAvailable && this.renderAllowEditCard(),
				),
			);
		}

		renderDeathTimeCard()
		{
			return Card(
				{
					testId: this.getTestId('death-time-card'),
					border: true,
				},
				SettingSelector({
					testId: this.getTestId('death-time-card'),
					checked: this.state.deathTimeEnabled,
					title: Loc.getMessage('M_DISK_SECURITY_SETTING_DIALOG_ENABLE_DEATH_TIME'),
					additionalContent: this.state.deathTimeEnabled ? this.renderDeathTimeFields() : null,
					switcherSize: SwitcherSize.L,
					onClick: () => {
						this.setState({
							deathTimeEnabled: !this.state.deathTimeEnabled,
							deathTimeValue: this.state.deathTimeValue || 1,
						});
					},
				}),
			);
		}

		renderDeathTimeFields()
		{
			return View(
				{
					style: {
						marginTop: Indent.L.getValue(),
						flexDirection: 'row',
						alignItems: 'center',
						alignContent: 'center',
					},
				},
				View(
					{ style: { flex: 1 } },
					this.renderDeathTimeValueField(),
				),
				this.renderVerticalDivider(),
				View(
					{ style: { flex: 1 } },
					this.renderDeathTimeTypeField(),
				),
			);
		}

		renderDeathTimeValueField()
		{
			return NumberInput(
				{
					testId: 'duration-value-field',
					design: InputDesign.GREY,
					required: false,
					value: this.state.deathTimeValue || '',
					shouldShowToolbar: false,
					onChange: (value) => this.setState({
						deathTimeValue: value,
					}),
					title: '',
					placeholder: '0',
				},
			);
		}

		renderDeathTimeTypeField()
		{
			return Input({
				testId: this.getTestId('death-time-type-field'),
				readOnly: true,
				dropdown: true,
				onClick: this.openDeathTimeTypeMenu,
				design: InputDesign.GREY,
				value: deathTimeTypeNamePlural(this.state.deathTimeValue)[this.state.deathTimeType],
				forwardRef: (ref) => {
					this.DeathTimeTypeMenuTargetRef = ref;
				},
			});
		}

		openDeathTimeTypeMenu = () => {
			const menuItems = Object.values(DeathTimeType).map((id) => this.createDeathTimeTypeMenuItem(id));
			this.menu = new UIMenu(menuItems);
			this.menu.show({ target: this.DeathTimeTypeMenuTargetRef });
		};

		createDeathTimeTypeMenuItem(id)
		{
			return {
				id,
				testId: this.getTestId(`death-time-type-menu-item-${id}`),
				title: deathTimeTypeNamePlural(this.state.deathTimeValue)[id],
				onItemSelected: (event, item) => this.setState({
					deathTimeType: item.id,
				}),
				sectionCode: 'default',
			};
		}

		renderVerticalDivider()
		{
			return View(
				{
					style: {
						height: 14,
						width: 1,
						backgroundColor: Color.bgSeparatorPrimary.toHex(),
						marginHorizontal: Indent.XL2.toNumber(),
					},
				},
			);
		}

		renderPasswordCard()
		{
			return Card(
				{
					testId: this.getTestId('password-card'),
					border: true,
				},
				SettingSelector({
					testId: this.getTestId('password-card-selector'),
					checked: this.state.hasPassword,
					title: Loc.getMessage('M_DISK_SECURITY_SETTING_DIALOG_ENABLE_PASSWORD'),
					additionalContent: this.state.hasPassword ? this.renderPasswordField() : null,
					switcherSize: SwitcherSize.L,
					onClick: () => {
						this.setState({
							hasPassword: !this.state.hasPassword,
						});
					},
				}),
			);
		}

		renderPasswordField()
		{
			return StringInput({
				testId: this.getTestId('password-field'),
				design: InputDesign.GREY,
				style: {
					marginTop: Indent.L.getValue(),
				},
				isPassword: !this.state.showPassword,
				value: this.state.password,
				rightContent: this.state.showPassword ? Icon.OBSERVER : Icon.CROSSED_EYE,
				onChange: (value) => {
					this.setState({
						password: value,
					});
				},
				onClickRightContent: () => {
					this.setState({
						showPassword: !this.state.showPassword,
					});
				},
			});
		}

		renderAllowEditCard()
		{
			return Card(
				{
					testId: this.getTestId('allow-edit-card'),
					border: true,
				},
				SettingSelector({
					testId: this.getTestId('allow-edit-selector'),
					checked: this.state.canEditDocument,
					title: Loc.getMessage('M_DISK_SECURITY_SETTING_DIALOG_ALLOW_EDIT'),
					switcherSize: SwitcherSize.L,
					onClick: () => {
						this.setState({
							canEditDocument: !this.state.canEditDocument,
						});
					},
				}),
			);
		}
	}

	module.exports = { SecuritySettingsDialog };
});
