;(function ()
{
	BX.namespace('BX.Rest.Configuration.Install');
	if (!BX.Rest.Configuration.Install)
	{
		return;
	}

	/**
	 * Install.
	 *
	 */
	function Install()
	{
	}

	Install.prototype =
	{
		init: function (params)
		{
			this.id = params.id;
			this.signedParameters = params.signedParameters;
			this.next = '';
			this.section = [];
			this.progressDescriptionContainer = BX.findChildByClassName( BX(this.id), 'rest-configuration-info');
			this.clearAll = false;
			this.errors = [];
			var startBtn = BX.findChildByClassName( BX(this.id),'start_btn');
			BX.bind(
				startBtn,
				'click',
				BX.delegate(
					function()
					{
						var btnConfirm = new BX.UI.Button({
							color: BX.UI.Button.Color.PRIMARY,
							state: BX.UI.Button.State.DISABLED,
							text: BX.message('REST_CONFIGURATION_IMPORT_INSTALL_CONFIRM_POPUP_BTN_CONTINUE'),
							onclick: BX.delegate(
									function (btn)
									{
										this.clearAll = BX('CONFIGURATION_ACCEPT_CLEAR_ALL').checked;
										if (!this.clearAll)
										{
											return false;
										}
										btn.context.close();
										this.start();
									},
									this
							)
						});
						var message = BX.create(
							'div',
							{
								children: [
									BX.create(
										"p",
										{
											text: BX.message('REST_CONFIGURATION_IMPORT_INSTALL_CONFIRM_POPUP_TEXT'),
										}
									),
									BX.create(
										"INPUT",
										{
											attrs: {
												id: "CONFIGURATION_ACCEPT_CLEAR_ALL",
												type: "checkbox",
												name: 'ACCEPT_CLEAR_ALL',
												value: 'Y'
											},
											events: {
												change: function (event) {
													btnConfirm.setState(
														this.checked ? BX.UI.Button.State.ACTIVE : BX.UI.Button.State.DISABLED
													);
												}
											}
										}
									),
									BX.create(
										'label',
										{
											attrs: {
												for: "CONFIGURATION_ACCEPT_CLEAR_ALL"
											},
											text: BX.message('REST_CONFIGURATION_IMPORT_INSTALL_CONFIRM_POPUP_CHECKBOX_LABEL'),
										}
									)
								]
							}
						);

						BX.UI.Dialogs.MessageBox.show({
							message: message,
							modal: true,
							bindElement: startBtn,
							buttons: [
								btnConfirm,
								new BX.UI.Button({
									text: BX.message('REST_CONFIGURATION_IMPORT_INSTALL_CONFIRM_POPUP_BTN_CANCEL'),
									onclick: function(btn) {
										btn.context.close();
									}
								}),
							],
						});

					},
					this
				)
			);

		},

		setDescription: function (code)
		{
			code = 'REST_CONFIGURATION_IMPORT_INSTALL_STEP_'+code;
			var mess = BX.message[code]? BX.message(code): BX.message('REST_CONFIGURATION_IMPORT_INSTALL_STEP');
			BX.html(this.progressDescriptionContainer, mess);
		},

		finish: function ()
		{
			this.setDescription('FINISH');
			var barContainer = BX.findChildByClassName( BX(this.id),'rest-configuration-start-icon-main');
			var infoContainer = BX.findChildByClassName( BX(this.id),'rest-configuration-info');
			BX.removeClass(barContainer,' rest-configuration-start-icon-main-loading');

			var text = '';
			if(this.errors.length === 0)
			{
				text = BX.message("REST_CONFIGURATION_IMPORT_FINISH_DESCRIPTION");
				BX.addClass(barContainer,'rest-configuration-start-icon-main-success');
			}
			else
			{
				text = BX.message("REST_CONFIGURATION_IMPORT_FINISH_ERROR_DESCRIPTION");
				BX.addClass(barContainer,'rest-configuration-start-icon-main-error');
			}
			BX.cleanNode(this.progressDescriptionContainer);
			this.progressDescriptionContainer.appendChild(
				BX.create('p', {
					attrs: {
						className: ''
					},
					text: text
				})
			);

			if(this.errors.length !== 0)
			{
				this.progressDescriptionContainer.appendChild(
					BX.create('div', {
						attrs: {
							className: 'rest-configuration-links'
						},
						children: [
							BX.create('a', {
								attrs: {
									'data-slider-ignore-autobinding': 'true',
									href: ''
								},
								events: {
									click: BX.delegate(this.openPopupErrors, this)
								},
								text: BX.message("REST_CONFIGURATION_IMPORT_ERRORS_REPORT_BTN")
							})
						]
					})
				);
			}

			BX.insertAfter(
				BX.create('div', {
					attrs: {
						className: 'rest-configuration-action-block'
					},
					children:[
					]
				}),
				BX.findChildByClassName( BX(this.id),'rest-configuration-start-icon-main')
			);
			var self = this;
			this.sendAjax(
				'finish',
				{},
				function (response)
				{
					if(response.data.result === true)
					{
						if(self.errors.length > 0)
						{
							var errorsBlock = BX.findChildByClassName( BX(self.id),'rest-configuration-errors');
							for (var i = 0; i < self.errors.length; i++)
							{
								errorsBlock.appendChild(
									BX.create('p',
										{
											'text':self.errors[i]
										}
									)
								);
							}
						}
						else
						{
							BX(self.id).appendChild(
								BX.create('p', {
									attrs: {
										className: 'rest-configuration-import-finish rest-configuration-info'
									},
									html: BX.message('REST_CONFIGURATION_IMPORT_INSTALL_FINISH_TEXT')
								})
							);
						}

					}
				}
			);
		},

		addErrors: function (errors)
		{
			for (var i = 0; i < errors.length; i++)
			{
				this.errors.push(errors[i]);
			}
		},

		openPopupErrors: function ()
		{
			var errorText = '';
			this.errors.forEach(function(item) {
				errorText += item + '\r\n'
			});
			var errorTextArea = BX.create('textarea', {
				props: {
					className: 'rest-configuration-popup-textarea',
					placeholder: BX.message('REST_CONFIGURATION_IMPORT_ERRORS_POPUP_TEXT_PLACEHOLDER')
				},
				html: errorText
			});
			var restConfigWindowContent = BX.create('div', {
				children: [
					BX.create('div', {
						props: {
							className: 'rest-configuration-popup-textarea-title'
						},
						text: BX.message('REST_CONFIGURATION_IMPORT_ERRORS_POPUP_TEXT_LABEL')
					}),
					errorTextArea
				]
			});

			var restConfigWindow = BX.PopupWindowManager.create('rest-configuration-popup', null, {
				className: 'rest-configuration-popup',
				titleBar: BX.message('REST_CONFIGURATION_IMPORT_ERRORS_POPUP_TITLE'),
				content: restConfigWindowContent,
				contentBackground: 'transparent',
				contentPadding: 10,
				minWidth:250,
				maxWidth: 450,
				autoHide: true,
				closeIcon: true,
				animation: 'fading-slide',
				buttons: [
					new BX.UI.Button(
						{
							text: BX.message('REST_CONFIGURATION_IMPORT_ERRORS_POPUP_BTN_COPY'),
							color: BX.UI.Button.Color.LINK,
							events: {
								click: function () {
									errorTextArea.select();
									document.execCommand("copy");
								}
							}
						}
					)

				],
				onPopupClose: function () {
					this.destroy();
				},
			});
			restConfigWindow.show();

		},

		start: function ()
		{
			BX.addClass(BX.findChildByClassName( BX(this.id),'rest-configuration-start-icon-main'), 'rest-configuration-start-icon-main-loading');
			BX.style(BX.findChildByClassName( BX(this.id),'start-btn-block'), 'display', 'none');

			this.setDescription('START');
			BX.style(BX.findChildByClassName( BX(this.id),'start_btn_block'), 'display', 'none');
			var self = this;
			this.sendAjax(
				'start',
				{},
				function (response)
				{
					if(response.data.section.length > 0)
					{
						self.section = response.data.section;
						if(!!response.data.next && response.data.next === 'save')
						{
							self.save(0, 0);
						}
						else
						{
							self.clear(0, 0, 0);
						}
					}
				}
			);
		},

		save: function (section, step)
		{
			this.sendAjax(
				'save',
				{
					code: this.section[section],
					step: step,
					next: this.next
				},
				BX.delegate(
					function (response)
					{
						if(!!response.data)
						{
							this.next = response.data.next;
							step++;
							if(this.next === false)
							{
								section++;
								step = 0;
							}

							if(section >= this.section.length)
							{
								this.clear(0, 0, 0);
							}
							else
							{
								this.save(section, step);
							}
						}
						else
						{
							this.showFatalError();
						}
					},
					this
				)
			);
		},

		clear: function (section, step, next)
		{
			this.setDescription('CLEAR');
			this.sendAjax(
				'clear',
				{
					code: this.section[section],
					step: step,
					next: next
				},
				BX.delegate(
					function (response)
					{
						step++;
						next = response.data.next;
						if (next === false)
						{
							section++;
							step = 0;
							next = 0;
						}

						if (section < this.section.length)
						{
							this.clear(section, step, next);
						}
						else
						{
							this.import(0, 0);
						}
					},
					this
				)
			);
		},

		import: function (section, step)
		{
			this.sendAjax(
				'import',
				{
					code: this.section[section],
					step: step
				},
				BX.delegate(
					function (response)
					{
						step++;
						if(!response.data.errors)
						{
							this.setDescription(this.section[section]);
						}
						if(response.data.result === true)
						{
							section++;
							step = 0;
						}
						if(section < this.section.length)
						{
							this.import(section, step);
						}
						else
						{
							this.finish();
						}
					},
					this
				)
			);
		},

		showFatalError: function ()
		{
			var barContainer = BX.findChildByClassName( BX(this.id),'rest-configuration-start-icon-main');
			BX.removeClass(barContainer,'rest-configuration-start-icon-main-zip rest-configuration-start-icon-main-loading');
			BX.addClass(barContainer,'rest-configuration-start-icon-main-error');

			BX.cleanNode(this.progressDescriptionContainer);
			this.progressDescriptionContainer.appendChild(
				BX.create('div', {
					attrs: {
						className: 'rest-configuration-fatal-error-block'
					},
					children:[
					],
					'text': BX.message("REST_CONFIGURATION_IMPORT_INSTALL_FATAL_ERROR")
				})
			);
		},

		sendAjax: function (action, data, callback)
		{
			data.clear = this.clearAll;
			var self = this;
			BX.ajax.runComponentAction(
				'bitrix:rest.configuration.install',
				action,
				{
					mode: 'class',
					signedParameters: this.signedParameters,
					data: data
				}
			).then(
				function(response)
				{
					callback(response);
					if(!!response.data.errors)
					{
						self.addErrors(response.data.errors);
					}
					if(!!response.data['errorsNotice'])
					{
						console.log({
							errors: response.data['errorsNotice'],
							action: action,
							data: data,
							response: response
						});
					}
				}
			).catch(
				function(response)
				{
					this.showFatalError();
				}.bind(this)
			);
		}
	};

	BX.Rest.Configuration.Install =  new Install();

})(window);