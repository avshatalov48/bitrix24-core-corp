(() => {
	const require = (extension) => jn.require(extension);

	const { Loc } = require('loc');
	const { checkDisabledToolById } = require('settings/disabled-tools');

	class TaskSettings
	{
		constructor()
		{
			this.values = Application.storage.getObject('settings.task', { taskBetaActive: false });

			this.providerId = 'task';
			this.providerTitle = Loc.getMessage('TASKSMOBILE_SETTINGS_TASK_TITLE');
			this.providerSubtitle = '';

			this.isBetaAvailablePromise = this.checkIsBetaAvailable();
			this.isBetaActivePromise = this.checkAjaxActive(
				'taskBetaActive',
				'isBetaActive',
			);
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
				BX.ajax.runAction('tasksmobile.Settings.isBetaAvailable')
					.then((result) => resolve(result.data))
					.catch(() => resolve(false));
			});
		}

		checkAjaxActive(valueName, action)
		{
			return new Promise((resolve) => {
				BX.ajax.runAction(`tasksmobile.Settings.${action}`)
					.then((result) => {
						this.values[valueName] = result.data;
						Application.storage.setObject('settings.task', this.values);
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

			this.provider.openForm(form.compile(), form.getId());
		}

		createSwitch(switchId, messageId, value)
		{
			const formSwitch = FormItem.create(
				switchId,
				FormItemType.SWITCH,
				Loc.getMessage(messageId),
			);
			formSwitch.setValue(value);

			return formSwitch;
		}

		async getForm()
		{
			const isBetaAvailable = await this.isBetaAvailablePromise;

			let taskBetaOption = null;
			if (isBetaAvailable)
			{
				const isBetaActive = await this.isBetaActivePromise;

				const taskBetaActiveSwitch = FormItem.create(
					'taskBetaActive',
					FormItemType.SWITCH,
					Loc.getMessage('TASKSMOBILE_SETTINGS_TASK_BETA_ACTIVE_TITLE'),
				);
				taskBetaActiveSwitch.setValue(isBetaActive);

				if (typeof taskBetaActiveSwitch.setTestId === 'function')
				{
					taskBetaActiveSwitch.setTestId('TASK_SETTINGS_TASK_BETA_ACTIVE');
				}

				taskBetaOption = FormSection.create('taskBeta', Loc.getMessage('TASKSMOBILE_SETTINGS_TASK_BETA_TITLE'));
				taskBetaOption.addItems([
					taskBetaActiveSwitch,
				]);
			}

			const form = Form.create(this.providerId, this.providerTitle);
			form.addSections([taskBetaOption]);

			return form;
		}

		setAjaxItemValue(name, params)
		{
			const actions = params.value ? `activate${name}` : `deactivate${name}`;

			BX.ajax.runAction(`tasksmobile.Settings.${actions}`).then(
				() => {
					this.values[params.id] = params.value;
					Application.clearCache();
					Application.storage.setObject('settings.task', this.values);
					Application.relogin();
				},
			).catch(console.error);
		}

		setFormItemValue(params)
		{
			switch (params.id)
			{
				case 'taskBetaActive':
				{
					this.setAjaxItemValue('Beta', params);
					break;
				}

				default:
				{
					this.values[params.id] = params.value;
					Application.storage.setObject('settings.task', this.values);
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

	const taskSettingsManager = new TaskSettings();

	BX.addCustomEvent('onRegisterProvider', async (provider) => {
		class TaskSettingsProvider extends SettingsProvider
		{
			onButtonTap(data)
			{
				super.onValueChanged(data);

				taskSettingsManager.setSettingsProvider(this);
				taskSettingsManager.onSettingsProviderButtonTap(data);
			}

			onValueChanged(item)
			{
				super.onValueChanged(item);

				taskSettingsManager.setSettingsProvider(this);
				taskSettingsManager.onSettingsProviderValueChanged(item);
			}
		}

		const enableInMenuFromCache = Application.storage.getBoolean('settings.task.enableInMenu', false);

		if (enableInMenuFromCache)
		{
			provider(
				new TaskSettingsProvider(
					taskSettingsManager.getProviderId(),
					taskSettingsManager.getProviderTitle(),
					taskSettingsManager.getProviderSubtitle(),
				),
			);
		}

		const isBetaAvailable = await taskSettingsManager.isBetaAvailablePromise;
		checkDisabledToolById('tasks')
			.then((tasksDisabled) => {
				console.log(tasksDisabled);
				const enableInMenu = (isBetaAvailable && !tasksDisabled);
				if (enableInMenu !== enableInMenuFromCache)
				{
					Application.storage.setBoolean('settings.task.enableInMenu', enableInMenu);
					BX.onCustomEvent('onAppSettingsShouldRedraw');
				}
			})
			.catch((result) => console.error(result));
	});
})();
