(() => {
	if (env.isCollaber || env.extranet)
	{
		return;
	}

	const require = (extension) => jn.require(extension);

	const { Loc } = require('loc');

	class UsersSettings
	{
		constructor()
		{
			this.values = Application.storage.getObject('settings.intranet', { isBetaActive: false });

			this.providerId = 'users';
			this.providerTitle = Loc.getMessage('INTRANETMOBILE_SETTINGS_TITLE');
			this.providerSubtitle = '';

			this.isBetaAvailablePromise = this.checkIsBetaAvailable();
			this.isBetaActivePromise = this.checkAjaxActive(
				'isBetaActive',
				'isBetaActive',
			);
		}

		async initOptions()
		{
			const section = await this.createBetaForm();

			/**
			 * @private
			 * @type {FormSection[]}
			 */
			this.options = [section].filter(Boolean);
		}

		hasOptions()
		{
			return this.options.length > 0;
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

		checkIsBetaAvailable()
		{
			return new Promise((resolve) => {
				BX.ajax.runAction('intranetmobile.settings.isBetaAvailable')
					.then((result) => resolve(result.data))
					.catch(() => resolve(false));
			});
		}

		async createBetaForm()
		{
			const isBetaAvailable = await this.isBetaAvailablePromise;
			Application.storage.setBoolean('settings.intranet.isBetaAvailable', isBetaAvailable);

			let intranetBetaOption = null;
			if (isBetaAvailable)
			{
				const isBetaActive = await this.isBetaActivePromise;

				const betaActiveSwitch = this.createSwitchItem(
					'beta',
					'INTRANETMOBILE_SETTINGS_USERS_BETA_ACTIVE_TITLE',
					isBetaActive,
					'INTRANETMOBILE_SETTINGS_USERS_BETA_ACTIVE',
				);

				intranetBetaOption = this.createSection(
					'intranetBeta',
					[betaActiveSwitch],
					'',
				);
			}

			return intranetBetaOption;
		}

		checkAjaxActive(valueName, action)
		{
			return new Promise((resolve) => {
				BX.ajax.runAction(`intranetmobile.settings.${action}`)
					.then((result) => {
						this.values[valueName] = result.data;
						Application.storage.setObject('settings.intranet', this.values);
						resolve(result.data);
					})
					.catch(() => resolve(false));
			});
		}

		onSettingsProviderButtonTap()
		{
			void this.drawForm();
		}

		async drawForm()
		{
			const form = await this.getForm();

			if (this.provider)
			{
				this.provider.openForm(form.compile(), form.getId());
			}
		}

		createSwitchItem(switchId, textCode, isActive, testId)
		{
			// eslint-disable-next-line no-undef
			const formSwitch = FormItem.create(
				switchId,
				// eslint-disable-next-line no-undef
				FormItemType.SWITCH,
				Loc.getMessage(textCode),
			);
			formSwitch.setValue(isActive);

			if (typeof formSwitch.setTestId === 'function')
			{
				formSwitch.setTestId(testId);
			}

			return formSwitch;
		}

		async getForm()
		{
			// eslint-disable-next-line no-undef
			const form = Form.create(this.providerId, this.providerTitle);

			if (this.hasOptions())
			{
				form.addSections(this.options);
			}

			return form;
		}

		createSection(formId, formItems, sectionNameCode)
		{
			// eslint-disable-next-line no-undef
			const option = FormSection.create(formId, Loc.getMessage(sectionNameCode));
			option.addItems(formItems);

			return option;
		}

		setAjaxItemValue(name, params)
		{
			const actions = params.value ? `activate${name}` : `deactivate${name}`;

			BX.ajax.runAction(`intranetmobile.settings.${actions}`).then(
				() => {
					this.values[params.id] = params.value;
					Application.clearCache();
					Application.storage.setObject('settings.intranet', this.values);
					Application.relogin();
				},
			).catch(console.error);
		}

		setFormItemValue(params)
		{
			switch (params.id)
			{
				case 'beta':
				{
					this.setAjaxItemValue('beta', params);
					break;
				}

				default:
				{
					this.values[params.id] = params.value;
					Application.storage.setObject('settings.intranet', this.values);
				}
			}

			return false;
		}

		onSettingsProviderValueChanged(item)
		{
			this.setFormItemValue({
				sectionId: item.sectionCode,
				id: item.id,
				value: item.value,
			});

			return true;
		}

		setSettingsProvider(provider)
		{
			this.provider = provider;

			return true;
		}
	}

	const usersSettingsManager = new UsersSettings();

	BX.addCustomEvent('onRegisterProvider', async (provider) => {
		// eslint-disable-next-line no-undef
		class UsersSettingsProvider extends SettingsProvider
		{
			onButtonTap(data)
			{
				usersSettingsManager.setSettingsProvider(this);
				usersSettingsManager.onSettingsProviderButtonTap(data);
			}

			onValueChanged(item)
			{
				usersSettingsManager.setSettingsProvider(this);
				usersSettingsManager.onSettingsProviderValueChanged(item);
			}
		}

		const enableInMenu = Application.storage.getBoolean('settings.intranet.isBetaAvailable', false);

		if (enableInMenu)
		{
			provider(
				new UsersSettingsProvider(
					usersSettingsManager.getProviderId(),
					usersSettingsManager.getProviderTitle(),
					usersSettingsManager.getProviderSubtitle(),
				),
			);
		}

		await usersSettingsManager.initOptions();
	});
})();
