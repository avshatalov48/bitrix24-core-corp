(function()
{
	var configCamera = 'bx-crm-visit-default-camera';
	var AJAX_URL = '/bitrix/components/bitrix/crm.activity.visit/ajax.php?site_id=' + BX.message('SITE_ID');
	var COMMUNICATIONS_AJAX_URL = '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='+BX.message('SITE_ID')+'&sessid='+BX.bitrix_sessid();

	var crmSelectorLoaded = false;
	var consentGiven = false;
	var recognizeConsentGiven = false;

	var callbacks = {
		onVisitCreated: BX.DoNothing
	};

	BX.CrmActivityVisit = function(config)
	{
		if(!BX.type.isPlainObject(config))
			config = {};

		this.buttons = {
			createLead: null,
			createContact: null,
			selectEntity: null,
			addDeal: null,
			addInvoice: null
		};

		this.owner = {
			type: null,
			id: null
		};

		this.createdDeals = [];
		this.createdInvoices = [];

		this.id = config.id || 'crm-activity-visit-' + Math.round(Math.random() * 100000);
		this.ajaxUrl = config.ajaxUrl || AJAX_URL;
		this.recorder = null;
		this.mainNode = null;
		this.popup = null;

		this.hasFaceId = config.hasFaceId || false; //todo
		this.hasConsent = (config.HAS_CONSENT === 'Y') || consentGiven;
		this.hasRecognizeConsent = (config.HAS_RECOGNIZE_CONSENT === 'Y') || recognizeConsentGiven;
		this.faceIdInstalled = config.FACEID_INSTALLED === 'Y';
		this.faceIdEnabled = config.FACEID_ENABLED === 'Y';
		this.hasPhoto = false;
		this.faceId = 0;

		this.communicationSearch = null;
		this.faceSearch = null;

		this.recordLength = 0; //milliseconds
		this.timestamp = (new Date()).getTime();
		this.timerInterval = null;

		this.externalRequests = {};
		this._externalEventHandler = this._onExternalEvent.bind(this);
		this._selectEntityHandler = this._onSelectEntity.bind(this);

		this.createTimestamp = 0;
		this.entityType = config.OWNER_TYPE || '';
		this.entityId = config.OWNER_ID || 0;

		this.failed = false;

		this.vkProfile = '';
		this.vkProfileChanged = false;

		this.init();
	};

	BX.CrmActivityVisit.create = function(config)
	{
		return new BX.CrmActivityVisit(config);
	};

	BX.CrmActivityVisit.prototype.init = function()
	{
		BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
	};

	BX.CrmActivityVisit.prototype.getId = function()
	{
		return this.id;
	};

	BX.CrmActivityVisit.prototype.setId = function(id)
	{
		this.id = id;
	};

	BX.CrmActivityVisit.prototype.getMainNode = function()
	{
		return this.mainNode;
	};

	BX.CrmActivityVisit.prototype.setMainNode = function(mainNode)
	{
		this.mainNode = mainNode;
	};

	BX.CrmActivityVisit.prototype.getPopup = function()
	{
		return this.popup;
	};

	BX.CrmActivityVisit.prototype.setPopup = function(popup)
	{
		this.popup = popup;
	};

	BX.CrmActivityVisit.prototype.getNode = function(name, scope)
	{
		if (!scope)
			scope = this.getMainNode();

		return scope ? scope.querySelector('[data-role="'+name+'"]') : null;
	};

	BX.CrmActivityVisit.prototype.getNodeValue = function(name, scope)
	{
		var node = this.getNode(name, scope);

		return (node ? node.value : null);
	};

	BX.CrmActivityVisit.prototype.showEdit = function()
	{
		if (BX.getClass('BX.Crm.Restriction.Bitrix24') && BX.Crm.Restriction.Bitrix24.isRestricted('visit'))
		{
			return BX.Crm.Restriction.Bitrix24.getHandler('visit').call();
		}
		var self = this;
		var params = {
			ajax_action: 'EDIT'
		};

		if(this.entityType && this.entityId)
		{
			params.entityType = this.entityType;
			params.entityId = this.entityId;
		}

		self._checkConsent(function ()
		{
			self._checkRecognizeConsent(function (){
				self._createAjaxPopup(params, function()
				{
					self.createTimestamp = self.getNodeValue('field-create-timestamp');
					self.owner.type = self.getNode('field-owner-entity-type');
					self.owner.id =self.getNode('field-owner-entity-id');

					if(self.owner.type.value && self.owner.id.value)
					{
						self.entityType = self.owner.type.value;
						self.entityId = self.owner.id.value;
						if(self.owner.type.value !== 'LEAD')
						{
							var linksContainer = self.getNode('entity-links');
							BX.removeClass(linksContainer, 'crm-activity-visit-hidden');
						}
					}

					var dealId = parseInt(self.getNode('field-owner-entity-deal').value);
					if(dealId > 0)
					{
						self.createdDeals.push(dealId);
					}

					self.buttons.createContact = self.getNode('create-contact-button');
					self.buttons.createLead = self.getNode('create-lead-button');
					self.buttons.selectEntity = self.getNode('select-owner-button');
					self.buttons.addDeal = self.getNode('add-deal-button');
					self.buttons.addInvoice = self.getNode('add-invoice-button');

					if(self.buttons.createContact)
						self.buttons.createContact.addEventListener('click', self._onCreateButtonClick.bind(self));

					if(self.buttons.createLead)
						self.buttons.createLead.addEventListener('click', self._onCreateButtonClick.bind(self));

					if(self.buttons.selectEntity)
						self.buttons.selectEntity.addEventListener('click', self._onSelectorButtonClick.bind(self));

					if(self.buttons.addDeal)
						self.buttons.addDeal.addEventListener('click', self._onAddButtonClick.bind(self));

					if(self.buttons.addInvoice)
						self.buttons.addInvoice.addEventListener('click', self._onAddButtonClick.bind(self));

					var recorderNode = self.getNode('activity-recorder');

					var finishButton = self.getNode('button-finish');
					BX.bind(finishButton, 'click', self._onFinishButtonClick.bind(self));

					if(BX.CrmRecorder.isSupported())
					{
						self.recorder = new BX.CrmRecorder({
							element: recorderNode
						});

						BX.addCustomEvent(self.recorder, 'deviceFailure', self._onRecorderDeviceFailure.bind(self));
						BX.addCustomEvent(self.recorder, 'deviceReady', self._onRecorderDeviceReady.bind(self));

						self.recorder.start();
						self.timerInterval = setInterval(function()
						{
							var oldTimestamp = self.timestamp;
							var newTimestamp = (new Date()).getTime();
							var difference = newTimestamp - oldTimestamp;
							self.recordLength = self.recordLength + difference;
							self.timestamp = newTimestamp;
							self._updateTimer();
						}, 1000);
					}
					else
					{
						BX.addClass(self.getNode('record-timer'), 'crm-activity-visit-hidden');
						BX.removeClass(self.getNode('recorder-error'), 'crm-activity-visit-hidden');
						self.failed = true;
					}

					var faceidNode = self.getNode('faceid-container');
					if(faceidNode)
					{
						self.faceSearch = new FaceSearch(faceidNode, {
							visitView: self,
							onSelect: self._onFaceSelected.bind(self),
							onReset: self._onFaceReset.bind(self),
							onSocialProfileSelected: self._onFaceSocialProfileSelected.bind(self)
						});
						self.mainNode.style.minWidth = '989px';
						self.hasFaceId = true;

						var profileNode = self.getNode('crm-card-vk-profile');
						if(profileNode)
						{
							self.vkProfile = profileNode.value;
							if(BX.type.isNotEmptyString(self.vkProfile) && self.faceSearch)
							{
								self.faceSearch.setVkProfile(self.vkProfile);
							}
						}
					}
					setTimeout(function(){self.getPopup().adjustPosition();}, 150);
				});
			});
		});
	};

	BX.CrmActivityVisit.prototype.saveActivity = function(next)
	{
		var self = this;
		var finishButton = self.getNode('button-finish');
		var finishLoader = self.getNode('loader-finish');

		clearInterval(this.timerInterval);
		BX.addClass(finishButton, 'crm-activity-visit-hidden');
		BX.removeClass(finishLoader, 'crm-activity-visit-hidden');

		if(this.recorder)
		{
			this.recorder.stop(function(record)
			{
				var formData = new FormData();
				formData.append('RECORD', record);
				formData.append('RECORD_LENGTH', Math.floor(self.recordLength / 1000));
				formData.append('CREATED_DEALS[]', self.createdDeals);
				formData.append('CREATED_INVOICES[]', self.createdInvoices);
				formData.append('HAS_PHOTO', (self.hasPhoto ? 'Y' : 'N'));
				formData.append('OWNER_ENTITY_TYPE', self.entityType);
				formData.append('OWNER_ENTITY_ID', self.entityId);
				formData.append('CREATE_TIMESTAMP', self.createTimestamp);

				if(self.faceSearch)
				{
					formData.append('SAVE_PHOTO', (self.faceSearch.savePhoto ? 'Y' : 'N'));
					if(self.faceSearch.savePhoto)
					{
						formData.append('IMAGE', self.faceSearch.getImageBlob());
					}
				}

				if(self.vkProfileChanged)
				{
					formData.append('VK_PROFILE', self.vkProfile);
				}

				formData.append('sessid', BX.bitrix_sessid());
				formData.append('ajax_action', 'SAVE');

				BX.ajax({
					method: 'POST',
					dataType: 'json',
					url: self.ajaxUrl,
					data: formData,
					preparePost: false,
					onsuccess: function(response)
					{
						next();
					},
					onprogress: function(p)
					{
						
					}
				});
			});
		}
		else
		{
			next();
		}
	};

	BX.CrmActivityVisit.prototype.dispose = function()
	{
		if(this.timerInterval)
			clearInterval(this.timerInterval);

		BX.removeCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);

		if(typeof obCrm !== "undefined" && obCrm.visitCrmSelector)
		{
			obCrm.visitCrmSelector.RemoveOnSaveListener(this._selectEntityHandler);
		}
	};

	BX.CrmActivityVisit.prototype._checkConsent = function(next)
	{
		if (this.hasConsent)
		{
			next();
			return;
		}

		var content = '<p>' + BX.message('CRM_ACTIVITY_VISIT_CONSENT_BODY_1') + '</p>' +
					  '<p>' + BX.message('CRM_ACTIVITY_VISIT_CONSENT_BODY_2') + '</p>';

		var popup = new BX.PopupWindow('crm_visit_consent_popup' + (new Date()).getTime(), null, {
			titleBar: BX.message('CRM_ACTIVITY_VISIT_CONSENT_TITLE'),
			content: content,
			closeIcon: true,
			closeByEsc: true,
			events: {
				onPopupClose: function ()
				{
					popup.destroy();
				}
			},
			buttons: [
				new BX.PopupWindowButton({
					text: BX.message('CRM_ACTIVITY_VISIT_CONSENT_AGREED'),
					className: "popup-window-button-accept",
					events: {
						click: function()
						{
							consentGiven = true;
							BX.userOptions.save('crm.activity.visit', 'consent', 'timestamp',  (new Date()).getTime());
							popup.close();
							next();
						}
					}
				}),
				new BX.PopupWindowButtonLink({
					text: BX.message('CRM_ACTIVITY_VISIT_CONSENT_CLOSE'),
					events: {
						click: function()
						{
							popup.close();
						}
					}
				})
			]
		});
		popup.show();
	};

	BX.CrmActivityVisit.prototype._checkRecognizeConsent = function(next)
	{
		var self = this;
		if(!this.faceIdInstalled || !this.faceIdEnabled)
		{
			next();
			return;
		}

		if(this.hasRecognizeConsent)
		{
			next();
			return;
		}

		var content = BX.create('div', {style: {'max-width': '595px'}, html: BX.message('CRM_ACTIVITY_VISIT_FACEID_AGREEMENT')});
		var popup = new BX.PopupWindow('crm_visit_recognize_consent_popup' + (new Date()).getTime(), null, {
			titleBar: BX.message('CRM_ACTIVITY_VISIT_CONSENT_TITLE'),
			content: content,
			closeIcon: true,
			closeByEsc: true,
			events: {
				onPopupClose: function ()
				{
					popup.destroy();
				}
			},
			buttons: [
				new BX.PopupWindowButton({
					text: BX.message('CRM_ACTIVITY_VISIT_CONSENT_AGREED'),
					className: "popup-window-button-accept",
					events: {
						click: function()
						{
							recognizeConsentGiven = true;
							self.hasRecognizeConsent = true;
							popup.close();
							self._saveRecognizeConsent(next);
						}
					}
				}),
				new BX.PopupWindowButtonLink({
					text: BX.message('CRM_ACTIVITY_VISIT_CONSENT_CLOSE'),
					events: {
						click: function()
						{
							recognizeConsentGiven = false;
							self.hasRecognizeConsent = false;
							popup.close();
							next();
						}
					}
				})
			]
		});
		popup.show();
	};

	BX.CrmActivityVisit.prototype._saveRecognizeConsent = function(next)
	{
		var params = {
			sessid: BX.bitrix_sessid(),
			'ajax_action': 'SAVE_RECOGNIZE_CONSENT'
		};
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxUrl,
			data: params,
			onsuccess: function (data)
			{
				next();
			}
		})
	};

	BX.CrmActivityVisit.prototype._createAjaxPopup = function(params, next)
	{
		params['sessid'] = BX.bitrix_sessid();
		params['HAS_RECOGNIZE_CONSENT'] = (this.hasRecognizeConsent ? 'Y' : 'N');

		var self = this;
		var wrapper = BX.create('div', {style:	{'min-width': (self.hasFaceId ? '989px' : '550px'), 'height': '650px'}});
		var popup = new BX.PopupWindow(self.getId(), null, {
			content: wrapper,
			closeIcon: false,
			noAllPaddings: true,
			zIndex: -100,
			closeByEsc: false,
			draggable: {restrict: false},
			overlay: {backgroundColor: 'black', opacity: 30},
			events: {
				onPopupClose: function()
				{
					self._onPopupClose();
					self.popup.destroy();
				}
			}
		});
		popup.show();

		BX.ajax({
			method: 'POST',
			dataType: 'html',
			url: this.ajaxUrl,
			data: params,
			onsuccess: function (HTML)
			{
				wrapper.innerHTML = HTML;
				self.setMainNode(wrapper);
				self.setPopup(popup);
				next();
			}
		});
	};

	BX.CrmActivityVisit.prototype.setOwner = function(entity)
	{
		var self = this;
		this.owner.type.value = entity.entityType;
		this.owner.id.value = entity.entityId;

		this.entityType = entity.entityType;
		this.entityId = entity.entityId;

		this._reloadOwnerCard(entity, function()
		{
			var selectorContainer = self.getNode('owner-selector');
			var linksContainer = self.getNode('entity-links');
			BX.addClass(selectorContainer, 'crm-activity-visit-hidden');
			if(entity.entityType == 'LEAD')
			{
				BX.addClass(linksContainer, 'crm-activity-visit-hidden');
			}
			else
			{
				BX.removeClass(linksContainer, 'crm-activity-visit-hidden');
			}
			self.getPopup().adjustPosition();
			if(self.faceSearch)
			{
				self.faceSearch.__renderSavePhoto();
			}
			var profileNode = self.getNode('crm-card-vk-profile');
			if(profileNode)
			{
				self.vkProfile = profileNode.value;
				if(BX.type.isNotEmptyString(self.vkProfile) && self.faceSearch)
				{
					self.faceSearch.setVkProfile(self.vkProfile);
				}
			}
		});
	};

	BX.CrmActivityVisit.prototype._onCreateButtonClick = function(e)
	{
		var target = e.target;
		var url = target.dataset.url;
		var context = target.dataset.context;

		this.externalRequests[context] = {
			type: 'create',
			context: context,
			window: window.open(url)
		};
	};

	BX.CrmActivityVisit.prototype._onExternalEvent = function(params)
	{
		var entity;

		params = BX.type.isPlainObject(params) ? params : {};
		params.key = params.key || '';

		var value = params.value || {};
		value.entityTypeName = value.entityTypeName || '';
		value.context = value.context || '';
		value.isCanceled = BX.type.isBoolean(value.isCanceled) ? value.isCanceled : false;

		if(value.isCanceled)
			return;

		if(params.key === "onCrmEntityCreate" && this.externalRequests[value.context])
		{
			if(this.externalRequests[value.context])
			{
				if (this.externalRequests[value.context]['type'] == 'create')
				{
					entity = {
						entityType: value.entityTypeName,
						entityId: value.entityId
					};
					this.setOwner(entity);
				}
				else if (this.externalRequests[value.context]['type'] == 'add')
				{
					if (value.entityTypeName == 'DEAL')
						this.createdDeals.push(parseInt(value.entityId));
					else if (value.entityTypeName == 'INVOICE')
						this.createdInvoices.push(parseInt(value.entityId));

					// reload crm card
					entity = {
						entityType: this.entityType,
						entityId: this.entityId
					};
					this.setOwner(entity);
				}

				if(this.externalRequests[value.context]['window'])
					this.externalRequests[value.context]['window'].close();

				delete this.externalRequests[value.context];
			}
		}
	};

	BX.CrmActivityVisit.prototype._onAddButtonClick = function(e)
	{
		var target = e.target;
		var url = target.dataset.url;
		var role = target.dataset.role;

		if(role === 'add-deal-button')
		{
			if(this.owner.type.value === 'CONTACT')
			{
				url = BX.util.add_url_param(url, { contact_id: this.owner.id.value });
			}
			else if(this.owner.type.value === 'COMPANY')
			{
				url = BX.util.add_url_param(url, { company_id: this.owner.id.value });
			}
		}
		else if(role === 'add-invoice-button')
		{
			if(this.owner.type.value === 'CONTACT')
			{
				url = BX.util.add_url_param(url, { contact: this.owner.id.value });
			}
			else if(this.owner.type.value === 'COMPANY')
			{
				url = BX.util.add_url_param(url, { company: this.owner.id.value });
			}
		}

		var context = target.dataset.context;
		this.externalRequests[context] = {
			type: 'add',
			context: context,
			window: window.open(url)
		};

		window.open(url);
	};

	BX.CrmActivityVisit.prototype._onFinishButtonClick = function (e)
	{
		var self = this;
		if(this.failed)
		{
			self.getPopup().close();
		}
		else
		{
			self.saveActivity(function()
			{
				self.getPopup().close();
				BX.CrmActivityVisit.runCallback('onVisitCreated', {});
			});
		}
	};

	BX.CrmActivityVisit.prototype._onPopupClose = function()
	{
		if(this.communicationSearch)
			this.communicationSearch.dispose();

		if(this.recorder)
			this.recorder.dispose();

		if(this.faceSearch)
			this.faceSearch.dispose();

		this.dispose();
	};

	BX.CrmActivityVisit.prototype._reloadOwnerCard = function(entity, next)
	{
		var self = this;
		var requestData = {
			'sessid': BX.bitrix_sessid(),
			'ajax_action': 'GET_CARD',
			'ENTITY_TYPE': entity.entityType,
			'ENTITY_ID': entity.entityId
		};
		BX.ajax({
			url: self.ajaxUrl,
			method: 'POST',
			data: requestData,
			onsuccess: function(response)
			{
				var cardContainer = self.getNode('activity-owner-card');
				cardContainer.innerHTML = response;
				next();
			}
		});
	};
	
	BX.CrmActivityVisit.prototype._updateTimer = function()
	{
		var lengthElement = this.getNode('record-length');
		var minutes = Math.floor(this.recordLength / 1000 / 60).toString();
		var seconds = Math.floor(this.recordLength / 1000 % 60).toString();

		if(minutes.length < 2)
			minutes = String.prototype.concat('0', minutes);

		if(seconds.length < 2)
			seconds = String.prototype.concat('0', seconds);

		lengthElement.innerText = minutes + ':' + seconds;
	};

	BX.CrmActivityVisit.prototype._onSelectorButtonClick = function()
	{
		this._openCrmSelector();
	};


	BX.CrmActivityVisit.prototype._openCrmSelector = function()
	{
		var self = this;
		if(!crmSelectorLoaded)
		{
			var requestData = {
				'sessid': BX.bitrix_sessid(),
				'ajax_action': 'LOAD_SELECTOR'
			};

			BX.ajax({
				method: 'POST',
				url: self.ajaxUrl,
				data: requestData,
				onsuccess: function()
				{
					crmSelectorLoaded = true;
					setTimeout(self._openCrmSelector.bind(self), 50);
				}
			})
		}

		if(typeof obCrm !== "undefined" && obCrm.visitCrmSelector)
		{
			obCrm.visitCrmSelector.Open();
			obCrm.visitCrmSelector.AddOnSaveListener(this._selectEntityHandler);
		}
	};

	BX.CrmActivityVisit.prototype._onSelectEntity = function(params)
	{
		var entity = {
			entityType: '',
			entityId: 0
		};
		var found = false;
		
		if(params.lead && params.lead.hasOwnProperty('0') && BX.type.isNotEmptyString(params.lead['0'].id))
		{
			entity.entityType = 'LEAD';
			entity.entityId = params.lead['0'].id.substr(2);
			found = true;
		}
		else if(params.contact && params.contact.hasOwnProperty('0') && BX.type.isNotEmptyString(params.contact['0'].id))
		{
			entity.entityType = 'CONTACT';
			entity.entityId = params.contact['0'].id.substr(2);
			found = true;
		}
		else if(params.company && params.company.hasOwnProperty('0') && BX.type.isNotEmptyString(params.company['0'].id))
		{
			entity.entityType = 'COMPANY';
			entity.entityId = params.company['0'].id.substr(3);
			found = true;
		}

		if(found)
		{
			this.setOwner(entity);
		}
	};

	BX.CrmActivityVisit.prototype._onFaceSelected = function(params)
	{
		this.hasPhoto = true;
		this.faceId = params.faceId;

		if(params.entityType != '' && params.entityId > 0)
		{
			this.setOwner({
				entityType: params.entityType,
				entityId: params.entityId
			});
		}
	};

	BX.CrmActivityVisit.prototype._onFaceReset = function(params)
	{
		this.hasPhoto = false;
	};

	BX.CrmActivityVisit.prototype._onFaceSocialProfileSelected = function(profile)
	{
		this.vkProfile = profile;
		this.vkProfileChanged = true;
	};

	BX.CrmActivityVisit.prototype._onRecorderDeviceReady = function()
	{
		if (this.faceSearch)
			this.faceSearch.start();
	};

	BX.CrmActivityVisit.prototype._onRecorderDeviceFailure = function(error)
	{
		var timerNode = this.getNode('record-timer');
		var errorNode = this.getNode('recorder-error');

		BX.addClass(timerNode, 'crm-activity-visit-hidden');
		BX.removeClass(errorNode, 'crm-activity-visit-hidden');

		errorNode.innerHTML = BX.message('CRM_ACTIVITY_VISIT_ERROR_MIC_FAILURE') + '<br>' + error;
		this.failed = true;
	};


	BX.CrmActivityVisit.setCallback = function(eventName, callback)
	{
		if(callbacks.hasOwnProperty(eventName) && BX.type.isFunction(callback))
		{
			callbacks[eventName] = callback;
		}
	};

	BX.CrmActivityVisit.runCallback = function(eventName, event)
	{
		if(callbacks.hasOwnProperty(eventName) && BX.type.isFunction(callbacks[eventName]))
		{
			callbacks[eventName](event);
		}
	};

	var CommunicationSearch = function(node, config)
	{
		var self = this;
		this.id = 'crm-act-visit-' + (new Date()).getTime().toString();
		this.node = node;
		this.communicationType = BX.CrmCommunicationType.undefined;
		this.ajaxUrl = config.ajaxUrl || COMMUNICATIONS_AJAX_URL;
		this.entityType = null;
		this.entityId = null;
		this.entityTitle = null;

		this.callBacks = {
			onSelect: BX.type.isFunction(config.onSelect) ? config.onSelect : nop
		};

		this.setEntity({
			entityType: config.entityType || '',
			entityId: config.entityId || '',
			entityTitle: config.entityTitle || ''
		});

		if(typeof(BX.CrmCommunicationSearch.messages) === 'undefined')
		{
			BX.CrmCommunicationSearch.messages =
			{
				SearchTab: BX.message('CRM_ACTIVITY_PLANNER_COMMUNICATION_SEARCH_TAB'),
				NoData: BX.message('CRM_ACTIVITY_PLANNER_COMMUNICATION_SEARCH_NO_DATA')
			}
		}

		var selectNode = this.getNode('select-owner');
		var inputNode = this.getNode('input-owner');

		BX.style(inputNode, 'display', 'none');

		BX.bind(selectNode, 'click', function(e) {
			self.openDialog();
			return BX.PreventDefault(e);
		});

		this._communicationSearch = BX.CrmCommunicationSearch.create(this.id, {
			entityType : this.entityType,
			entityId: this.entityId,
			serviceUrl: this.ajaxUrl,
			communicationType:  this.communicationType,
			selectCallback: BX.delegate(this.selectCommunication, this),
			enableSearch: true,
			enableDataLoading: true,
			dialogAutoHide: true
		});

		this._communicationSearchController = null;
	};

	CommunicationSearch.prototype.getNode = function(name, scope)
	{
		if (!scope)
			scope = this.node;

		return scope ? scope.querySelector('[data-role="'+name+'"]') : null;
	};

	CommunicationSearch.prototype.setEntity = function(entity)
	{
		var selectNode = this.getNode('select-owner');
		var titleNode = this.getNode('owner-title');

		this.entityId = entity.entityId || 0;
		this.entityType = entity.entityType || '';
		this.entityTitle = entity.entityTitle || '';

		titleNode.innerText = this.entityTitle;

		if(this.entityId > 0)
			selectNode.innerText = BX.message('CRM_ACTIVITY_VISIT_OWNER_CHANGE');
		else
			selectNode.innerText = BX.message('CRM_ACTIVITY_VISIT_OWNER_SELECT');
	};

	CommunicationSearch.prototype.openDialog = function()
	{
		var inputNode = this.getNode('input-owner');
		var selectNode = this.getNode('select-owner');
		var titleNode = this.getNode('owner-title');
		BX.style(selectNode, 'display', 'none');
		BX.style(inputNode, 'display', 'inline-block');
		BX.style(titleNode, 'display', 'none');

		this._communicationSearchController = BX.CrmCommunicationSearchController.create(this._communicationSearch, inputNode);
		this._communicationSearchController.start();
		this._communicationSearch.openDialog(this.node, BX.delegate(this.onCloseDialog, this));

		BX.defer(BX.focus)(inputNode);
	};

	CommunicationSearch.prototype.onCloseDialog = function()
	{
		var inputNode = this.getNode('input-owner');
		var selectNode = this.getNode('select-owner');
		var titleNode = this.getNode('owner-title');
		BX.style(selectNode, 'display', 'inline-block');
		BX.style(inputNode, 'display', 'none');
		BX.style(titleNode, 'display', 'inline-block');

		if(this._communicationSearchController)
		{
			this._communicationSearchController.stop();
			this._communicationSearchController = null;
		}
		inputNode.value = '';
	};

	CommunicationSearch.prototype.selectCommunication = function(result)
	{
		var entity = result.getSettings();
		this.callBacks.onSelect(entity);
		this.setEntity(entity);
		this._communicationSearch.closeDialog();
	};

	CommunicationSearch.prototype.dispose = function()
	{
		this._communicationSearch.closeDialog();
	};

	var FaceSearch = function(node, config)
	{
		if(!BX.type.isPlainObject(config))
			config = {};

		this.ajaxUrl = config.ajaxUrl || AJAX_URL;
		this.node = node;
		this.visitView = config.visitView || null;
		this.mediaStream = null;
		this.defaulCamera = this.__getDefaultCamera();
		this.state = 'video';
		this.cameraList = [];
		this.savePhoto = false;

		this.imageBlob = null;

		this.elements = {
			loader: this.getNode('faceid-button-picture-loader'),
			social: this.getNode('faceid-social')
		};

		this.buttons = {
			picture: this.getNode('faceid-button-picture'),
			settings: this.getNode('faceid-button-settings'),
			// seachSocial: this.getNode('faceid-button-search-social'),
			savePhoto: this.getNode('faceid-button-save-photo')
		};

		this.callbacks = {
			onSelect: BX.type.isFunction(config.onSelect) ? config.onSelect : nop,
			onReset: BX.type.isFunction(config.onReset) ? config.onReset : nop,
			onSocialProfileSelected: BX.type.isFunction(config.onSocialProfileSelected) ? config.onSocialProfileSelected : nop
		};

		this.settingsMenu = null;
		this.socialSelector = null;

		this.init();
		this.__bindEvents();
	};

	FaceSearch.prototype.init = function()
	{
		this.buttons.picture.innerText = BX.message('CRM_ACTIVITY_VISIT_OWNER_TAKE_PICTURE');
		BX.hide(this.buttons.settings);
	};

	FaceSearch.prototype.getNode = function(name, scope)
	{
		if (!scope)
			scope = this.node;

		return scope ? scope.querySelector('[data-role="'+name+'"]') : null;
	};

	FaceSearch.prototype.getImageBlob = function()
	{
		return this.imageBlob;
	};

	FaceSearch.prototype.setImageBlob = function(imageBlob)
	{
		this.imageBlob = imageBlob;
	};

	FaceSearch.prototype.start = function()
	{
		this.__getMediaStream();
	};

	FaceSearch.prototype.changeCamera = function(cameraId)
	{
		this.defaulCamera = cameraId;
		this.__setDefaultCamera(cameraId);
		if(this.mediaStream)
			stopMediaStream(this.mediaStream);

		this.mediaStream = null;
		this.__getMediaStream();
	};

	FaceSearch.prototype.__bindEvents = function()
	{
		this.buttons.picture.addEventListener('click', this.__onSearchButtonClick.bind(this));
		this.buttons.settings.addEventListener('click', this.__onSettingsButtonClick.bind(this));
		// this.buttons.seachSocial.addEventListener('click', this.__onSearchSocialButtonClick.bind(this));
		this.buttons.savePhoto.addEventListener('click', this.__onSavePhotoButtonClick.bind(this));
	};

	FaceSearch.prototype.__getMediaStream = function()
	{
		var self = this;
		navigator.mediaDevices.getUserMedia(this.__getConstraints()).then(function(stream)
		{
			self.mediaStream = stream;
			var videoNode = self.getNode('faceid-video');
			videoNode.volume = 0;
			videoNode.srcObject = self.mediaStream;
			videoNode.play();
			if(self.cameraList.length === 0)
			{
				self.__getCameraList();
			}
			else
			{
				BX.show(self.buttons.settings);
			}
		}).catch(function(e)
		{
			console.log('Could not get access to user camera: ', e);
		})
	};

	FaceSearch.prototype.__getConstraints = function()
	{
		var result = {
			audio: false,
			video: {}
		};

		if(this.defaulCamera != '')
		{
			if(BX.browser.IsChrome())
			{
				result.video.mandatory = {sourceId: this.defaulCamera}
			}
			else
			{
				result.video.deviceId = {exact: this.defaulCamera}
			}
		}
		return result;
	};

	FaceSearch.prototype.__getCameraList = function()
	{
		var self = this;
		navigator.mediaDevices.enumerateDevices().then(function(deviceList)
		{
			deviceList.forEach(function(deviceInfo)
			{
				if(deviceInfo.kind !== 'videoinput')
					return;

				if(deviceInfo.label = '')
					deviceInfo.label = BX.message('CRM_ACTIVITY_VISIT_DEFAULT_CAMERA');

				self.cameraList.push(deviceInfo);
			});
			if(self.cameraList.length > 0)
			{
				BX.show(self.buttons.settings);
			}
		});
	};

	FaceSearch.prototype.__getDefaultCamera = function()
	{
		return localStorage.getItem(configCamera) || '';
	};

	FaceSearch.prototype.__setDefaultCamera = function(cameraId)
	{
		return localStorage.setItem(configCamera, cameraId);
	};

	FaceSearch.prototype.__onSearchButtonClick = function()
	{
		var self = this;
		var pictureContainer = self.getNode('faceid-picture-container');
		var picture = self.getNode('faceid-picture');
		var videoContainer = self.getNode('faceid-video-container');

		if(self.state == 'video')
		{
			self.createPicture(function(imageBlob)
			{
				self.__setState('picture');
				self.setImageBlob(imageBlob);
				picture.src = URL.createObjectURL(imageBlob);

				stopMediaStream(self.mediaStream);
				self.mediaStream = null;
				
				BX.addClass(videoContainer, 'crm-activity-visit-hidden');
				BX.removeClass(pictureContainer, 'crm-activity-visit-hidden');
				BX.removeClass(self.elements.social, 'crm-activity-visit-block-disable');
				BX.hide(self.buttons.settings);

				self.__showLoader();
				self.searchFace(imageBlob, function(response)
				{
					self.__hideLoader();
					if(response.ERRORS.length > 0)
					{
						console.log('Error received: ', response.ERRORS[0]);
						return;
					}

					if(response.SUCCESS === true)
					{
						self.callbacks.onSelect({
							entityType: response.DATA.ENTITY_TYPE,
							entityId: response.DATA.ENTITY_ID,
							entityTitle: response.DATA.ENTITY_TITLE,
							faceId: response.FACE_ID
						});
					}
					else if(response.ERRORS.length > 0)
					{
						window.alert(response.ERRORS[0]);
					}
				});
			});
		}
		else if(self.state == 'picture')
		{
			self.__setState('video');
			BX.removeClass(videoContainer, 'crm-activity-visit-hidden');
			BX.addClass(pictureContainer, 'crm-activity-visit-hidden');
			BX.addClass(self.elements.social, 'crm-activity-visit-block-disable');

			// BX.removeClass(self.getNode('faceid-button-search-social'), 'crm-activity-visit-hidden');
			// BX.addClass(self.getNode('faceid-vk-profile'), 'crm-activity-visit-hidden');

			self.__getMediaStream();
			self.callbacks.onReset();
		}
	};

	FaceSearch.prototype.__onSettingsButtonClick = function(e)
	{
		var self = this;
		var menuItems = [];

		if(this.settingsMenu)
		{
			this.settingsMenu.popupWindow.close();
			this.settingsMenu = null;
			return;
		}
		if(this.cameraList.length === 0)
			return;

		this.cameraList.forEach(function(cameraInfo)
		{
			var menuItem = {
				id: "setCamera" + cameraInfo.deviceId,
				text: cameraInfo.label,
				onclick: function()
				{
					self.changeCamera(cameraInfo.deviceId);
					self.settingsMenu.popupWindow.close();
				}
			};

			if(cameraInfo.deviceId == self.__getDefaultCamera())
			{
				menuItem.className = "crm-activity-visit-popup-settings-icon-checked";
			}
			else
			{
				menuItem.className = "crm-activity-visit-popup-settings-icon-none";
			}

			menuItems.push(menuItem);
		});
		this.settingsMenu = BX.PopupMenu.create(
			'visitTrackerCameraSettingsMenu',
			this.buttons.settings,
			menuItems,
			{
				autoHide: true,
				offsetTop: 0,
				offsetLeft: Math.round(self.buttons.settings.offsetWidth / 2),
				angle: {position: "top"},
				events: {
					onPopupClose : function()
					{
						self.settingsMenu.popupWindow.destroy();
						BX.PopupMenu.destroy('visitTrackerCameraSettingsMenu');
					},
					onPopupDestroy: function ()
					{
						self.settingsMenu = null;
					}
				}
			}
		);
		this.settingsMenu.popupWindow.show();
	};

	FaceSearch.prototype.__onSearchSocialButtonClick = function(e)
	{
		this.socialSelector = new SocialSelector({
			imageBlob: this.getImageBlob(),
			onSelect: this.__onSocialProfileSelected.bind(this),
			onDispose: this.__onSocialSelectorClosed.bind(this)
		});
		this.socialSelector.show();
	};

	FaceSearch.prototype.__onSavePhotoButtonClick = function(e)
	{
		this.savePhoto = !this.savePhoto;
		if(this.savePhoto)
			BX.addClass(this.buttons.savePhoto, 'crm-activity-visit-faceid-checkbox-active');
		else
			BX.removeClass(this.buttons.savePhoto, 'crm-activity-visit-faceid-checkbox-active');
	};

	FaceSearch.prototype.__setState = function(newState)
	{
		var pictureButton = this.getNode('faceid-button-picture');
		switch (newState)
		{
			case 'picture':
				pictureButton.innerText = BX.message('CRM_ACTIVITY_VISIT_OWNER_RETAKE_PICTURE');
				break;
			case 'video':
				pictureButton.innerText = BX.message('CRM_ACTIVITY_VISIT_OWNER_TAKE_PICTURE');
				break;
		}
		this.state = newState;
		this.__renderSavePhoto();
	};

	FaceSearch.prototype.__showLoader = function()
	{
		BX.addClass(this.buttons.picture, 'crm-activity-visit-hidden');
		BX.removeClass(this.elements.loader,'crm-activity-visit-hidden');
	};

	FaceSearch.prototype.__hideLoader = function()
	{
		BX.removeClass(this.buttons.picture, 'crm-activity-visit-hidden');
		BX.addClass(this.elements.loader,'crm-activity-visit-hidden');
	};

	FaceSearch.prototype.createPicture = function(next)
	{
		var canvas = this.getNode('faceid-canvas');
		var context = canvas.getContext('2d');
		var video = this.getNode('faceid-video');
		var width = video.videoWidth;
		var height = video.videoHeight;

		if(width === 0 || height === 0)
			return false;

		canvas.width = width;
		canvas.height = height;

		context.drawImage(video, 0, 0, width, height);
		canvas.toBlob(function(imageBlob)
		{
			next(imageBlob);
		});
	};

	FaceSearch.prototype.searchFace = function(imageBlob, next)
	{
		var self = this;
		var formData = new FormData();
		
		formData.append('IMAGE', imageBlob);
		formData.append('sessid', BX.bitrix_sessid());
		formData.append('ajax_action', 'RECOGNIZE');
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: self.ajaxUrl,
			data: formData,
			preparePost: false,
			onsuccess: function(response)
			{
				next(response);
			}
		});
	};

	FaceSearch.prototype.setVkProfile = function(profile)
	{
		var profileContainer = this.getNode('faceid-vk-profile');
		var profileLink = this.getNode('faceid-vk-profile-link');
		var searchButton = this.getNode('faceid-button-search-social');

		profileLink.innerText = 'VK.com/' + BX.util.htmlspecialchars(profile);
		profileLink.href = 'https://vk.com/' + BX.util.htmlspecialchars(profile);
		BX.addClass(searchButton, 'crm-activity-visit-hidden');
		BX.removeClass(profileContainer, 'crm-activity-visit-hidden');
	};

	FaceSearch.prototype.__onSocialProfileSelected = function(profile)
	{
		this.setVkProfile(profile);
		this.callbacks.onSocialProfileSelected(profile);
	};

	FaceSearch.prototype.__onSocialSelectorClosed = function()
	{
		this.socialSelector = null;
	};

	FaceSearch.prototype.__renderSavePhoto = function()
	{
		if(this.state === 'picture' && this.visitView.owner.type.value === 'CONTACT')
		{
			BX.removeClass(this.buttons.savePhoto, 'crm-activity-visit-hidden');
		}
		else
		{
			BX.addClass(this.buttons.savePhoto, 'crm-activity-visit-hidden');
		}
	};

	FaceSearch.prototype.dispose = function()
	{
		if(this.mediaStream)
		{
			stopMediaStream(this.mediaStream);
			this.mediaStream = null;
		}
	};

	var SocialSelector = function(params)
	{
		this.node = null;
		this.popup = null;
		this.imageBlob = params.imageBlob;

		this.callbacks = {
			onSelect: BX.type.isFunction(params.onSelect) ? params.onSelect : nop,
			onDispose: BX.type.isFunction(params.onDispose) ? params.onDispose : nop
		}
	};

	SocialSelector.prototype.getNode = function(name, scope)
	{
		if (!scope)
			scope = this.node;

		return scope ? scope.querySelector('[data-role="'+name+'"]') : null;
	};

	SocialSelector.prototype.setImageBlob = function(imageBlob)
	{
		this.imageBlob = imageBlob;
	};

	SocialSelector.prototype._bindEvents = function()
	{
		var selectButtons = document.querySelectorAll('[data-role="faceid-social-button-select"]');
		var i;

		if (selectButtons)
		{
			for (i = 0; i < selectButtons.length; i++)
			{
				selectButtons.item(i).addEventListener('click', this._onSelectButtonClick.bind(this));
			}
		}
	};

	SocialSelector.prototype.show = function()
	{
		var self = this;
		var loader = this.getNode('template-social-search', document).innerHTML;
		this.node = BX.create('div', {html: loader});

		this.popup = new BX.PopupWindow(
			'crm-activity-visit-social-selector',
			null,
			{
				titleBar: BX.message('CRM_ACTIVITY_VISIT_FACEID_SOCIAL_SEACH_IN_PROCESS'),
				content: this.node,
				closeIcon: true,
				closeByEsc: true,
				draggable: true,
				noAllPaddings: true,

				events: {
					OnPopupClose: function() {
						this.destroy();
					},
					OnPopupDestroy: function() {
						self.dispose();
					}
				}
			}
		);
		this.popup.show();
		this.startSearch(function(response)
		{
			self.node.innerHTML = response;
			self.popup.adjustPosition();
			self._bindEvents();
		});
	};

	SocialSelector.prototype.startSearch = function(next)
	{
		var formData = new FormData();

		formData.append('IMAGE', this.imageBlob);
		formData.append('sessid', BX.bitrix_sessid());
		formData.append('ajax_action', 'SEARCH_SOCIAL');
		BX.ajax({
			method: 'POST',
			dataType: 'html',
			url: AJAX_URL,
			data: formData,
			preparePost: false,
			onsuccess: function(response)
			{
				next(response);
			}
		});
	};
	
	SocialSelector.prototype._onSelectButtonClick = function(e)
	{
		var profile = e.target.dataset.profile;
		this.callbacks.onSelect(profile);
		this.popup.close();
	};

	SocialSelector.prototype.dispose = function()
	{
		this.callbacks.onDispose();
	};

	var nop = function(){};
	var stopMediaStream = function(mediaStream)
	{
		if(!(mediaStream instanceof MediaStream))
			return;

		if (typeof mediaStream.getTracks === 'undefined')
		{
			// Support for legacy browsers
			mediaStream.stop();
		}
		else
		{
			mediaStream.getTracks().forEach(function(track)
			{
				track.stop();
			});
		}
	};

})();