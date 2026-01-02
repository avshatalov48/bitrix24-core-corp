/**
 * @module disk/dialogs/external-link
 */
jn.define('disk/dialogs/external-link', (require, exports, module) => {
	const { BaseDialog } = require('disk/dialogs/base');

	const { Loc } = require('loc');
	const { Indent, Color, Component } = require('tokens');
	const { showToast } = require('toast');
	const { CardList } = require('ui-system/layout/card-list');
	const { Card } = require('ui-system/layout/card');
	const { SettingSelector } = require('ui-system/blocks/setting-selector');
	const { SwitcherSize } = require('ui-system/blocks/switcher');
	const { Text3 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { SecuritySettingsDialog } = require('disk/dialogs/external-link/security-settings');
	const {
		getDeathTimeMessage,
	} = require('disk/dialogs/external-link/death-time-util');

	const { selectById } = require('disk/statemanager/redux/slices/files/selector');
	const { setExternalLink } = require('disk/statemanager/redux/slices/files');
	const store = require('statemanager/redux/store');

	class ExternalLinkDialog extends BaseDialog
	{
		constructor(props)
		{
			super(props);

			this.objectId = Number(props.objectId) || null;
			this.object = selectById(store.getState(), this.objectId) ?? {};

			this.state = {
				pending: false,
				linkEnabled: Boolean(this.linkObject),
				deathTime: this.linkObject?.deathTime ?? null,
				hasPassword: this.linkObject?.hasPassword,
				canEditDocument: this.linkObject?.canEditDocument,
				password: null,
			};
		}

		get linkObject()
		{
			return this.object?.links?.external ?? null;
		}

		get isEditAvailable()
		{
			return this.linkObject?.availableEdit ?? null;
		}

		getTestId(suffix)
		{
			return `external-link-dialog-${suffix}`;
		}

		isButtonDisabled()
		{
			return !this.state.linkEnabled;
		}

		getButtonText()
		{
			return Loc.getMessage('M_DISK_EXTERNAL_LINK_DIALOG_SAVE_BUTTON');
		}

		save = () => {
			if (this.state.pending)
			{
				return;
			}

			Application.copyToClipboard(this.object.links.external.link);
			showToast({
				message: Loc.getMessage('M_DISK_EXTERNAL_LINK_DIALOG_COPY_TOAST_MESSAGE'),
				icon: Icon.COPY,
			});

			this.layoutWidget.close();
		};

		getStartingLayoutHeight()
		{
			const TITLE_HEIGHT = 44;
			const TURN_ON_LINK_BLOCK_HEIGHT = 48 + Component.cardPaddingT.toNumber() + Component.cardPaddingB.toNumber();
			const SECURITY_BLOCK_HEIGHT = 24 + Component.cardPaddingT.toNumber() + Component.cardPaddingB.toNumber();
			const BUTTON_HEIGHT = 42 + Indent.XL2.toNumber() * 2;

			return TITLE_HEIGHT
				+ TURN_ON_LINK_BLOCK_HEIGHT
				+ Component.cardListGap.getValue()
				+ SECURITY_BLOCK_HEIGHT
				+ BUTTON_HEIGHT;
		}

		getTitleParams()
		{
			return {
				text: Loc.getMessage('M_DISK_EXTERNAL_LINK_DIALOG_TITLE'),
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
			return CardList(
				{
					divided: true,
				},
				this.renderTurnLinkCard(),
				this.renderSecurityCard(),
			);
		}

		generateLink = () => {
			this.setState(
				{
					pending: true,
					linkEnabled: true,
				},
				() => {
					BX.ajax.runAction('diskmobile.ExternalLink.generate', this.prepareGenerateLinkConfig())
						.then(this.onGenerateLinkFulfilled).catch(this.onGenerateLinkRejected);
				},
			);
		};

		prepareGenerateLinkConfig = () => {
			const toSeconds = (miliseconds) => (miliseconds ? miliseconds / 1000 : null);
			const toMiliseconds = (seconds) => (seconds ? seconds * 1000 : null);

			const deathTimeTimestamp = this.state.deathTime
				? (new Date(Date.now() + toMiliseconds(this.state.deathTime))).getTime()
				: null;

			return {
				data: {
					objectId: this.objectId,
					newPassword: this.state.hasPassword ? this.state.password : null,
					deathTime: toSeconds(deathTimeTimestamp),
					allowEditDocument: this.state.canEditDocument ? 1 : 0,
				},
			};
		};

		onGenerateLinkFulfilled = (response) => {
			const linkObject = response.data.externalLink;
			const preparedLink = {
				...linkObject,
				deathTime: linkObject?.deathTime
					? (new Date(linkObject.deathTime) - Date.now()) / 1000
					: null,
			};
			this.saveLinkToStore(preparedLink);
			this.setState({
				pending: false,
			});
		};

		onGenerateLinkRejected = (e) => {
			console.error(e);
			this.setState({
				linkEnabled: false,
				pending: false,
			});
		};

		disableLink = () => {
			this.setState({
				pending: true,
				linkEnabled: false,
			}, () => {
				BX.ajax.runAction('disk.api.commonActions.disableExternalLink', {
					data: { objectId: this.objectId },
				}).then(this.onDisableFulfilled).catch(this.onDisableRejected);
			});
		};

		onDisableFulfilled = () =>
		{
			this.saveLinkToStore(null);
			this.setState({
				pending: false,
			});
		};

		onDisableRejected = (e) => {
			console.error(e);
			this.setState({
				linkEnabled: true,
				pending: false,
			});
		};

		saveLinkToStore = (link) => {
			store.dispatch(setExternalLink({ objectId: this.objectId, external: link }));
			this.object = selectById(store.getState(), this.objectId) ?? {};
		};

		renderTurnLinkCard()
		{
			const subtitleTimePart = getDeathTimeMessage(this.state.deathTime);

			const subtitlePasswordPart = ` · ${this.state.hasPassword
				? Loc.getMessage('M_DISK_EXTERNAL_LINK_DIALOG_WITH_PASSWORD')
				: Loc.getMessage('M_DISK_EXTERNAL_LINK_DIALOG_WITHOUT_PASSWORD')}`;

			let subtitleEditPart = ` · ${this.state.canEditDocument
				? Loc.getMessage('M_DISK_EXTERNAL_LINK_DIALOG_WITH_EDIT')
				: Loc.getMessage('M_DISK_EXTERNAL_LINK_DIALOG_WITHOUT_EDIT')}`;

			if (!this.isEditAvailable)
			{
				subtitleEditPart = '';
			}

			const subtitle = `${subtitleTimePart}${subtitlePasswordPart}${subtitleEditPart}`;

			return Card(
				{
					testId: this.getTestId('link-card'),
					accent: this.state.linkEnabled,
					border: true,
				},
				SettingSelector({
					testId: this.getTestId('link-selector'),
					checked: this.state.linkEnabled,
					title: Loc.getMessage('M_DISK_EXTERNAL_LINK_DIALOG_ENABLE_LINK'),
					subtitle,
					switcherSize: SwitcherSize.L,
					onClick: () => {
						if (this.state.linkEnabled)
						{
							this.disableLink();
						}
						else
						{
							this.generateLink();
						}
					},
				}),
			);
		}

		openSecuritySettings = () => {
			SecuritySettingsDialog.open({
				objectId: this.objectId,
				deathTime: this.state.deathTime,
				hasPassword: this.state.hasPassword,
				availableEdit: this.state.availableEdit,
				canEditDocument: this.state.canEditDocument,
				password: this.state.password,
				onSave: ({ deathTime, hasPassword, canEditDocument, password }) => {
					this.setState({
						deathTime,
						hasPassword,
						canEditDocument,
						password,
					});
					if (this.state.linkEnabled)
					{
						this.saveLinkToStore({
							...this.linkObject,
							deathTime,
							hasPassword,
							canEditDocument,
							password,
						});
					}
					else
					{
						this.generateLink();
					}
				},
			}, this.layoutWidget);
		};

		renderSecurityCard()
		{
			return Card(
				{
					onClick: this.openSecuritySettings,
					testId: this.getTestId('security-settings'),
					border: true,
				},
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'space-between',
						},
					},
					Text3({
						text: Loc.getMessage('M_DISK_EXTERNAL_LINK_DIALOG_SECURITY'),
						color: Color.base1,
					}),
					IconView({
						icon: Icon.CHEVRON_TO_THE_RIGHT,
						size: 24,
						color: Color.base3,
					}),
				),
			);
		}
	}

	module.exports = { ExternalLinkDialog };
});
