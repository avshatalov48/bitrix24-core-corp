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
			this.providerTitle = BX.message('SE_CHAT_TITLE');
			this.providerSubtitle = '';
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

		getForm()
		{
			this.values = Application.storage.getObject('settings.chat', {
				quoteEnable: true,
				quoteFromRight: Application.getApiVersion() < 31,
				historyShow: true,
				autoplayVideo: ChatPerformance.isAutoPlayVideoSupported(),
				backgroundType: SettingsChat.BackgroundType.lightGray,
				chatBetaEnable: false,
			});

			const backgroundItems = [];
			for (let type in SettingsChat.BackgroundType)
			{
				if (SettingsChat.BackgroundType.hasOwnProperty(type))
				{
					backgroundItems.push({value: SettingsChat.BackgroundType[type], name: BX.message('SE_CHAT_BACKGROUND_COLOR_'+SettingsChat.BackgroundType[type]) || type});
				}
			}

			let gestureQuoteOption = null;
			if (ChatPerformance.isGestureQuoteSupported())
			{
				gestureQuoteOption = FormSection.create("quote", BX.message("SE_CHAT_QUOTE_TITLE")).addItems([
					FormItem.create("quoteEnable", FormItemType.SWITCH, BX.message("SE_CHAT_QUOTE_ENABLE_TITLE")).setValue(this.values.quoteEnable),
					FormItem.create("quoteFromRight", FormItemType.SWITCH, BX.message("SE_CHAT_QUOTE_FROM_RIGHT_TITLE")).setValue(this.values.quoteFromRight).setEnabled(!this.values.quoteFromRight || Application.getApiVersion() >= 31),
				])
			}

			let chatBetaOption = null;
			if (Application.getPlatform() === 'ios' && Application.getApiVersion() >= 43 && Application.isBeta())
			{
				const chatBetaEnableSwitch =
					FormItem.create('chatBetaEnable', FormItemType.SWITCH, BX.message('SE_CHAT_BETA_ENABLE_TITLE'))
						.setValue(this.values.chatBetaEnable)
				;

				if (typeof chatBetaEnableSwitch.setTestId === 'function')
				{
					chatBetaEnableSwitch.setTestId('CHAT_SETTINGS_CHAT_BETA_ENABLE');
				}

				chatBetaOption =
					FormSection
						.create('chatBeta', BX.message('SE_CHAT_BETA_TITLE'))
						.addItems([ chatBetaEnableSwitch ])
				;
			}

			return Form.create(this.providerId, this.providerTitle).addSections([
				FormSection.create("history", BX.message("SE_CHAT_HISTORY_TITLE")).addItems([
					FormItem.create("historyShow", FormItemType.SWITCH, BX.message("SE_CHAT_HISTORY_SHOW_TITLE")).setValue(this.values.historyShow),
				]),
				gestureQuoteOption,
				FormSection.create("autoplay", BX.message("SE_CHAT_AUTOPLAY_TITLE")).addItems([
					FormItem.create("autoplayVideo", FormItemType.SWITCH, BX.message("SE_CHAT_AUTOPLAY_VIDEO_TITLE")).setValue(this.values.autoplayVideo),
				]),
				FormSection.create("background", BX.message("SE_CHAT_BACKGROUND_TITLE"), BX.message('SE_CHAT_DESC')).addItems([
					FormItem.create("backgroundType", FormItemType.SELECTOR, BX.message("SE_CHAT_BACKGROUND_COLOR_TITLE")).setSelectorItems(backgroundItems).setValue(this.values.backgroundType),
				]),
				chatBetaOption,
			]);
		}

		drawForm()
		{
			const form = this.getForm();

			this.provider.openForm(
				form.compile(),
				form.getId()
			);
		}

		/**
		 *
		 * @returns boolean;
		 */
		setFormItemValue(params)
		{
			let {id, value} = params;

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
				value: item.value
			});

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

	BX.addCustomEvent("onRegisterProvider", (provider) =>
	{
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
			SettingsChatManager.getProviderSubtitle()
		));
	});

})();
