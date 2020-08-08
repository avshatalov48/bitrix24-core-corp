;(function()
{
	BX.namespace("BX.Voximplant");

	var instances = {};

	BX.Voximplant.Rent = function (params)
	{
		this.id = params.id;
		this.publicFolder = params.publicFolder;
		this.selectPlaceholder = params.selectPlaceholder;
		this.numbersPlaceholder = params.numbersPlaceholder;
		this.verifiedAddressesPlaceholder = params.verifiedAddressesPlaceholder;
		this.location = params.location;
		this.canRent = params.canRent;
		this.iframe = params.iframe;

		this.currentBalance = params.currentBalance;

		this.rentPacketSize = params.rentPacketSize || 1;

		this.maximumNumbersToRent = params.maximumNumbersToRent || 0;

		this._data = {};
		this._countryId = '';
		this._categoryId = '';
		this._stateId = '';
		this._regionId = '';

		Object.defineProperty(this, "currentCountry", {
			get: function()
			{
				return this._data[this._countryId];
			}
		});
		Object.defineProperty(this, "currentCategory", {
			get: function()
			{
				return this._data[this._countryId]["CATEGORIES"][this._categoryId];
			}
		});
		Object.defineProperty(this, "currentState", {
			get: function()
			{
				return this._data[this._countryId]["CATEGORIES"][this._categoryId]["STATES"][this._stateId];
			}
		});
		Object.defineProperty(this, "currentRegion", {
			get: function()
			{
				if(this._data[this._countryId]["CATEGORIES"][this._categoryId]["HAS_STATES"])
				{
					return this._data[this._countryId]["CATEGORIES"][this._categoryId]["STATES"][this._stateId]["REGIONS"] ?
						this._data[this._countryId]["CATEGORIES"][this._categoryId]["STATES"][this._stateId]["REGIONS"][this._regionId]
						:
						undefined;
				}
				else
				{
					return this._data[this._countryId]["CATEGORIES"][this._categoryId]["REGIONS"] ?
						this._data[this._countryId]["CATEGORIES"][this._categoryId]["REGIONS"][this._regionId]
						:
						undefined;
				}
			}
		});

		Object.defineProperty(this, "isRentButtonVisible", {
			get: function()
			{
				if(this.currentRegion["PHONE_COUNT"] > 0)
				{
					if(this.currentRegion["IS_NEED_REGULATION_ADDRESS"])
					{
						return !!this.currentAddressVerification;
					}
					else
					{
						return true;
					}
				}
				else
				{
					return false;
				}
			}
		});

		this.countryTypes = {};
		this.countryStates = {};
		this.countryRegion = {};
		this.countryRegionNumbers = {};
		this.countryVerifiedAddresses = {};
		this.countryRegionNumberCount = 0;

		this.currentNumber = '';
		this.currentAddressVerification = null;
		this.phoneNumberInstallationPrice = 0;
		this.phoneNumberMonthPrice = 0;
		this.phoneNumberFullPrice = 0;
		this.phoneNumberCurrency = 'RUR';

		this.numberName = '';

		this.selectedNumbers = {};
		Object.defineProperty(this, "selectedCount", {
			get: function()
			{
				return Object.keys(this.selectedNumbers).length
			}
		});

		this.nodes = {
			rentButton: BX('vi_rent_options'),
			container: BX('vi_rent_options_div'),
		};

		this.container = params.container;
		this.loader = null;

		this.elements = {
			loaderOverlay: null,
			countryBox: null,
			countrySelect: null,

			stateBox: null,
			stateSelect: null,

			categoryBox: null,
			categorySelect: null,

			regionBox: null,
			regionLabel: null,
			regionSelect: null,

			priceContainer: null,
			messageContainer: null,
			numbersContainer: null,

			title: null,
			counterTitle: null,
			selectedCount: null,

			buttons: null
		};

		this.init();
	};

	BX.Voximplant.Rent.create = function(params)
	{
		var instance = new BX.Voximplant.Rent(params);
		instances[params.id] = instance;
		return instance;
	};

	BX.Voximplant.Rent.getInstance = function(id)
	{
		return instances[id];
	};

	BX.Voximplant.Rent.prototype.init = function ()
	{
		var layout = this.render();
		this.container.appendChild(layout);

		this.loader = new BX.Loader({target: this.elements.loaderOverlay});

		this.getCountries().then(function()
		{
			this.updateCountrySelect();
			this.setCurrentCountry(this.elements.countrySelect.value);

		}.bind(this));
	};

	BX.Voximplant.Rent.prototype.render = function()
	{
		return BX.createFragment([
			this.elements.loaderOverlay = BX.create("div", {
				props: {className: "voximplant-config-rent-overlay"}
			}),
			/*BX.create("div", {
				props: {className: "tel-set-item-select-label"},
				text: "Number caption"
			}),
			BX.create("div", {
				props: {className: "voximplant-control-row"},
				children: [
					BX.create("input", {
						attrs: {type: "text"},
						props: {className: "voximplant-control-input voximplant-control-full-length"},
						events: {
							change: function(e)
							{
								this.numberName = e.currentTarget.value;
							}.bind(this)
						}
					})
				]
			}),*/

			BX.create("div", {
				props: {className: "tel-set-select-block"},
				children: [
					this.elements.countryBox = BX.create("div", {
						props: {className: "tel-set-item-select-wrap"},
						children: [
							BX.create("div", {
								props: {className: "tel-set-item-select-label"},
								text: BX.message('VI_CONFIG_RENT_COUNTRY')
							}),
							this.elements.countrySelect = BX.create("select", {
								props: {className: "tel-set-item-select"},
								events: {
									bxchange: this.onCountrySelected.bind(this)
								},
							})
						]
					}),
					this.elements.categoryBox = BX.create("div", {
						props: {className: "tel-set-item-select-wrap"},
						style: {display: "none"},
						children: [
							BX.create("div", {
								props: {className: "tel-set-item-select-label"},
								text: BX.message('VI_CONFIG_RENT_PHONE_NUMBER')
							}),
							this.elements.categorySelect = BX.create("select", {
								props: {className: "tel-set-item-select"},
								events: {
									bxchange: this.onCategorySelected.bind(this)
								},
							})
						]
					}),
					this.elements.stateBox = BX.create("div", {
						props: {className: "tel-set-item-select-wrap"},
						style: {display: "none"},
						children: [
							BX.create("div", {
								props: {className: "tel-set-item-select-label"},
								text: BX.message('VI_CONFIG_RENT_STATE')
							}),
							this.elements.stateSelect = BX.create("select", {
								props: {className: "tel-set-item-select"},
								events: {
									bxchange: this.onStateSelected.bind(this)
								},
							})
						]
					}),
					this.elements.regionBox = BX.create("div", {
						props: {className: "tel-set-item-select-wrap"},
						style: {display: "none"},
						children: [
							this.elements.regionLabel = BX.create("div", {
								props: {className: "tel-set-item-select-label"},
								text: BX.message('VI_CONFIG_RENT_REGION') // currentCategory == 'TOLLFREE' ? BX.message('VI_CONFIG_RENT_CATEGORY') : BX.message('VI_CONFIG_RENT_REGION')
							}),
							this.elements.regionSelect = BX.create("select", {
								props: {className: "tel-set-item-select"},
								events: {
									bxchange: this.onRegionSelected.bind(this)
								},
							})
						]
					}),
				]
			}),
			BX.create("div", {
				children: [
					BX.create("div", {
						props: {className: "tel-set-list-nums"}, children: [
							this.elements.priceContainer = BX.create("div", {
								props: {className: "tel-set-amount-box"},
							}),
							this.elements.messageContainer = BX.create("div", {
								props: {className: "tel-message-container"}
							}),
							this.elements.numbersContainer = BX.create("div", {
								props: {className: "tel-set-list-nums-container"},
							}),
						]
					}),
				]
			}),
		])
	};

	BX.Voximplant.Rent.prototype.showLoader = function()
	{
		this.elements.loaderOverlay.classList.add('active');
		this.loader.show();
	};

	BX.Voximplant.Rent.prototype.hideLoader = function()
	{
		this.loader.hide();
		this.elements.loaderOverlay.classList.remove('active');
	};

	BX.Voximplant.Rent.prototype.showCategoryBox = function()
	{
		this.elements.categoryBox.style.removeProperty('display');
	};

	BX.Voximplant.Rent.prototype.showStateBox = function()
	{
		this.elements.stateBox.style.removeProperty('display');
	};

	BX.Voximplant.Rent.prototype.showRegionBox = function()
	{
		this.elements.regionBox.style.removeProperty('display');

		BX.adjust(this.elements.regionLabel, {
			text: this._categoryId == 'TOLLFREE' ? BX.message('VI_CONFIG_RENT_CATEGORY') : BX.message('VI_CONFIG_RENT_REGION')
		});
	};

	BX.Voximplant.Rent.prototype.hideCategoryBox = function()
	{
		this.elements.categoryBox.style.display = 'none';
	};

	BX.Voximplant.Rent.prototype.hideStateBox = function()
	{
		this.elements.stateBox.style.display = 'none';
	};

	BX.Voximplant.Rent.prototype.hideRegionBox = function()
	{
		this.elements.regionBox.style.display = 'none';
	};

	BX.Voximplant.Rent.prototype.getCountries = function()
	{
		return new Promise(function(resolve, reject)
		{
			if (this.blockAjax)
			{
				return resolve();
			}

			if (Object.keys(this._data).length > 0)
			{
				return resolve();
			}

			this.blockAjax = true;
			this.showLoader();
			BX.ajax.runComponentAction('bitrix:voximplant.config.rent', 'getCountries').then(function(result)
			{
				this.hideLoader();
				this.blockAjax = false;
				this._data = result.data;
				resolve();

			}.bind(this)).catch(function(error)
			{
				this.hideLoader();
				this.blockAjax = false;

				BX.Voximplant.alert(BX.message('VI_CONFIG_RENT_ERROR_TITLE'), BX.message('VI_CONFIG_RENT_AJAX_ERROR'));

				reject();
			}.bind(this));
		}.bind(this));
	};

	BX.Voximplant.Rent.prototype.getStates = function()
	{
		return new Promise(function(resolve, reject)
		{
			if (this.blockAjax)
				return resolve();

			this.blockAjax = true;
			this.showLoader();
			BX.ajax.runComponentAction('bitrix:voximplant.config.rent', 'getStates', {
				data: {
					country: this._countryId,
					category: this._categoryId,
				}
			}).then(function(result)
			{
				this.hideLoader();
				this.blockAjax = false;
				this._data[this._countryId]["CATEGORIES"][this._categoryId]["STATES"] = result.data;
				resolve();

			}.bind(this)).catch(function(error)
			{
				this.hideLoader();
				this.blockAjax = false;

				BX.Voximplant.alert(BX.message('VI_CONFIG_RENT_ERROR_TITLE'), BX.message('VI_CONFIG_RENT_AJAX_ERROR'));

				reject();
			}.bind(this));
		}.bind(this));
	};

	BX.Voximplant.Rent.prototype.getRegions = function()
	{
		return new Promise(function(resolve, reject)
		{
			if (this.blockAjax)
				return resolve();

			this.blockAjax = true;

			this.showLoader();
			BX.ajax.runComponentAction('bitrix:voximplant.config.rent', 'getRegions', {
				data: {
					country: this._countryId,
					category: this._categoryId,
					state: this._stateId,
					bundleSize: this.rentPacketSize > 1 ? this.rentPacketSize : 0
				}
			}).then(function(result)
			{
				this.hideLoader();
				this.blockAjax = false;

				if(this._stateId)
				{
					this.currentState["REGIONS"] = result.data;
				}
				else
				{
					this.currentCategory["REGIONS"] = result.data;
				}

				resolve(result.data);
			}.bind(this)).catch(function(error)
			{
				this.hideLoader();
				this.blockAjax = false;

				BX.Voximplant.alert(BX.message('VI_CONFIG_RENT_ERROR_TITLE'), BX.message('VI_CONFIG_RENT_AJAX_ERROR'));

				reject();
			}.bind(this));
		}.bind(this));
	};

	//BX.Voximplant.Rent.prototype.

	BX.Voximplant.Rent.prototype.clearSelect = function(selectElement)
	{
		for(var i = selectElement.options.length - 1 ; i >= 0 ; i--)
		{
			selectElement.options.remove(i);
		}
	};

	BX.Voximplant.Rent.prototype.updateCountrySelect = function()
	{
		this.clearSelect(this.elements.countrySelect);

		for (var countryCode in this._data)
		{
			var attrs = {'value': countryCode};
			if (this._countryId === countryCode)
			{
				attrs['selected'] = 'true';
			}

			this.elements.countrySelect.options.add(BX.create("option", {attrs: attrs, html: this._data[countryCode].NAME}))
		}
	};

	BX.Voximplant.Rent.prototype.updateCategorySelect = function()
	{
		var categories = this._data[this._countryId].CATEGORIES;

		this.clearSelect(this.elements.categorySelect);

		var sortedCategories = BX.util.objectSort(categories, 'TITLE', 'asc');

		for (var i in sortedCategories)
		{
			var category = sortedCategories[i];
			if (category['PHONE_TYPE'] === 'MOSCOW495' || category['PHONE_TYPE'] === 'TOLLFREE_CATEGORY2')
			{
				continue;
			}

			var attrs = {'value': category['PHONE_TYPE']};
			if (this._categoryId === category['PHONE_TYPE'])
			{
				attrs['selected'] = 'true';
			}

			this.elements.categorySelect.options.add(BX.create("option", {attrs: attrs, text: category['TITLE']}));
		}
	};

	BX.Voximplant.Rent.prototype.updateStateSelect = function()
	{
		this.clearSelect(this.elements.stateSelect);

		var states = this._data[this._countryId]['CATEGORIES'][this._categoryId]['STATES'];

		for (var stateCode in states)
		{
			var attrs = {'value': stateCode};
			if (this._stateId == stateCode)
			{
				attrs['selected'] = 'true';
			}

			this.elements.stateSelect.options.add(BX.create("option", {attrs: attrs, text: states[stateCode]['NAME']}));
		}
	};

	BX.Voximplant.Rent.prototype.updateRegionSelect = function()
	{
		var categoryFields = this._data[this._countryId]["CATEGORIES"][this._categoryId];
		var regions;

		this.clearSelect(this.elements.regionSelect);

		if(categoryFields["HAS_STATES"])
		{
			regions = this._data[this._countryId]["CATEGORIES"][this._categoryId]["STATES"][this._stateId]["REGIONS"];
		}
		else
		{
			regions = this._data[this._countryId]["CATEGORIES"][this._categoryId]["REGIONS"];
		}

		var orderForRu = [1, 15, 2];
		var sortedRegions = Object.values(regions).sort(function(a, b)
		{
			if(this._countryId === 'RU' && orderForRu.indexOf(a.REGION_ID) != -1 && orderForRu.indexOf(b.REGION_ID) != -1)
			{
				return orderForRu.indexOf(a.REGION_ID) - orderForRu.indexOf(b.REGION_ID);
			}
			else if (this._countryId === 'RU' && orderForRu.indexOf(a.REGION_ID) != -1)
			{
				return -1;
			}
			else if (this._countryId === 'RU' && orderForRu.indexOf(b.REGION_ID) != -1)
			{
				return 1;
			}
			else
			{
				return a['REGION_NAME'].localeCompare(b['REGION_NAME']);
			}
		}.bind(this));

		for (var i = 0; i < sortedRegions.length; i++)
		{
			this.elements.regionSelect.options.add(
				BX.create("option", {
					attrs: {'value': sortedRegions[i].REGION_ID},
					html: sortedRegions[i].REGION_NAME
				})
			);
		}
	};

	BX.Voximplant.Rent.prototype._onUploadDocumentsButtonClick = function(e)
	{
		BX.SidePanel.Instance.open(this.getUploadUrl(), {cacheable: false, allowChangeHistory: false});
		e.preventDefault();
		e.stopPropagation();
	};

	BX.Voximplant.Rent.prototype._onRentButtonClick = function (e)
	{
		if (!this.canRent)
		{
			BX.UI.InfoHelper.show('limit_contact_center_telephony_rent_numbers');
			return;
		}

		if(this.currentCountry['CAN_LIST_PHONES'])
		{
			if(this.selectedCount == 0)
			{
				BX.Voximplant.alert(
					BX.message('VI_CONFIG_RENT_ERROR_TITLE'),
					BX.message('VI_CONFIG_RENT_AJAX_NUMBER')
				);
				return;
			}
			else if (this.rentPacketSize > 1 && this.selectedCount != this.rentPacketSize)
			{
				BX.Voximplant.alert(
					BX.message('VI_CONFIG_RENT_ERROR_TITLE'),
					BX.message('VI_CONFIG_RENT_AJAX_WRONG_NUMBER_COUNT').replace('#COUNT#', this.rentPacketSize.toString())
				);
				return;
			}
		}

		if (this.currentRegion['IS_NEED_REGULATION_ADDRESS'] && !this.currentAddressVerification)
		{
			BX.Voximplant.alert(
				BX.message('VI_CONFIG_RENT_ERROR_TITLE'),
				BX.message('VI_CONFIG_SELECT_ADDRESS_ERROR')
			);
			return;
		}

		this.showLoader();
		var requestData = {
			country: this._countryId,
			category: this._categoryId,
			region: this._regionId,
		};

		if(this.currentCategory['HAS_STATES'])
		{
			requestData['state'] = this._stateId;
		}

		if(this.currentCountry['CAN_LIST_PHONES'])
		{
			requestData['numbers'] = Object.keys(this.selectedNumbers);
		}
		else
		{
			requestData['count'] = this.rentPacketSize;
		}

		if(this.rentPacketSize > 1)
		{
			requestData['singleSubscription'] = 'Y';
		}

		if(this.currentAddressVerification)
		{
			requestData['verificationId'] = this.currentAddressVerification;
		}

		if(this.numberName)
		{
			requestData['name'] = this.numberName;
		}

		BX.ajax.runComponentAction('bitrix:voximplant.config.rent', 'attachNumbers', {
			data: requestData
		}).then(function(response)
		{
			this.hideLoader();

			if(BX.SidePanel.Instance.isOpen())
			{
				BX.SidePanel.Instance.postMessage(
					window,
					"BX.Voximplant.Rent::onSuccess",
					{
						configId: response.data.configId,
						numbers: response.data.numbers
					}
				);
				BX.SidePanel.Instance.close();
			}


		}.bind(this)).catch(function (response)
		{
			this.hideLoader();

			var errorMessage;

			if(response.errors.length > 0)
			{
				errorMessage = response.errors.map(function(a){return a.message}).join('; ');
			}
			else
			{
				errorMessage = BX.message('VI_CONFIG_RENT_AJAX_RENT_UNKNOWN_ERROR');
			}

			BX.Voximplant.alert(BX.message('VI_CONFIG_RENT_ERROR_TITLE'), errorMessage);

		}.bind(this));

		e.preventDefault();
		e.stopPropagation();
	};

	BX.Voximplant.Rent.prototype._onCancelButtonClick = function()
	{
		BX.SidePanel.Instance.close();
	};

	BX.Voximplant.Rent.prototype.getNumbers = function (getMore)
	{
		getMore = (getMore === true);
		return new Promise(function(resolve, reject)
		{
			var region;
			if(this._stateId)
			{
				region = this._data[this._countryId]["CATEGORIES"][this._categoryId]["STATES"][this._stateId]["REGIONS"][this._regionId];
			}
			else
			{
				region = this._data[this._countryId]["CATEGORIES"][this._categoryId]["REGIONS"][this._regionId];
			}

			if (!region["NUMBERS"])
			{
				region["NUMBERS"] = {};
			}
			if(region["PHONE_COUNT"] === 0)
			{
				return false;
			}

			var fetchedNumbersCount = Object.keys(region["NUMBERS"]).length;
			var requestData = {
				country: this._countryId,
				category: this._categoryId,
				region: this._regionId
			};

			if(getMore && fetchedNumbersCount > 0)
			{
				requestData.offset = fetchedNumbersCount;
			}

			this.showLoader();
			BX.ajax.runComponentAction('bitrix:voximplant.config.rent', 'getPhoneNumbers', {data: requestData}).then(function(result)
			{
				this.hideLoader();

				for(var phoneId in result.data)
				{
					if(result.data.hasOwnProperty(phoneId))
					{
						this.currentRegion["NUMBERS"][phoneId] = result.data[phoneId];
					}
				}

				if(this.selectedCount === 0  && !getMore && Object.keys(region["NUMBERS"]).length >= this.rentPacketSize && this.rentPacketSize > 1)
				{
					for (var i = 0; i < this.rentPacketSize; i++)
					{
						this.selectedNumbers[Object.keys(region["NUMBERS"])[i]] = true;
					}
				}

				resolve();

			}.bind(this)).catch(function(error)
			{
				this.hideLoader();
				BX.Voximplant.alert(BX.message('VI_CONFIG_RENT_ERROR_TITLE'), BX.message('VI_CONFIG_RENT_AJAX_ERROR'));
				reject();
			}.bind(this));
		}.bind(this));
	};

	BX.Voximplant.Rent.prototype.getAvailableVerifications = function()
	{
		return new Promise(function(resolve, reject)
		{
			if (this.blockAjax)
			{
				return resolve();
			}

			var requestData = {
				country: this._countryId,
				category: this._categoryId,
				region: this.currentRegion.REGION_CODE
			};

			this.blockAjax = true;
			this.showLoader();
			BX.ajax.runComponentAction('bitrix:voximplant.config.rent', 'getAvailableVerifications', {data: requestData}).then(function(result)
			{
				this.hideLoader();
				this.blockAjax = false;

				this.currentRegion['ADDRESS_VERIFICATION'] = result.data;
				resolve(result.data);
			}.bind(this)).catch(function(error)
			{
				this.hideLoader();
				this.blockAjax = false;
				BX.Voximplant.alert(BX.message('VI_CONFIG_RENT_ERROR_TITLE'), BX.message('VI_CONFIG_RENT_AJAX_ERROR'));
				reject();
			}.bind(this));
		}.bind(this));
	};

	BX.Voximplant.Rent.prototype.setCurrentCountry = function(country)
	{
		this.hideStateBox();
		this.hideRegionBox();
		this.clearInterface();

		this._countryId = country;
		this._categoryId = '';
		this._stateId = '';
		this._regionId = '';
		this.selectedNumbers = {};

		this.currentNumber = "";

		this.updateCategorySelect();
		this.showCategoryBox();
		this.setCurrentCategory(this.elements.categorySelect.value);
	};

	BX.Voximplant.Rent.prototype.setCurrentCategory = function(category)
	{
		this.hideStateBox();
		this.hideRegionBox();
		this.clearInterface();

		this._stateId = '';
		this._regionId = '';
		this._categoryId = category;

		this.selectedNumbers = {};

		if(this.currentCategory.HAS_STATES)
		{
			this.getStates().then(function()
			{
				this.updateStateSelect();
				this.showStateBox();
				this.setCurrentState(this.elements.stateSelect.value);
			}.bind(this));
		}
		else
		{
			this.getRegions().then(function(regions)
			{
				if(regions.length === 0)
				{
					BX.adjust(this.elements.messageContainer, {children: [
							this.renderMessage(BX.message("VI_CONFIG_RENT_NO_PHONES"))
						]});
					this.hideButtons();
				}
				else
				{
					this.updateRegionSelect();
					this.showRegionBox();
					this.setCurrentRegion(this.elements.regionSelect.value);
				}
			}.bind(this))
		}
	};

	BX.Voximplant.Rent.prototype.setCurrentState = function(state)
	{
		this.hideRegionBox();
		this.clearInterface();

		this._stateId = state;
		this._regionId = '';
		this.selectedNumbers = {};

		this.getRegions().then(function(regions)
		{
			if(regions.length === 0)
			{
				BX.adjust(this.elements.messageContainer, {children: [
					this.renderMessage(BX.message("VI_CONFIG_RENT_NO_PHONES"))
				]});
				this.hideButtons();
			}
			else
			{
				this.updateRegionSelect();
				this.showRegionBox();
				this.setCurrentRegion(this.elements.regionSelect.value);
			}
		}.bind(this))
	};

	BX.Voximplant.Rent.prototype.setCurrentRegion = function(region)
	{
		this.clearInterface();

		this._regionId = region;

		var currentRegionParameters = this.currentRegion;
		this.countryRegionNumberCount = currentRegionParameters.PHONE_COUNT;

		this.currentNumber = '';
		this.selectedNumbers = {};

		if(this.countryRegionNumberCount == 0)
		{
			BX.adjust(this.elements.messageContainer, {children: [
				this.renderMessage(BX.message("VI_CONFIG_RENT_NO_PHONES"))
			]});

			this.hideButtons();

			//this.showNumberRequestForm();
		}
		else if(this.currentRegion.REGULATION_ADDRESS_TYPE != '')
		{
			this.getAvailableVerifications().then(function(result)
			{
				this.showPhonePrice();
				if(result.VERIFICATIONS_AVAILABLE > 0)
				{
					this.showVerifiedAddress();
					this.showButtons();
				}
				else
				{
					this.showVerificationRequired();
					this.hideButtons();
				}
			}.bind(this));
		}
		else if(this.currentRegion.REQUIRED_VERIFICATION != '' && this.rentPacketSize > 1)
		{
			this.showDocumentsRequiredForMassRent();
		}
		else if(this.currentCountry.CAN_LIST_PHONES)
		{
			this.getNumbers().then(function()
			{
				this.showPhonePrice();
				this.showNumbers();
				this.showButtons();
			}.bind(this))
		}
		else
		{
			this.showPhonePrice();
			BX.adjust(this.elements.messageContainer, {children: [
				this.renderMessage(BX.message("VI_CONFIG_RENT_WITHOUT_CHOICE"))
			]});
			this.showButtons();
		}
	};

	BX.Voximplant.Rent.prototype.onCountrySelected = function(e)
	{
		this.setCurrentCountry(e.currentTarget.value);
	};

	BX.Voximplant.Rent.prototype.onCategorySelected = function(e)
	{
		this.setCurrentCategory(e.currentTarget.value);
	};


	BX.Voximplant.Rent.prototype.onStateSelected = function(e)
	{
		this.setCurrentState(e.currentTarget.value);
	};

	BX.Voximplant.Rent.prototype.onRegionSelected = function(e)
	{
		this.setCurrentRegion(e.currentTarget.value);

	};

	BX.Voximplant.Rent.prototype.onMoreNumbersClick = function(e)
	{
		this.getNumbers(true).then(this.showNumbers.bind(this));
	};

	BX.Voximplant.Rent.prototype.getCurrentRegionParameters = function ()
	{
		var category = this._data[this._countryId]["CATEGORIES"][this._categoryId];

		if(category.HAS_STATES)
		{
			return this._data[this._countryId]["CATEGORIES"][this._categoryId]["STATES"][this._stateId]["REGIONS"][this._regionId];
		}
		else
		{
			return this._data[this._countryId]["CATEGORIES"][this._categoryId]["REGIONS"][this._regionId];
		}
	};

	BX.Voximplant.Rent.prototype.clearInterface = function ()
	{
		BX.cleanNode(this.elements.priceContainer);
		BX.cleanNode(this.elements.messageContainer);
		BX.cleanNode(this.elements.numbersContainer);
	};

	BX.Voximplant.Rent.prototype.showPhonePrice = function ()
	{
		BX.cleanNode(this.elements.priceContainer);

		if(!this._regionId)
		{
			return;
		}

		var category = this._data[this._countryId]["CATEGORIES"][this._categoryId];
		var region;

		if(this._stateId)
		{
			region = this._data[this._countryId]["CATEGORIES"][this._categoryId]["STATES"][this._stateId]["REGIONS"][this._regionId];
		}
		else
		{
			region = this._data[this._countryId]["CATEGORIES"][this._categoryId]["REGIONS"][this._regionId];
		}

		if(this._data[this._countryId]["CAN_LIST_PHONES"] && !region["NUMBERS"])
		{
			return;
		}

		var currency = category["CURRENCY"].toLocaleLowerCase();

		var rentPrice = 0;
		var installationPrice = 0;
		var selectedCount = (this.selectedCount || 1);

		if(this._data[this._countryId]["CAN_LIST_PHONES"])
		{
			rentPrice = parseFloat(region["MONTH_PRICE"]) * selectedCount;
			installationPrice = parseFloat(region["INSTALLATION_PRICE"]) * selectedCount;
		}
		else
		{
			rentPrice = parseFloat(region["MONTH_PRICE"]) || 0;
			installationPrice = parseFloat(region["INSTALLATION_PRICE"]) || 0;
		}

		// 4 digits after comma, removing trailing zeroes
		rentPrice = parseFloat(rentPrice.toFixed(4));
		installationPrice = parseFloat(installationPrice.toFixed(4));

		this.elements.priceContainer.appendChild(BX.create("div", {
			props: {className: "tel-set-frame-title"},
			text: BX.message("VI_CONFIG_RENT_MONTH_PRICE")
		}));

		var priceElement = BX.create('div', {
			props: {className: "tel-set-amount-inner"}
		});

		this.elements.priceContainer.appendChild(priceElement);

		var rentPriceElement = BX.create("div", {
			props: {className: "tel-set-amount-content"},
			children: [
				BX.create('div', {
					props: {className: "tel-set-amount-main tel-set-amount " + "tel-set-amount-" + currency},
					children: [
						BX.create("div", {
							props: {className: "tel-set-amount-number"},
							text: rentPrice.toString(10)
						}),
						BX.create("div", {
							props: {className: "tel-set-amount-text"},
							text: BX.message("VI_CONFIG_RENT_MONTHLY_PAYMENT_HINT")
						})
					]
				}),
			]
		});

		priceElement.appendChild(rentPriceElement);

		if(installationPrice > 0)
		{
			var installationPriceElement = BX.create("div", {
				props: {className: "tel-set-amount-content"},
				children: [
					BX.create('div', {
						props: {className: "tel-set-amount-subtitle"},
						text: BX.message("VI_CONFIG_RENT_INSTALLATION_PRICE")
					}),
					BX.create('div', {
						props: {className: "tel-set-amount-main tel-set-amount-value-md " + "tel-set-amount-" + currency},
						children: [
							BX.create("div", {
								props: {className: "tel-set-amount-number"},
								text: installationPrice.toString(10)
							}),
							BX.create("div", {
								props: {className: "tel-set-amount-text"},
								text: BX.message("VI_CONFIG_RENT_INSTALLATION_PAYMENT_HINT")
							})
						]
					})
				]
			});

			priceElement.appendChild(installationPriceElement);
		}

		if((rentPrice + installationPrice) > this.currentBalance)
		{
			this.elements.priceContainer.appendChild(BX.create("div", {
				props: {className: "tel-set-amount-warning-box"},
				children: [
					BX.create("div", {
						props: {className: "tel-set-amount-warning-text"},
						text: BX.type.isNotEmptyString(this.currentRegion["REQUIRED_VERIFICATION"]) ? BX.message("VI_CONFIG_RENT_NO_MONEY_TO_RESERVE") : BX.message("VI_CONFIG_RENT_NO_MONEY_TO_RENT")
					}),
					BX.create("span", {
						props: {className: "tel-set-amount-warning-link"},
						text: BX.message("VI_CONFIG_RENT_TOP_UP_BALANCE"),
						events: {
							click: function()
							{
								BX.Voximplant.openBilling();
							}
						}
					})
				]
			}));
		}
	};

	BX.Voximplant.Rent.prototype.showNumbers = function()
	{
		var phoneList = [];
		var numbers = this.currentRegion["NUMBERS"];
		var totalCount = this.currentRegion["PHONE_COUNT"];

		BX.cleanNode(this.elements.numbersContainer);

		for (var phoneId in numbers)
		{
			if (this._countryId === 'RU' && (this._categoryId === 'TOLLFREE' || this._categoryId === 'TOLLFREE804'))
			{
				phoneName = numbers[phoneId]['PHONE_NUMBER_LOCAL'];
			}
			else
			{
				phoneName = numbers[phoneId]['PHONE_NUMBER_INTERNATIONAL'];
			}

			phoneList.push(
				BX.create("span", {
					props: {className: "tel-set-list-item"},
					children: [
						BX.create("input", {
							props: {className: "tel-set-list-item-checkbox"},
							attrs: {
								id: 'phone' + phoneId,
								name: 'tel-set-list-item',
								type: "checkbox",
								value: phoneId,
								checked: this.selectedNumbers[phoneId] === true

							},
							dataset: {
								countryCode: numbers[phoneId].COUNTRY_CODE,
								regionId: numbers[phoneId].REGION_ID,
								phoneNumber: numbers[phoneId].PHONE_NUMBER
							},
							events: {
								bxchange: this.onNumberSelected.bind(this)
							}
						}),
						BX.create("label", {
							props: {className: "tel-set-list-item-num"},
							attrs: {'for': 'phone' + phoneId},
							text: phoneName
						})
					]
				})
			);
		}

		this.elements.title = BX.create("div", {
			props: {className: "tel-set-frame-title"},
			children: [
				BX.create("div", {
					props: {className: "tel-set-frame-title-name"},
					text: BX.message('VI_CONFIG_RENT_LIST_PHONES'),
				}),

			]
		});

		this.elements.counterTitle = BX.create("div", {
			props: {className: "tel-set-frame-counter"},
			children: [
				BX.create("div", {
					props: {className: "tel-set-frame-counter-name"},
					text: BX.message("VI_CONFIG_SELECTED")
				})
			]
		});
		if(this.rentPacketSize == 1)
		{
			this.elements.selectedCount = BX.create("div", {
				props: {className: "tel-set-frame-number"},
				text: Object.keys(this.selectedNumbers).length
			})
		}
		else
		{
			this.elements.selectedCount = BX.create("div", {
				props: {className: "tel-set-frame-number"},
				text: BX.message("VI_CONFIG_RENT_SELECTED_COUNT").replace("#SELECTED#", Object.keys(this.selectedNumbers).length).replace("#TOTAL#", this.rentPacketSize)
			})
		}

		this.elements.counterTitle.appendChild(this.elements.selectedCount);

		if(this.rentPacketSize > 1 || Object.keys(this.selectedNumbers).length > 0)
		{
			this.elements.title.appendChild(this.elements.counterTitle);
		}

		this.elements.numbersContainer.appendChild(this.elements.title);

		var numbersContent = BX.create("div", {
			props: {className: "tel-set-list-nums-content"},
			children: [
				BX.create("div", {
					props: {className: "tel-set-list-nums-wrap"},
					children: phoneList
				}),
			]
		});

		if(totalCount > Object.keys(numbers).length)
		{
			numbersContent.appendChild(BX.create("div", {
				props: {className: "tel-set-list-nums-control-box"},
				children: [
					BX.create("div", {
						props: {className: "ui-btn ui-btn-sm ui-btn-light-border"},
						text: BX.message("VI_CONFIG_RENT_MORE_NUMBERS"),
						events: {
							click: this.onMoreNumbersClick.bind(this)
						}
					})
				]
			}));
		}

		this.elements.numbersContainer.appendChild(numbersContent);

		if(this.currentRegion['REQUIRED_VERIFICATION'])
		{
			this.showDocumentsRequired();
		}
	};

	BX.Voximplant.Rent.prototype.showDocumentsRequired = function()
	{
		this.elements.numbersContainer.appendChild(BX.create("div", {
			props: {className: "tel-set-list-nums-warning"},
			children: [
				BX.create("p", {
					children: [
						BX.create("strong", {
							text: BX.message("VI_CONFIG_RENT_TEXT_REQUIRE_VERIFICATION_P1")
						})
					]
				}),
				BX.create("p", {text: BX.message("VI_CONFIG_RENT_TEXT_REQUIRE_VERIFICATION_P2")}),
				BX.create("a", {
					attrs: {
						href: this.publicFolder + "documents.php",
						target: "_blank"
					},
					text: BX.message("VI_CONFIG_RENT_TEXT_REQUIRE_VERIFICATION_P3"),
					events: {
						click: this._onUploadDocumentsButtonClick.bind(this)
					}
				})
			]
		}));
	};

	BX.Voximplant.Rent.prototype.showDocumentsRequiredForMassRent = function()
	{
		this.elements.numbersContainer.appendChild(BX.create("div", {
			props: {className: "tel-set-list-nums-warning"},
			children: [
				BX.create("p", {
					children: [
						BX.create("strong", {
							text: BX.message("VI_CONFIG_RENT_TEXT_REQUIRE_VERIFICATION_MASS_P1")
						})
					]
				}),
				BX.create("p", {text: BX.message("VI_CONFIG_RENT_TEXT_REQUIRE_VERIFICATION_MASS_P2")}),
				BX.create("a", {
					attrs: {
						href: this.publicFolder + "documents.php",
						target: "_blank"
					},
					text: BX.message("VI_CONFIG_RENT_TEXT_REQUIRE_VERIFICATION_P3"),
					events: {
						click: this._onUploadDocumentsButtonClick.bind(this)
					}
				})
			]
		}));
	};

	BX.Voximplant.Rent.prototype.showVerificationRequired = function()
	{
		BX.adjust(this.elements.messageContainer, {
			children: [
				this.renderMessage(BX.message("VI_CONFIG_ADDRESS_VERIFICATION_REQUIRED_2")),

				BX.create("button", {
					props: {className: "ui-btn ui-btn-primary"},
					text: BX.message("VI_CONFIG_UPLOAD_ADDRESS_VERIFICATION"),
					events: {
						click: this._onUploadDocumentsButtonClick.bind(this)
					}
				})
			]
		});
	};

	BX.Voximplant.Rent.prototype.showVerifiedAddress = function(e)
	{
		var adrblocks = this.currentRegion.ADDRESS_VERIFICATION.VERIFIED_ADDRESS.map(function(address)
		{
			return BX.create("div", {
				props: {className: "tel-set-frame-radio-item"},
				children: [
					BX.create("input", {
						props: {className: "tel-set-frame-radio-input"},
						attrs: {
							type: "radio",
							id: "address-" + address["ID"],
							value: address["ID"],
							name: "ADDRESS_ID"
						},
						events: {
							change: function(e)
							{
								this.currentAddressVerification = e.currentTarget.value;
								this.showButtons();
							}.bind(this)
						}
					}),
					BX.create("label", {
						props: {className: "tel-set-frame-radio-name"},
						attrs: {
							for: "address-" + address["ID"]
						},
						text: this.formatVerifiedAddress(address)
					})
				]
			});
		}, this);

		var frameAddress = BX.create("div", {
			props: {className: "tel-set-frame-address"},
			children: [
				BX.create("div", {
					props: {className: "tel-set-frame-title"},
					text: BX.message("VI_CONFIG_SELECT_ADDRESS")
				}),
				BX.create("div", {
					props: {className: "tel-set-list-nums-content"},
					children: [
						BX.create("div", {
							props: {className: "tel-set-list-nums-wrap"},
							children: adrblocks
						}),
						BX.create("div", {
							props: {className: "tel-set-list-nums-control-box"},
							children: [
								BX.create("div", {
									props: {className: "ui-btn ui-btn-sm ui-btn-light-border"},
									text: BX.message("VI_CONFIG_RENT_UPLOAD_DOCUMENTS"),
									events: {
										click: this._onUploadDocumentsButtonClick.bind(this)
									}
								})
							]
						})
					]
				})
			]
		});

		this.elements.numbersContainer.appendChild(frameAddress);
	};

	BX.Voximplant.Rent.prototype.renderMessage = function (message)
	{
		return BX.create("div", {
			props: {className: "ui-alert ui-alert-success"},
			children: [
				BX.create("span", {
					props: {className: "ui-alert-message"},
					text: message
				})
			]
		})
	};

	BX.Voximplant.Rent.prototype.renderButtons = function(e)
	{
		var result = BX.createFragment([]);

		if(this.isRentButtonVisible)
		{
			result.appendChild(BX.create("button", {
				props: {className: "ui-btn ui-btn-success"},
				text: BX.type.isNotEmptyString(this.currentRegion['REQUIRED_VERIFICATION']) ? BX.message("VI_CONFIG_RESERVE_BTN") : BX.message("VI_CONFIG_RENT_BTN"),
				events: {
					click: this._onRentButtonClick.bind(this)
				}
			}));
		}

		result.appendChild(BX.create("button", {
			props: {className: "ui-btn ui-btn-link"},
			text: BX.message("VI_CONFIG_CANCEL_BTN"),
			events: {
				click: this._onCancelButtonClick.bind(this)
			}
		}));
		return result;
	};

	BX.Voximplant.Rent.prototype.showButtons = function()
	{
		if(this.elements.buttons)
		{
			BX.cleanNode(this.elements.buttons);
		}
		else
		{
			this.elements.buttons = BX.create("div", {
				props: {className: "voximplant-button-panel"}
			});
			document.body.appendChild(this.elements.buttons);
		}

		this.elements.buttons.appendChild(this.renderButtons());
	};

	BX.Voximplant.Rent.prototype.hideButtons = function()
	{
		BX.cleanNode(this.elements.buttons);
	};

	BX.Voximplant.Rent.prototype.onNumberSelected = function (e)
	{
		var checkbox = e.currentTarget;

		if(checkbox.checked)
		{
			if (this.rentPacketSize > 1 && this.selectedCount == this.rentPacketSize)
			{
				checkbox.checked = false;
				e.stopPropagation();
				e.preventDefault();
			}
			else if (this.maximumNumbersToRent > 0 && this.selectedCount == this.maximumNumbersToRent)
			{
				checkbox.checked = false;
				e.stopPropagation();
				e.preventDefault();

				BX.UI.InfoHelper.show('limit_contact_center_telephony_rent_numbers')
			}
			else
			{
				this.selectedNumbers[checkbox.dataset.phoneNumber] = true;
			}
		}
		else
		{
			delete this.selectedNumbers[checkbox.dataset.phoneNumber];
		}

		if(this.rentPacketSize == 1)
		{
			BX.adjust(this.elements.selectedCount, {
				text: Object.keys(this.selectedNumbers).length
			});
		}
		else
		{
			BX.adjust(this.elements.selectedCount, {
				text: BX.message("VI_CONFIG_RENT_SELECTED_COUNT").replace("#SELECTED#", Object.keys(this.selectedNumbers).length).replace("#TOTAL#", this.rentPacketSize)
			});
		}

		if(this.rentPacketSize == 1 && Object.keys(this.selectedNumbers).length === 0)
		{
			this.elements.title.removeChild(this.elements.counterTitle);
		}
		else
		{
			this.elements.title.appendChild(this.elements.counterTitle);
		}

		this.showPhonePrice();
		this.showButtons();
	};

	BX.Voximplant.Rent.prototype.getUploadUrl = function ()
	{
		var parameters = {
			'SHOW_UPLOAD_IFRAME': 'Y',
			'UPLOAD_COUNTRY_CODE': this._countryId,
			'UPLOAD_ADDRESS_TYPE': this.currentRegion.REGULATION_ADDRESS_TYPE,
			'UPLOAD_PHONE_CATEGORY': this._categoryId,
			'UPLOAD_REGION_CODE': this.currentRegion.REGION_CODE,
			'UPLOAD_REGION_ID': this.currentRegion.REGION_ID,
			'IFRAME': this.iframe === true ? 'Y' : 'N'
		};

		return this.publicFolder + 'documents.php?' + BX.util.buildQueryString(parameters);
	};

	BX.Voximplant.Rent.prototype.formatVerifiedAddress = function (address)
	{
		var result = '';
		var field;
		var addressFields = ['ZIP_CODE', 'COUNTRY', 'CITY', 'STREET', 'BUILDING_NUMBER'];

		if (address.BUILDING_LETTER)
			address.BUILDING_NUMBER += '-' + address.BUILDING_LETTER;

		var first = true;
		addressFields.forEach(function (field)
		{
			if (address[field])
			{
				result += (first ? '' : ', ') + address[field];
				first = false;
			}
		});

		return result;
	};
})();