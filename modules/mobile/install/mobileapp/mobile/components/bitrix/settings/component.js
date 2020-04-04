/**
* @bxjs_lang_path component.php
*/

(function()
{
	this.SettingsProvider = class SettingsProvider
	{
		/**
		 *
		 * @param {string} id
		 * @param {string} title
		 * @param {string} subtitle
		 */
		constructor(id, title, subtitle = "")
		{
			this.title = title;
			this.subtitle = subtitle;
			this.id = id;
			this.forms = {};
		}

		getSection()
		{
			return new FormItem(
				this.id,
				FormItemType.BUTTON,
				this.title,
				this.subtitle
			).setButtonTransition(true).setCustomParam("providerId", this.id).compile()
		};

		/**
		 * Handler of button tap
		 */
		onButtonTap(item){
			//must be overridden in subclass
		}

		/**
		 * Handles the changes of settings
		 */
		onValueChanged(item){
			//must be overridden in subclass
		}

		/**
		 * Handles the changes of form's
		 */
		onStateChanged(state, formId){
			//must be overridden in subclass
		}

		/**
		 * Opens another one settings form
		 * @param data
		 * @param formId
		 * @param onReady
		 */
		openForm(data, formId, onReady = null)
		{
			data.onReady = obj=>
			{
				this.forms[formId] = obj;

				if(typeof onReady == "function")
				{
					onReady(obj);
				}

				obj.setListener((event, data)=>
				{
					if(event === "onItemChanged")
					{
						if(data.type == "button")
							this.onButtonTap(data);
						else
							this.onValueChanged(data);
					}
					else
					{
						this.onStateChanged(event, formId);
					}

					this.listener(event, data, formId);
				});
			};

			PageManager.openWidget("form", data);
		}

		/**
		 * Global event listener
		 * If you are going to override this method in subclass
		 * don't forget call super.listener()
		 * @param event
		 * @param data
		 * @param formId
		 */
		listener(event, data, formId)
		{
			if(event == "onViewRemoved")
			{
				console.info(`SettingsProvider.listener: onViewRemoved - %c${formId}`, "color: red; font-weight: bold");
				this.forms[formId] = null;
				delete this.forms[formId];
			}
		}
	};

	let AppSettingsManager = {
		/**
		 * @type  {Array<SettingsProvider>} provider
		 */
		providers:[],
		listener:function(event, item){
			if(item)
			{
				/**
				 * @type  {SettingsProvider} provider
				 */
				let provider = this.providerById(item.params.providerId);
				if(provider)
					provider.onButtonTap(item);
			}
		},
		/**
		 * @param id
		 * @return {SettingsProvider}
		 */
		providerById:function(id)
		{
			return this.providers.find(provider=>provider.id === id);
		},
		/**
		 *
		 * @param {SettingsProvider} provider
		 */
		addProvider:function(provider)
		{
			if(provider instanceof SettingsProvider)
				this.providers.push(provider);
		},
		init:function()
		{
			settings.setListener((event, item) => this.listener(event, item));

			BX.onCustomEvent("onRegisterProvider", [ provider => this.addProvider(provider)]);

			let items = [];
			if(this.providers.length > 0)
			{
				this.providers.forEach(provider=>
				{
					/**
					 * @type {SettingsProvider} provider
					 */
					items.push(provider.getSection());
				})
			}

			BX.onViewLoaded(()=>settings.setItems(items, [
				new FormSection("main", BX.message("SETTINGS_TITLE")).compile()
			]));

			console.info("AppSettingsManager.init:", items);
		},
	};

	AppSettingsManager.init()

})();