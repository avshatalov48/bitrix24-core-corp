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
				BX.ajax.runAction('tasksmobile.Settings.isBetaAvailable')
					.then((result) => resolve(result.data))
					.catch(() => resolve(false));
			});
		}

		async createBetaForm()
		{
			const isBetaAvailable = await this.isBetaAvailablePromise;

			let taskBetaOption = null;
			if (isBetaAvailable)
			{
				const isBetaActive = await this.isBetaActivePromise;

				const taskBetaActiveSwitch = this.createSwitchItem(
					'taskBetaActive',
					'TASKSMOBILE_SETTINGS_TASK_BETA_ACTIVE_TITLE',
					isBetaActive,
					'TASK_SETTINGS_TASK_BETA_ACTIVE',
				);

				taskBetaOption = this.createSection(
					'taskBeta',
					[taskBetaActiveSwitch],
					'TASKSMOBILE_SETTINGS_TASK_BETA_TITLE',
				);
			}

			return taskBetaOption;
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
		// eslint-disable-next-line no-undef
		class TaskSettingsProvider extends SettingsProvider
		{
			onButtonTap(data)
			{
				taskSettingsManager.setSettingsProvider(this);
				taskSettingsManager.onSettingsProviderButtonTap(data);
			}

			onValueChanged(item)
			{
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

		await taskSettingsManager.initOptions();

		checkDisabledToolById('tasks', false)
			.then((tasksDisabled) => {
				const enableInMenu = !tasksDisabled && taskSettingsManager.hasOptions();
				if (enableInMenu !== enableInMenuFromCache)
				{
					Application.storage.setBoolean('settings.task.enableInMenu', enableInMenu);
					BX.onCustomEvent('onAppSettingsShouldRedraw');
				}
			})
			.catch((result) => console.error(result));
	});
})();
