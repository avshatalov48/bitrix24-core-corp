/**
 * @bxjs_lang_path extension.php
 */

(function()
{
	const require = jn.require;

	const { RestMethod } = require('im/messenger/const');
	const { runAction } = require('im/messenger/lib/rest');

	class SettingsChat
	{
		constructor()
		{
			this.values = {};

			this.counterTypes = [];
			this.pushTypes = [];

			this.providerId = 'chat';
			this.providerTitle = BX.message('SE_MESSENGER_TITLE');
			this.providerSubtitle = '';

			this.loadSettingsPromise = this.loadSettings();
		}

		getProviderId()
		{
			return this.providerId;
		}

		getProviderTitle()
		{
			return this.providerTitle;
		}

		getProviderSubtitle()
		{
			return this.providerSubtitle;
		}

		loadSettings()
		{
			return new Promise((resolve) => {
				BX.ajax.runAction(RestMethod.immobileSettingsGet)
					.then((result) => {
						console.log(RestMethod.immobileSettingsGet);
						resolve(result.data);
					})
					.catch((error) => {
						console.error(RestMethod.immobileSettingsGet, error);
						resolve({});
					})
				;
			});
		}

		async getForm()
		{
			this.values = Application.storage.getObject('settings.chat', {
				quoteEnable: true,
				quoteFromRight: false,
				historyShow: true,
				autoplayVideo: true,
				backgroundType: SettingsChat.BackgroundType.lightGray,
				chatBetaEnable: false,
				chatDevModeEnable: false,
				localStorageEnable: true,
				messengerV2Enabled: false,
			});

			const settings = await this.loadSettingsPromise;

			const isBetaAvailable = settings.IS_BETA_AVAILABLE === true;
			const isMessengerV2Enabled = settings.IS_MESSENGER_V2_ENABLED === true;

			let chatBetaOption = null;
			if (isBetaAvailable)
			{
				const items = [];
				const chatBetaEnableSwitch = FormItem
					.create('chatBetaEnable', FormItemType.SWITCH, BX.message('SE_CHAT_BETA_ENABLE_TITLE_V2'))
					.setValue(this.values.chatBetaEnable)
				;

				if (typeof chatBetaEnableSwitch.setTestId === 'function')
				{
					chatBetaEnableSwitch.setTestId('CHAT_SETTINGS_CHAT_BETA_ENABLE');
				}

				items.push(chatBetaEnableSwitch);

				const chatDevModeEnableSwitch = FormItem
					.create('chatDevModeEnable', FormItemType.SWITCH, BX.message('SE_CHAT_DEV_MODE_ENABLE_TITLE'))
					.setValue(this.values.chatDevModeEnable)
				;

				if (typeof chatDevModeEnableSwitch.setTestId === 'function')
				{
					chatDevModeEnableSwitch.setTestId('CHAT_SETTINGS_CHAT_DEV_ENABLE');
				}

				items.push(chatDevModeEnableSwitch);

				const messengerV2EnabledSwitch = FormItem
					.create('messengerV2Enabled', FormItemType.SWITCH, 'MessengerV2')
					.setValue(isMessengerV2Enabled)
				;

				if (typeof messengerV2EnabledSwitch.setTestId === 'function')
				{
					messengerV2EnabledSwitch.setTestId('CHAT_SETTINGS_MESSENGER_V2_ENABLED');
				}

				items.push(messengerV2EnabledSwitch);

				// // TODO this setting may need to be reverted
				// const bitrixCallDevEnableSwitch = FormItem.create(
				// 	'bitrixCallDevEnable',
				// 	FormItemType.SWITCH,
				// 	BX.message('SE_CHAT_BETA_CALL_ENABLE_TITLE_V2'),
				// )
				// 	.setValue(this.values.bitrixCallDevEnable)
				// ;
				//
				// if (typeof bitrixCallDevEnableSwitch.setTestId === 'function')
				// {
				// 	bitrixCallDevEnableSwitch.setTestId('CHAT_SETTINGS_CALL_DEV_ENABLE');

				chatBetaOption = FormSection
					.create('chatBeta', BX.message('SE_CHAT_BETA_TITLE_V2'))
					.addItems(items)
				;
			}

			const localStorageEnableSwitch = FormItem.create('localStorageEnable', FormItemType.SWITCH, BX.message('SE_CHAT_LOCAL_STORAGE_ENABLE_TITLE_MSGVER_1'))
				.setValue(this.values.localStorageEnable)
			;

			if (typeof localStorageEnableSwitch.setTestId === 'function')
			{
				localStorageEnableSwitch.setTestId('CHAT_SETTINGS_LOCAL_STORAGE_ENABLE');
			}

			let localStorageSection = null;
			const isSupportedAndroid = (
				Application.getPlatform() === 'android'
				&& parseInt(Application.getBuildVersion(), 10) >= 2443
			);
			const isSupportedIos = device.platform === 'iOS'
				&& parseInt(device.version, 10) >= 15
			;
			const isLocalStorageSupported = isSupportedAndroid || isSupportedIos;

			if (
				isLocalStorageSupported
				&& settings.IS_CHAT_LOCAL_STORAGE_AVAILABLE === true
			)
			{
				localStorageSection = FormSection
					.create('localStorage', '', BX.message('SE_CHAT_LOCAL_STORAGE_ENABLE_DESCRIPTION_MSGVER_1'))
					.addItems([localStorageEnableSwitch])
				;
			}

			const autoplayVideoSection = FormSection
				.create('autoplay', BX.message('SE_CHAT_AUTOPLAY_TITLE_V2'))
				.addItems([
					FormItem
						.create('autoplayVideo', FormItemType.SWITCH, BX.message('SE_CHAT_AUTOPLAY_VIDEO_TITLE_V2'))
						.setValue(this.values.autoplayVideo),
				])
			;

			return Form.create(this.providerId, this.providerTitle).addSections([
				FormSection.create('history', BX.message('SE_CHAT_HISTORY_TITLE')).addItems([
					FormItem.create('historyShow', FormItemType.SWITCH, BX.message('SE_CHAT_HISTORY_SHOW_TITLE_V2_MSGVER_1')).setValue(this.values.historyShow),
				]),
				localStorageSection,
				autoplayVideoSection,
				chatBetaOption,
			]);
		}

		async drawForm()
		{
			const form = await this.getForm();

			this.provider.openForm(
				form.compile(),
				form.getId(),
			);
		}

		/**
		 *
		 * @returns boolean;
		 */
		setFormItemValue(params)
		{
			const { id, value } = params;

			this.values[id] = value;

			Application.storage.setObject('settings.chat', this.values);

			return false;
		}

		setSettingsProvider(provider)
		{
			this.provider = provider;

			return true;
		}

		onSettingsProviderButtonTap(item)
		{
			this.drawForm();

			return true;
		}

		async onSettingsProviderValueChanged(item)
		{
			this.setFormItemValue({
				sectionId: item.sectionCode,
				id: item.id,
				value: item.value,
			});

			if (item && item.id === 'messengerV2Enabled')
			{
				try
				{
					const result = await runAction(RestMethod.immobileSettingsToggleMessengerV2);
					console.log(RestMethod.immobileSettingsToggleMessengerV2, result);
				}
				catch (error)
				{
					console.error(RestMethod.immobileSettingsToggleMessengerV2, error);
				}
			}

			const restartRequiringItems = [
				'localStorageEnable',
				'chatDevModeEnable',
				'messengerV2Enabled',
			];
			if (item && restartRequiringItems.includes(item.id))
			{
				Application.relogin();
			}

			if (item && item.id === 'chatBetaEnable')
			{
				BX.postComponentEvent('ImMobile.Messenger.Settings.Chat:change', [{ id: item.id, value: item.value }]);
			}

			return true;
		}
	}
	SettingsChat.BackgroundType = {
		lightGray: 'LIGHT_GRAY',
		lightGreen: 'LIGHT_GREEN',
		pink: 'PINK',
		creamy: 'CREAMY',
		dark: 'DARK',
	};

	this.SettingsChatManager = new SettingsChat();

	/**
	 * Subscribe to settings draw event
	 */

	BX.addCustomEvent('onRegisterProvider', (provider) => {
		if (!Application.isWebComponentSupported())
		{
			return false;
		}
		class SettingsNotifyProvider extends SettingsProvider
		{
			onButtonTap(data)
			{
				super.onValueChanged(data);
				SettingsChatManager.setSettingsProvider(this);
				SettingsChatManager.onSettingsProviderButtonTap(data);
			}

			async onValueChanged(item)
			{
				super.onValueChanged(item);
				SettingsChatManager.setSettingsProvider(this);
				await SettingsChatManager.onSettingsProviderValueChanged(item);
			}
		}

		provider(new SettingsNotifyProvider(
			SettingsChatManager.getProviderId(),
			SettingsChatManager.getProviderTitle(),
			SettingsChatManager.getProviderSubtitle(),
		));
	});
})();
