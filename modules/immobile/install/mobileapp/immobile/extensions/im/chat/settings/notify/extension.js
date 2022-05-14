/**
 * @bxjs_lang_path extension.php
 */

(function() {

	/**
	 *
	 * @type {{PUSH: string, SMART_FILTER: string, COUNTERS: string, PUSH_CATEGORY: string}}
	 */
	this.SettingsNotifyTypes = {
		PUSH: 'push',
		SMART_FILTER: 'smart',
		COUNTERS: 'counters',
		PUSH_CATEGORY: 'push_category'
	};

	class SettingsNotify
	{
		constructor()
		{
			this.pushTypesRename = {
				'im': BX.message('SE_NOTIFY_CATEGORY_IM_TITLE')
			};

			this.userId = BX.componentParameters.get('USER_ID', 0);
			this.languageId = BX.componentParameters.get('LANGUAGE_ID', 'en');
			this.siteId = BX.componentParameters.get('SITE_ID', 's1');

			this.configDataLoaded = false;
			this.values = {
				push: true,
				smart: true
			};
			this.counterTypes = [];
			this.pushTypes = [];

			this.sessionValues = {
				values: {},
				counterTypes: {},
				pushTypes: {}
			};

			this.database = new ReactDatabase(ChatDatabaseName, this.userId, this.languageId, this.siteId);
			this.requestConfigDataSend = false;
			this.requestConfigDataLoaded = false;

			this.providerId = 'notify';
			this.providerTitle = BX.message("SE_NOTIFY_TITLE");
			this.providerSubtitle = BX.message("SE_NOTIFY_DESC");

			this.forms = {};
			this.formIdDefault = this.providerId;
			this.formIsVisible = BX.componentParameters.get('SETTINGS_NOTIFY_VISIBLE', false);
			this.formCurrent = BX.componentParameters.get('SETTINGS_NOTIFY_CURRENT', this.formIdDefault);

			this.provider = BX.componentParameters.get('SETTINGS_NOTIFY_PROVIDER', false);

			this.restQueue = {};

			this.loadCache().then(() =>
			{
				if (!this.requestConfigDataLoaded)
				{
					this.requestConfigData();
				}
			});
		}

		loadCache()
		{
			let promise = new BX.Promise();
			let executeTime = new Date();

			this.database.table(ChatTables.notifyConfig).then(table =>
			{
				table.get().then(items =>
				{
					if (items.length <= 0)
					{
						promise.fulfill(false);
						return false;
					}

					let cacheData = JSON.parse(items[0].VALUE);

					if (this.requestConfigDataLoaded)
					{
						console.info('SettingsNotify.loadCache: cache file "notifyConfig" has been ignored because it was loaded a very late');
						return false;
					}

					this.values = cacheData.values;
					this.pushTypes = cacheData.pushTypes;
					this.counterTypes = cacheData.counterTypes;

					this.configDataLoaded = true;

					console.info(`SettingsNotify.loadCache: config load from cache (${new Date()-executeTime}ms)`);

					this.redrawForm(
						this.prepareForm(this.formCurrent)
					);

					promise.fulfill(true);
				});
			});

			return promise;
		}

		saveCache()
		{
			let executeTime = new Date();

			this.database.table(ChatTables.notifyConfig).then(table => {
				table.delete().then(() =>
				{
					table.add({value : {
						values: this.values,
						pushTypes: this.pushTypes,
						counterTypes: this.counterTypes
					}}).then(() => {
						console.info(`SettingsNotify.saveCache: cache config updated (${new Date()-executeTime}ms)`);
					});
				})
			});
		}

		requestConfigData()
		{
			if (this.requestConfigDataSend)
				return false;

			this.requestConfigDataSend = true;

			let executeTime = new Date();

			BX.rest.callBatch({
				pushStatus : ['mobile.push.status.get', {}],
				smartFilter : ['mobile.push.smartfilter.status.get', {}],
				counterTypes : ['mobile.counter.types.get', {'USER_VALUES': 'Y'}],
				pushTypes : ['mobile.push.types.get', {'USER_VALUES': 'Y'}],
			}, (result) =>
			{
				this.requestConfigDataSend = false;

				if (
					result.pushStatus.error() ||
					result.smartFilter.error() ||
					result.counterTypes.error() ||
					result.pushTypes.error()
				)
				{
					console.error('SettingsNotify.requestConfigData: failed', [
						result.pushStatus.error(),
						result.smartFilter.error(),
						result.counterTypes.error(),
						result.pushTypes.error()
					]);

					return false;
				}

				this.requestConfigDataLoaded = true;

				this.values.push = result.pushStatus.data();
				this.values.smart = result.smartFilter.data();

				this.pushTypes = result.pushTypes.data();
				this.counterTypes = result.counterTypes.data();

				console.info(`SettingsNotify.requestConfigData: config loaded and updated (${new Date() - executeTime}ms)`, [this.values, this.pushTypes, this.counterTypes]);

				this.configDataLoaded = true;

				this.saveCache();

				this.redrawForm(
					this.prepareForm(this.formCurrent)
				);
			});

			return true;
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

		prepareForm(formId, event)
		{
			console.info(`SettingsNotify.prepareForm: create form %c${formId}`, "color: green; font-weight: bold");

			if (formId == 'notify')
			{
				let pushTypes = [];
				if (this.pushTypes.length > 0)
				{
					this.pushTypes.forEach(item =>
					{
						let categoryName = this.pushTypesRename[item.module_id]? this.pushTypesRename[item.module_id]: item.name;

						pushTypes.push(
							new FormItem("push_category_"+item.module_id, FormItemType.BUTTON, categoryName)
								.setButtonTransition()
								.setCustomParam("module_id", item.module_id)
						);
					});
				}
				else
				{
					pushTypes.push(
						new FormItem("loading", FormItemType.BUTTON, BX.message('SE_NOTIFY_LOADING')).setEnabled(false)
					);
				}

				this.forms[this.formIdDefault] = new Form(this.formIdDefault, this.providerTitle).addSections([
					new FormSection("push").addItems([
						new FormItem("push", FormItemType.SWITCH, BX.message("SE_NOTIFY_PUSH_TITLE")).setEnabled(this.configDataLoaded).setValue(this.getFormItemValue({type: SettingsNotifyTypes.PUSH})),
					]),
					new FormSection("smart", '', BX.message("SE_NOTIFY_SMART_FILTER_DESC")).addItems([
						new FormItem("smart", FormItemType.SWITCH, BX.message("SE_NOTIFY_SMART_FILTER_TITLE")).setEnabled(this.configDataLoaded).setValue(this.getFormItemValue({type: SettingsNotifyTypes.SMART_FILTER})),
					]),
					new FormSection("counters", '', BX.message("SE_NOTIFY_MAIN_COUNTER_DETAIL_DESC")).addItems([
						new FormItem("counters", FormItemType.BUTTON, BX.message("SE_NOTIFY_MAIN_COUNTER_TITLE")).setButtonTransition(true),
					]),
					new FormSection("push_category", BX.message("SE_NOTIFY_NOTIFY_TYPES_TITLE")).addItems(pushTypes),
				]);
			}
			else if (formId == 'counters' && this.configDataLoaded)
			{
				let counterTypes = [];
				if (this.counterTypes.length > 0)
				{
					this.counterTypes.forEach(item => {
						counterTypes.push(
							new FormItem(item.type, FormItemType.SWITCH, item.name).setValue(this.getFormItemValue({type: SettingsNotifyTypes.COUNTERS, name: item.type}))
						);
					});
				}

				if (counterTypes.length <= 0)
				{
					counterTypes.push(
						new FormItem("empty", FormItemType.BUTTON, BX.message('SE_NOTIFY_EMPTY')).setEnabled(false)
					)
				}

				this.forms[formId] = new Form(formId, BX.message("SE_NOTIFY_MAIN_COUNTER_TITLE")).addSection(
					new FormSection("counters", '', BX.message("SE_NOTIFY_MAIN_COUNTER_DETAIL_DESC")).addItems(counterTypes)
				);

			}
			else if (formId.indexOf('push_category_') > -1 && this.configDataLoaded)
			{
				let categoryId = formId.substr(14);
				let categoryName = BX.message("SE_NOTIFY_DEFAULT_TITLE");

				let pushTypes = [];
				for (let i = 0; i < this.pushTypes.length; i++)
				{
					if (this.pushTypes[i].module_id != categoryId)
					{
						continue;
					}

					categoryName = this.pushTypesRename[categoryId]? this.pushTypesRename[categoryId]: this.pushTypes[i].name;

					this.pushTypes[i].types.forEach(item =>
					{
						let typeName = this.pushTypesRename[categoryId+'_'+item.type]? this.pushTypesRename[categoryId+'_'+item.type]: item.name;

						pushTypes.push(
							new FormItem("push_category_"+categoryId+"_"+item.type, FormItemType.SWITCH, typeName)
								.setEnabled(!item.disabled)
								.setValue(this.getFormItemValue({type: SettingsNotifyTypes.PUSH_CATEGORY, category: categoryId, name: item.type}))
								.setCustomParam("module_id", categoryId)
								.setCustomParam("type", item.type)
						);
					});

					break;
				}

				if (pushTypes.length <= 0)
				{
					pushTypes.push(
						new FormItem("empty", FormItemType.BUTTON, BX.message('SE_NOTIFY_EMPTY')).setEnabled(false)
					)
				}

				this.forms[formId] = new Form(formId, categoryName).addSection(
					new FormSection("push_category").addItems(pushTypes)
				)
			}
			else
			{
				let formTitle = event? event.title: BX.message('SE_NOTIFY_DEFAULT_TITLE');
				this.forms[formId] = new Form(formId, formTitle).addItems([
					new FormItem("loading", FormItemType.BUTTON, BX.message('SE_NOTIFY_LOADING')).setEnabled(false)
				]);
			}

			return this.forms[formId];
		}

		/**
		 *
		 * @param {Form} form
		 */
		drawForm(form)
		{
			console.info(`SettingsNotify.drawForm: %c${form.getId()}`, "color: green; font-weight: bold");

			this.provider.openForm(
				form.compile(),
				form.getId()
			);
		}

		/**
		 *
		 * @param {Form} form
		 */
		redrawForm(form)
		{
			if (!this.provider || !this.formIsVisible)
			{
				return false;
			}

			if (!this.provider.forms[this.formCurrent])
			{
				console.error(`SettingsNotify.redrawForm: form not found - %c${form.getId()}`, "color: red; font-weight: bold");
				return false;
			}

			console.info(`SettingsNotify.redrawForm: %c${form.getId()}`, "color: green; font-weight: bold");

			let {items, sections} = form.compile();
			this.provider.forms[this.formCurrent].setItems(items);
			this.provider.forms[this.formCurrent].setSections(sections);
		}

		/**
		 *
		 * @param {{type, category?, name?}} params
		 * @returns boolean;
		 */
		getFormItemValue(params)
		{
			let {type, category, name} = params;

			if (type == SettingsNotifyTypes.PUSH)
			{
				if (typeof this.sessionValues.values.push != 'undefined')
				{
					return this.sessionValues.values.push;
				}

				return this.values.push;
			}
			else if (type == SettingsNotifyTypes.SMART_FILTER)
			{
				if (typeof this.sessionValues.values.smart != 'undefined')
				{
					return this.sessionValues.values.smart;
				}

				return this.values.smart;
			}
			else if (type == SettingsNotifyTypes.COUNTERS)
			{
				if (typeof this.sessionValues.counterTypes[name] != 'undefined')
				{
					return this.sessionValues.counterTypes[name];
				}

				let element = this.counterTypes.find(element => element.type == name);
				return element? element.value: false;
			}
			else if (type == SettingsNotifyTypes.PUSH_CATEGORY)
			{
				if (
					typeof this.sessionValues.pushTypes[category] != 'undefined'
					&& typeof this.sessionValues.pushTypes[category][name] != 'undefined'
				)
				{
					return this.sessionValues.pushTypes[category][name];
				}

				let categoryElement = this.pushTypes.find(element => element.module_id === category);
				if (categoryElement)
				{
					let element = categoryElement.types.find(element => element.type === name);
					if (element)
					{
						return element.value;
					}
				}

				return false;
			}

			return false;
		}

		/**
		 *
		 * @param {{type, category?, name?, value}} params
		 * @returns boolean;
		 */
		setFormItemValue(params)
		{
			let {type, category, name, value} = params;

			if (type == SettingsNotifyTypes.PUSH)
			{
				this.sessionValues.values.push = value === true;
				return true;
			}
			else if (type == SettingsNotifyTypes.SMART_FILTER)
			{
				this.sessionValues.values.smart = value === true;
				return true
			}
			else if (type == SettingsNotifyTypes.COUNTERS)
			{
				this.sessionValues.counterTypes[name] = value === true;
				return true;
			}
			else if (type == SettingsNotifyTypes.PUSH_CATEGORY)
			{
				if (typeof this.sessionValues.pushTypes[category] == 'undefined')
				{
					this.sessionValues.pushTypes[category] = {};
				}

				this.sessionValues.pushTypes[category][name] = value === true;
				return true;
			}

			return false;
		}

		setSettingsProvider(provider)
		{
			this.provider = provider;
			BX.componentParameters.set('SETTINGS_NOTIFY_PROVIDER', this.provider);

			return true;
		}

		configCommit(type, value = '')
		{
			if (type.indexOf(SettingsNotifyTypes.PUSH_CATEGORY) === 0)
			{
				if (typeof value == "object")
				{
					if (typeof this.restQueue[SettingsNotifyTypes.PUSH_CATEGORY] === 'undefined')
					{
						this.restQueue[SettingsNotifyTypes.PUSH_CATEGORY] = {
							[Symbol.iterator]() { return new ObjectIterator(this); }
						};
					}

					this.restQueue[SettingsNotifyTypes.PUSH_CATEGORY][type] = value;
				}
			}
			else
			{
				this.restQueue[type] = value;
			}

			clearTimeout(this.restQueueTimeout);
			this.restQueueTimeout = setTimeout(() => {
				this.configCommitWorker()
			}, 600);
		}

		configCommitWorker()
		{
			for (let type in this.restQueue)
			{
				if (!this.restQueue.hasOwnProperty(type))
				{
					continue;
				}

				if (type == SettingsNotifyTypes.PUSH)
				{
					console.info('SettingsNotify.configCommitWorker: push', this.restQueue[type]);
					BX.rest.callMethod('mobile.push.status.set', {'ACTIVE': this.restQueue[type]});
				}
				else if (type == SettingsNotifyTypes.SMART_FILTER)
				{
					console.info('SettingsNotify.configCommitWorker: smart filter', this.restQueue[type]);
					BX.rest.callMethod('mobile.push.smartfilter.status.set', {'ACTIVE': this.restQueue[type]});
				}
				else if (type == SettingsNotifyTypes.COUNTERS)
				{
					let config = {};

					this.counterTypes.forEach(item => {
						config[item.type] = item.value;
					});

					console.info('SettingsNotify.configCommitWorker: counters', config);
					BX.rest.callMethod('mobile.counter.config.set', {'CONFIG': config});
					BX.postComponentEvent("onUpdateConfig", [config], "communication");
				}
				else if (type == SettingsNotifyTypes.PUSH_CATEGORY)
				{
					let configs = [];
					for (let config of this.restQueue[type])
					{
						configs.push(config);
					}
					if (configs.length > 0)
					{
						console.info('SettingsNotify.configCommitWorker: push types', configs);
						BX.rest.callMethod('mobile.push.config.set', {'CONFIG': configs});
					}
				}
			}

			this.saveCache();
			this.restQueue = {};

			return true;
		}

		onDefaultFormOpen()
		{
			if (!this.requestConfigDataLoaded)
			{
				this.requestConfigData();
			}
		}

		onSettingsProviderButtonTap(data)
		{
			this.drawForm(this.prepareForm(data.id, data).setTitle(data.title));

			return true;
		}

		onSettingsProviderValueChanged(item)
		{
			if (item.sectionCode == 'push' && item.id == 'push')
			{
				this.values.push = item.value;

				this.configCommit(SettingsNotifyTypes.PUSH, item.value);
				this.setFormItemValue({
					type: SettingsNotifyTypes.PUSH,
					value: item.value
				});
			}
			else if (item.sectionCode == 'smart' && item.id == 'smart')
			{
				this.values.smart = item.value;

				this.configCommit(SettingsNotifyTypes.SMART_FILTER, item.value);
				this.setFormItemValue({
					type: SettingsNotifyTypes.SMART_FILTER,
					value: item.value
				});
			}
			else if (item.sectionCode == 'counters')
			{
				let type = this.counterTypes.find(element => element.type === item.id);
				if (type)
				{
					type.value = item.value;

					let config = {};

					this.counterTypes.forEach(counterItem => {
						config[counterItem.type] = counterItem.value;
					});

					this.configCommit(SettingsNotifyTypes.COUNTERS, config);
					this.setFormItemValue({
						type: SettingsNotifyTypes.COUNTERS,
						name: type.type,
						value: item.value
					});
				}
			}
			else if (item.sectionCode == 'push_category')
			{
				let category = this.pushTypes.find(element => element.module_id === item.params.module_id);
				if (category)
				{
					let type = category.types.find(element => element.type === item.params.type);
					if (type)
					{
						type.value = item.value;

						this.configCommit(`push_category_${category.module_id}_${type.type}`, {
							module_id: category.module_id,
							type: type.type,
							active: item.value
						});
						this.setFormItemValue({
							type: SettingsNotifyTypes.PUSH_CATEGORY,
							category: category.module_id,
							name: type.type,
							value: item.value
						});
					}
				}
			}
			else
			{
				console.error("SettingsNotify.onSettingsProviderValueChanged: value not saved!", item);
			}

			return true;
		}

		onSettingsProviderStateChanged(event, formId)
		{
			if (event == 'onViewShown')
			{
				this.formIsVisible = true;
				this.formCurrent = formId;

				BX.componentParameters.set('SETTINGS_NOTIFY_CURRENT', this.formCurrent);
				BX.componentParameters.set('SETTINGS_NOTIFY_VISIBLE', this.formIsVisible);

				if (formId == this.formIdDefault)
				{
					this.onDefaultFormOpen();
				}
			}
			else if (event == 'onViewRemoved')
			{
				this.formIsVisible = false;
				BX.componentParameters.set('SETTINGS_NOTIFY_VISIBLE', this.formIsVisible);
			}

			return true;
		}
	}

	this.SettingsNotifyManager = new SettingsNotify();


	/**
	 * Subscribe to settings draw event
	 */

	BX.addCustomEvent("onRegisterProvider", (provider) => {

		class SettingsNotifyProvider extends SettingsProvider
		{
			onButtonTap(data)
			{
				super.onValueChanged(data);
				SettingsNotifyManager.setSettingsProvider(this);
				SettingsNotifyManager.onSettingsProviderButtonTap(data);
			}

			onValueChanged(item)
			{
				super.onValueChanged(item);
				SettingsNotifyManager.setSettingsProvider(this);
				SettingsNotifyManager.onSettingsProviderValueChanged(item);
			}

			onStateChanged(event, formId)
			{
				super.onStateChanged(event, formId);
				SettingsNotifyManager.setSettingsProvider(this);
				SettingsNotifyManager.onSettingsProviderStateChanged(event, formId);
			}
		}

		provider(new SettingsNotifyProvider(
			SettingsNotifyManager.getProviderId(),
			SettingsNotifyManager.getProviderTitle(),
			SettingsNotifyManager.getProviderSubtitle()
		));
	});

})();
