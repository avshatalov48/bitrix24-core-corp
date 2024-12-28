/**
 * @bxjs_lang_path extension.php
 */

(function()
{
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
				BX.ajax.runAction('immobile.api.Settings.get')
					.then((result) => {
						console.log('immobile.api.Settings.get');
						resolve(result.data);
					})
					.catch((error) => {
						console.error('immobile.api.Settings.get', error);
						resolve({});
					})
				;
			});
		}

		async getForm()
		{
			this.values = Application.storage.getObject('settings.chat', {
				quoteEnable: true,
				quoteFromRight: Application.getApiVersion() < 31,
				historyShow: true,
				autoplayVideo: true,
				backgroundType: SettingsChat.BackgroundType.lightGray,
				chatBetaEnable: false,
				localStorageEnable: true,
			});

			const settings = await this.loadSettingsPromise;

			const isBetaAvailable = settings.IS_BETA_AVAILABLE === true;

			let chatBetaOption = null;
			if (isBetaAvailable)
			{
				const items = [];
				const chatBetaEnableSwitch = FormItem
					.create('chatBetaEnable', FormItemType.SWITCH, BX.message('SE_CHAT_BETA_ENABLE_TITLE_V2'))
					.setValue(this.values.chatBetaEnable)
				;
				items.push(chatBetaEnableSwitch);

				if (typeof chatBetaEnableSwitch.setTestId === 'function')
				{
					chatBetaEnableSwitch.setTestId('CHAT_SETTINGS_CHAT_BETA_ENABLE');
				}

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

			const localStorageEnableSwitch = FormItem.create('localStorageEnable', FormItemType.SWITCH, BX.message('SE_CHAT_LOCAL_STORAGE_ENABLE_TITLE'))
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
				Application.getApiVersion() >= 52
				&& isLocalStorageSupported
				&& settings.IS_CHAT_LOCAL_STORAGE_AVAILABLE === true
			)
			{
				localStorageSection = FormSection
					.create('localStorage', '', BX.message('SE_CHAT_LOCAL_STORAGE_ENABLE_DESCRIPTION'))
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
					FormItem.create('historyShow', FormItemType.SWITCH, BX.message('SE_CHAT_HISTORY_SHOW_TITLE_V2')).setValue(this.values.historyShow),
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

		onSettingsProviderValueChanged(item)
		{
			this.setFormItemValue({
				sectionId: item.sectionCode,
				id: item.id,
				value: item.value,
			});

			if (item && item.id === 'localStorageEnable')
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
		if (
			Application.getApiVersion() < 29
			|| !Application.isWebComponentSupported()
		)
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

			onValueChanged(item)
			{
				super.onValueChanged(item);
				SettingsChatManager.setSettingsProvider(this);
				SettingsChatManager.onSettingsProviderValueChanged(item);
			}
		}

		provider(new SettingsNotifyProvider(
			SettingsChatManager.getProviderId(),
			SettingsChatManager.getProviderTitle(),
			SettingsChatManager.getProviderSubtitle(),
		));
	});
})();
