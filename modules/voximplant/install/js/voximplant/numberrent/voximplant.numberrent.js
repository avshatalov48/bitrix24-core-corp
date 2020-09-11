;(function()
{
	BX.namespace("BX.Voximplant");

	var rentUrl = "/telephony/rent.php";

	BX.Voximplant.NumberRent = function(config)
	{
		if(!BX.type.isPlainObject(config))
		{
			config = {};
		}
		this.packetSize = parseInt(config.packetSize) || 1;

		this.slider = null;

		// event handlers
		this.onSliderMessageHandler = this.onSliderMessage.bind(this);
	};

	BX.Voximplant.NumberRent.create = function(config)
	{
		return new BX.Voximplant.NumberRent(config);
	};

	BX.Voximplant.NumberRent.prototype = {
		show: function()
		{
			var url = rentUrl;
			if (this.packetSize > 1)
			{
				url += "?PACKET_SIZE=" + this.packetSize;
			}
			BX.SidePanel.Instance.open(url, {
				allowChangeHistory:false,
				cacheable: false,
				events: {
					onOpen: this.onSliderOpen.bind(this),
					onClose: this.onSliderClose.bind(this),
					onDestroy: this.onSliderDestroy.bind(this),
				}
			});

			BX.addCustomEvent("SidePanel.Slider:onMessage", this.onSliderMessageHandler);
		},

		close: function()
		{
			if(this.slider)
			{
				this.slider.close();
			}
		},

		openConfigEditor: function(configId)
		{
			configId = parseInt(configId);
			if(configId > 0)
			{
				BX.SidePanel.Instance.open("/telephony/edit.php?ID=" + configId, {
					cacheable: false,
					allowChangeHistory: false
				});
			}
		},

		openDocumentsUploader: function()
		{
			BX.SidePanel.Instance.open("/telephony/documents.php", {
				cacheable: false,
				allowChangeHistory: false
			});
		},

		onSuccessRent: function (configId, numbers)
		{
			var content;
			var buttons = [];
			var popup;
			var numberList = Object.keys(numbers);

			var formattedNumbers = numberList.map(function(number)
			{
				return numbers[number]["PHONE_NUMBER_FORMATTED"];
			}).join(", ");

			if(numberList.length === 1 && numbers[numberList[0]]['VERIFICATION_STATUS'] === 'REQUIRED')
			{
				content = BX.message("VI_NUMBER_RENT_NUMBER_RESERVED").replace("#NUMBER#", formattedNumbers);
				buttons.push(
					new BX.PopupWindowButton({
						id: "upload",
						text: BX.message("VI_NUMBER_RENT_UPLOAD_DOCUMENTS"),
						events: {
							click: function()
							{
								popup.close();
								this.openDocumentsUploader();
							}.bind(this)
						}
					})
				);
			}
			else
			{
				if(numberList.length > 1)
				{
					content = BX.message("VI_NUMBER_RENT_NUMBERS_ATTACHED").replace("#NUMBERS#", formattedNumbers);
				}
				else
				{
					content = BX.message("VI_NUMBER_RENT_NUMBER_ATTACHED").replace("#NUMBER#", formattedNumbers);
				}

				buttons.push(
					new BX.PopupWindowButton({
						id: "configure",
						text: numberList.length > 1 ? BX.message("VI_NUMBER_RENT_CONFIGURE_NUMBERS") : BX.message("VI_NUMBER_RENT_CONFIGURE_NUMBER"),
						events: {
							click: function()
							{
								popup.close();
								this.openConfigEditor(configId);
							}.bind(this)
						}
					})
				);
			}

			buttons.push(new BX.PopupWindowButton({
				'id': 'close',
				'text': BX.message('VI_NUMBER_RENT_CLOSE'),
				'events': {
					'click': function(){
						popup.close();
					}
				}
			}));

			popup = new BX.PopupWindow("voximplant-rent-success", null, {
				closeIcon: true,
				closeByEsc: true,
				autoHide: false,
				titleBar: BX.message("VI_NUMBER_RENT_CONGRATULATIONS"),
				content: content,
				overlay: {
					color: 'gray',
					opacity: 30
				},
				buttons: buttons,
				events: {
					onPopupClose: function() {
						this.destroy();
					},
					onPopupDestroy: function() {
						popup = null;
					}
				}
			});
			popup.show();
		},

		getNumbersInSubscription: function(number)
		{
			return new Promise(function(resolve, reject)
			{
				BX.ajax.runAction("voximplant.subscription.getWithNumber", {
					data: {number: number}
				}).then(function(response)
				{
					var data = response.data;
					resolve(data);

				}).catch(function(response)
				{
					var error = response.errors[0];
					reject(error.message);
				})
			});
		},

		waitNumberDeletionConfirmation: function(numbers)
		{
			return new Promise(function(resolve)
			{
				var layout;
				if(numbers.length === 1)
				{
					layout = BX.message("VI_NUMBER_NUMBER_DELETE_CONFIRM").replace("#NUMBER#", numbers[0]);
				}
				else
				{
					layout = BX.create("div", {children: [
							BX.create("span", {
								text: BX.message("VI_NUMBER_NUMBER_RENTED_IN_BUNDLE").replace("#COUNT#", numbers.length)
							}),
							BX.create("ul", {
								children: numbers.map(function(number)
								{
									return BX.create("li", {
										text: number
									})
								})
							}),
							BX.create("span", {
								text: BX.message("VI_NUMBER_CONFIRM_BUNDLE_DISCONNECTION")
							})
						]});
				}

				BX.Voximplant.confirm(
					BX.message("VI_NUMBER_CONFIRM_ACTION"),
					layout
				).then(function(result)
				{
					if(result)
					{
						resolve();
					}
				})
			})
		},

		enqueueForDeletion: function(subscriptionId)
		{
			return new Promise(function(resolve, reject)
			{
				BX.ajax.runAction("voximplant.subscription.enqueueDisconnect", {
					data: {
						subscriptionId: subscriptionId
					}
				}).then(function(response)
				{
					resolve();
				}).catch(function(response)
				{
					var error = response.errors[0];
					reject(error.message);
				});
			});
		},

		deleteNumber: function(number)
		{
			return new Promise(function(resolve, reject)
			{
				BX.Voximplant.showLoader();

				var numbersInSubscriptions;
				var subscriptionId;
				this.getNumbersInSubscription(number).then(function(subscriptionData)
				{
					BX.Voximplant.hideLoader();
					subscriptionId = subscriptionData.subscriptionId;
					numbersInSubscriptions = subscriptionData.numbers;

					return this.waitNumberDeletionConfirmation(numbersInSubscriptions);

				}.bind(this)).then(function()
				{
					BX.Voximplant.showLoader();
					return this.enqueueForDeletion(subscriptionId)
				}.bind(this)).then(function()
				{
					BX.Voximplant.hideLoader();

					resolve()
				}.bind(this)).catch(function (errorMessage)
				{
					console.error(errorMessage);
					BX.Voximplant.hideLoader();
					if(errorMessage)
					{
						BX.Voximplant.alert(BX.message("VI_NUMBER_ERROR"), BX.message("VI_DELETE_NUMBER_ERROR"));
					}
					reject();
				});
			}.bind(this));

		},

		cancelNumberDeletion: function(number)
		{
			return new Promise(function(resolve, reject)
			{
				BX.Voximplant.showLoader();

				BX.ajax.runAction("voximplant.subscription.cancelDisconnect", {
					data: {
						number: number
					}
				}).then(function (response)
				{
					BX.Voximplant.hideLoader();
					resolve();
				}.bind(this)).catch(function (response)
				{
					var error = response.errors[0];
					BX.Voximplant.hideLoader();
					BX.Voximplant.alert(BX.message("VI_NUMBER_ERROR"), BX.message("VI_CANCEL_DELETE_NUMBER_ERROR"));
					reject();
				})
			}.bind(this));
		},

		onSliderOpen: function(e)
		{
			this.slider = e.slider;
		},

		onSliderMessage: function(e)
		{
			if(e.getEventId() === "BX.Voximplant.Rent::onSuccess")
			{
				var data = e.data;
				this.onSuccessRent(data.configId, data.numbers);
			}
		},

		onSliderClose: function(e)
		{
			BX.removeCustomEvent("SidePanel.Slider:onMessage", this.onSliderMessageHandler);
			this.slider.destroy();
		},

		onSliderDestroy: function(e)
		{
			this.slider = null;
		},
	}
})();