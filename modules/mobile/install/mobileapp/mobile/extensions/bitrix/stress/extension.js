/**
 * @bxjs_lang_path extension.php
 */

(() =>
{
	/**
	 * @class StressMeasure
	 */
	class StressMeasure
	{
		/**
		 * @param {StressMeasureUI} ui
		 * @param params
		 */
		constructor(ui, params = null)
		{
			this.currentState = params["currentData"];
			this.disclaimerAccepted = false;
			let setCurrentData = (params["setCurrentData"] !== false);
			BX.addCustomEvent("onDisclaimerAccepted", () =>
			{
				new RestExecutor("socialnetwork.api.user.stresslevel.setdisclaimer",
					{data: {}})
					.setStartRequestHandler(cache => Notify.showIndicatorLoading())
					.call()
					.then(data =>
					{
						Notify.hideCurrentIndicator();
						let result = data.result;
						if (result && typeof result["DATE_SIGNED"] != "undefined")
						{
							this.disclaimerAccepted = true;
							this.measure();
						}
					});

			});

			/**
			 * @type {StressMeasureUI}
			 */
			this.ui = ui;
			BX.onViewLoaded(() =>
			{
				this.ui.setTitle({text: BX.message("STRESS_LEVEL_TITLE"), type: "section"});
				this.ui.setRightButtons([
					{
						type: 'more',
						badgeCode: 'access_more',
						callback: () =>
						{
							if (this.popupMenu)
							{
								this.popupMenu.show();
							}
						},
					},
				]);
				BX.addCustomEvent("onAppActiveBefore", () => ui.hideLoading());
				this.ui.setHandler((event, data) => this.handler(event, data));
				if (this.currentState == null)
				{
					this.ui.setData(StateTemplates.Clear());
				}
				else
				{
					if (setCurrentData)
					{
						this.ui.setData(StateUtils.exitingMeasure(this.currentState));
					}
				}

				BX.rest.callBatch({
						stress: ['socialnetwork.api.user.stresslevel.get', {fields: {userId: env.userId}}],
						disclaimer: ['socialnetwork.api.user.stresslevel.getdisclaimer', {data: {}}],
						access: ['socialnetwork.api.user.stresslevel.getaccess', {
							fields: {userId: env.userId}
						}]
					},
					res =>
					{
						let result = (res.stress) ? res.stress.answer.result : false;
						if (typeof result === "object" && typeof result.value !== "undefined")
						{
							result.token = result.hash;
							this.currentState = result;
							this.ui.setData(StateUtils.exitingMeasure(this.currentState));
						}
						else
						{
							if (this.currentState != null)
							{
								this.ui.setData(StateUtils.exitingMeasure(this.currentState));
							}
							else
							{
								this.ui.setData(StateTemplates.Clear());
							}
						}

						let access = (res.access ? res.access.answer.result.value === "Y" : false);
						this.createMenuWithAccess(access);
						this.disclaimerAccepted = (res.disclaimer && typeof res.disclaimer.answer.result["DATE_SIGNED"] != "undefined");

						setTimeout(() => this.ui.hideLoading(), 100)
					}
				);
			});
		}

		handler(event, data)
		{
			if (event === "onMeasureResult")
			{
				console.log("onMeasureResult", data);

				if (data.stress)
				{
					let rowState = {
						value: Math.round(data.stress * 100),
						type: data.stress_c,
						comment: BX.message(
							`STRESS_${data.stress_c.toUpperCase()}_${Math.floor(Math.random() * 5) + 1}`),
						token: data.token
					};

					console.log("onMeasureResultAfterCeil", rowState);

					setTimeout(() => this.ui.setData(StateUtils.newMeasure(rowState)), 200);
				}
			}
			else
			{
				if (event === "onButtonClick")
				{
					reflectFunction(this, data.id).call(null, data.params);
				}
			}
		}

		measure()
		{
			if (!this.disclaimerAccepted)
			{
				PageManager.openPage({
					url: "/mobile/stresslevel/disclaimer.php", backdrop: {
						swipeAllowed: false,
						showOnTop: true,
						bounceEnable: false,
						hideNavigationBar: true,
						topPosition: 100,
					}
				});
			}
			else
			{
				this.ui.showLoading({opacity: 0.9});
				this.ui.measureStress({userId: env.userId});
			}
		}

		savePrivate(params)
		{
			this.saveResult(params, "N");
		}

		showDescription(params)
		{
			let cacheExists = false;
			let open = result =>
			{
				this.ui.showDescription(StateUtils.stressDescription(params, result.description),
					BX.message("STRESS_WHAT_DOES_IT_MEAN"));
			};
			new RestExecutor("socialnetwork.api.user.stresslevel.getvaluedescription",
				{value: params.value, type: params.type})
				.setCacheHandler(result => open(result))
				.setStartRequestHandler(cache =>
				{
					cacheExists = cache;
					if (!cache)
					{
						Notify.showIndicatorLoading({text: BX.message("DESC_LOADING")});
					}
				})
				.call(true)
				.then(data =>
				{
					Notify.hideCurrentIndicator();
					let result = data.result;
					if (result && result.description && !cacheExists)
					{
						open(result);
					}
				});

		}

		saveResult(params, share = "Y")
		{
			Notify.showIndicatorLoading({text: BX.message("STRESS_SAVING_RESULT")});
			BX.rest.callBatch({
					add: ['socialnetwork.api.user.stresslevel.add', {
						fields: {
							userId: env.userId,
							value: params.value,
							type: params.type,
							comment: params.comment,
							hash: params.token
						}
					}],
					access: ['socialnetwork.api.user.stresslevel.setaccess', {
						fields: {
							userId: env.userId,
							value: share
						}
					}]
				},
				result =>
				{
					Notify.showIndicatorSuccess({hideAfter: 1000});
					BX.postComponentEvent("onStressMeasureChanged", [params]);
					this.currentState = params;
					this.ui.setData(StateUtils.exitingMeasure(params));
				},
				false, false,
				"stressLevelAdd"
			);
		}

		cancelMeasure()
		{
			if (this.currentState)
			{
				this.ui.setData(StateUtils.exitingMeasure(this.currentState));
			}
			else
			{
				this.ui.setData(StateTemplates.Clear());
			}
		}

		share()
		{
			let style = WidgetListStyle.create()
				.setFont(WidgetListItemFont.create()
					.setColor("#333333").setSize(20));
			let cancelStyle = WidgetListStyle.create()
				.setFont(WidgetListItemFont.create()
					.setColor("#fb0000").setSize(20));
			BackdropMenu.create("stress_share")
				.setEventListener((name, params, message, backdrop) => {

					if (name === 'selected')
					{
						if(params.id === "stream")
						{
							this.shareActivityStream();
						}

					}
				}, "stress")
				.setOnlyMediumPosition(true)
				.setShouldResizeContent(false)
				.setItems(
					[
						BackdropMenuItem.create("stream")
							.setType("button")
							.setTitle(BX.message("STRESS_SHARE_NEWS"))
							.setStyles({label:style, title:style})
						,
						BackdropMenuItem.create("cancel")
							.setType("button")
							.setTitle(BX.message("STRESS_SHARE_CANCEL"))
							.setStyles({label:cancelStyle, title:cancelStyle}),
						BackdropMenuItem.create("space").setHeight(30),
					]
				).show();
		}

		shareActivityStream()
		{
			(new RecipientList(["users", "groups", "departments"], {users:{showAll:true}}))
				.open({returnShortFormat: true})
				.then(recipients =>
				{
					Notify.showIndicatorLoading();
					BX.rest.callBatch(
						{
							data: ['mobile.intranet.stresslevel.sharedata.get', {
								"title": BX.message("STRESS_SHARE_POST_TITLE"),
								"message": BX.message("STRESS_SHARE_POST_MESSAGE"),
								recipients
							}],
							add: ['log.blogpost.add', {
								POST_MESSAGE: '$result[data][message]',
								POST_TITLE: '$result[data][title]',
								DEST: '$result[data][recipients]',
								FILES: '$result[data][files]'
							}],
							get: ["log.blogpost.get", {POST_ID: '$result[add]'}],
							update: ["log.blogpost.update", {
								POST_ID: '$result[add]',
								POST_TITLE: '$result[data][title]',
								POST_MESSAGE: '[DISK FILE ID=' + '$result[get][0][FILES][0]' + ' ]'
							}],
						},
						(response) =>
						{
							for(let result of Object.values(response))
							{
								if(result.answer.error)
								{
									Notify.showIndicatorError({hideAfter: 3000, onTap:()=>Notify.hideCurrentIndicator(), text: BX.message("STRESS_SHARING_FAILED")});
									return;
								}
							}

							Notify.showIndicatorSuccess({hideAfter: 2000});
							PageManager.openPage({url:`/mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=${response["add"].answer.result}`, cache:false})
						},
						true, false, "stressLevelShare")
				})
		}

		createMenuWithAccess(access = false)
		{
			if (!this.popupMenu)
			{
				this.popupMenu = dialogs.createPopupMenu();
			}
			let items = [];

			if (access)
			{
				let iconUrl = `${component.path}/images/denided.png`;
				items.push({title: BX.message("STRESS_MENU_ACCESS_DENIED"), iconUrl, sectionCode: "main", id: "N"});
			}
			else
			{
				let iconUrl = `${component.path}/images/accept.png`;
				items.push({title: BX.message("STRESS_MENU_ACCESS_ACCEPT"), iconUrl, sectionCode: "main", id: "Y"});
			}
			this.popupMenu.setData(items, [{id: "main", title: ""}],
				(event, item) =>
				{
					if (event === "onItemSelected")
					{
						this.createMenuWithAccess(item.id === "Y");
						new RestExecutor("socialnetwork.api.user.stresslevel.setaccess", {
							fields: {
								userId: env.userId,
								value: item.id
							}
						})
							.call()

					}
					console.log(event, item);
				});
		}

		static open(initData = null, shouldLoad = true)
		{
			PageManager.openComponent("JSStackComponent",
				{
					scriptPath: availableComponents["stress"].publicUrl,
					componentCode: "stress",
					params: {
						params: {
							setCurrentData: false,
							currentData: initData,
							shouldLoad
						}
					},
					rootWidget: {
						name: "stress",
						settings: {
							title: BX.message("STRESS_LEVEL_TITLE"),
							objectName: "stress",
							data: StateUtils.exitingMeasure(initData)
						}
					}
				});
		}
	}

	jnexport(StressMeasure, [stressIndication, "stressIndication"]);
})();
