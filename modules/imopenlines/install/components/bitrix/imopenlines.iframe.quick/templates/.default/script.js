window.frameCommunication = {timeout: {}};

quickAnswersManager = function(params, callback)
{
	this.PAGE_SIZE = 10;

	this.searchInput = BX('quick-search-input');
	this.searchTimeoutId = 0;
	this.searchOffset = 0;
	this.resultContainer = BX('quick-result');
	this.searchContainer = BX('quick-search-container');
	this.editIdContainer = BX('quick-edit-id');
	this.infoContainer = BX('quick-info-container');
	this.searchNotificationContainer = BX('quick-search-notification');

	this.searchSectionId = 0;
	this.sections = params.sections || {};
	this.editSectionListContainer = BX('edit-category-list');
	this.editCurrentSectionNameContainer = BX('quick-edit-category');
	this.searchSectionsContainer = BX('search_category_list');
	this.getButtonsManager = function()
	{
		return BX.Main.interfaceButtonsManager.getById('search_category_list');
	};

	this.editSectionsContainer = BX('quick-edit-section-select');
	this.editContainer = BX('quick-edit-container');
	this.editTextarea = BX('quick-edit-text');
	this.editCancelButton = BX('quick-edit-cancel');
	this.editSaveButton = BX('quick-edit-save');
	this.editSectionId = 0;
	this.editNotificationContainer = BX('quick-edit-result');

	this.hiddenClassName = 'quick-hidden';

	this.ajaxUrl = params.ajaxUrl;
	this.allUrl = params.allUrl;
	this.searchTimeout = params.searchTimeout || 500;
	this.allCount = params.allCount || 0;
	this.lineId = params.lineId;

	this.actionInProgress = false;

	this.answers = {};
	this.freshId = 0;

	this.init(callback);

	if(this.allCount === 0)
	{
		this.showInfo();
	}
	else
	{
		this.showSearch();
		this.search();
	}
};

quickAnswersManager.prototype.init = function(callback)
{
	this.frameCommunicationInit();
	BX.bind(BX('quick-all-url'), 'click', BX.delegate(this.openAllUrl, this));
	BX.bind(BX('quick-info-all-url'), 'click', BX.delegate(this.openAllUrl, this));
	BX.bind(BX('quick-create-message'), 'click', BX.delegate(this.createMessage, this));
	BX.bind(BX('quick-info-create-message'), 'click', BX.delegate(this.createMessage, this));
	BX.bind(this.searchInput, 'keyup', BX.delegate(this.onSearchInputType, this));
	BX.bind(this.editTextarea, 'keyup', BX.delegate(this.onEditTextareaType, this));
	BX.bind(this.editCancelButton, 'click', BX.delegate(function()
	{
		//this.searchOffset = 0;
		this.showSearch();
		this.search();
	}, this));
	BX.bindDelegate(this.editSectionListContainer, 'click', {className: 'imopenlines-iframe-quick-category-item'}, BX.delegate(function(e)
	{
		this.setEditSection(e.target.getAttribute('data-id'));
	}, this));
	BX.bind(this.editCurrentSectionNameContainer, 'click', BX.delegate(function()
	{
		if(BX.hasClass(this.editSectionsContainer, 'imopenlines-iframe-quick-edit-active'))
		{
			BX.removeClass(this.editSectionsContainer, 'imopenlines-iframe-quick-edit-active');
			BX.unbind(this.editSectionsContainer, 'click', this.cancelBubble);
			BX.unbind(window, 'click', BX.proxy(this.closeEditSectionsList, this));
		}
		else
		{
			BX.addClass(this.editSectionsContainer, 'imopenlines-iframe-quick-edit-active');
			BX.bind(this.editSectionsContainer, 'click', this.cancelBubble);
			BX.bind(window, 'click', BX.proxy(this.closeEditSectionsList, this));
		}
	}, this));
	BX.bind(this.editSaveButton, 'click', BX.delegate(function()
	{
		if(this.editTextarea.value.length > 0)
		{
			this.editSave();
		}
	}, this));

	this.sectionsByIndex = [];
	for(var index in this.sections)
	{
		if(this.sections.hasOwnProperty(index))
		{
			this.sectionsByIndex.push(index);
		}
	}

	if(BX.type.isFunction(callback))
	{
		callback();
	}
};

quickAnswersManager.prototype.frameCommunicationInit = function()
{
	if(typeof window.postMessage === 'function')
	{
		BX.bind(window, 'message', BX.proxy(function(event){
			var data = {};
			try { data = JSON.parse(event.data); } catch (err){}

			if (data.action === 'init')
			{
				frameCommunication.uniqueLoadId = data.uniqueLoadId;
				frameCommunication.postMessageSource = event.source;
				frameCommunication.postMessageOrigin = event.origin;
			}
		}, this));
	}
};

quickAnswersManager.prototype.frameCommunicationSend = function(data)
{
	data['uniqueLoadId'] = frameCommunication.uniqueLoadId;
	var encodedData = JSON.stringify(data);
	if (!frameCommunication.postMessageOrigin)
	{
		clearTimeout(frameCommunication.timeout[encodedData]);
		frameCommunication.timeout[encodedData] = setTimeout(function(){
			this.frameCommunicationSend(data);
		}, 10);
		return true;
	}

	if(typeof window.postMessage === 'function')
	{
		if(frameCommunication.postMessageSource)
		{
			frameCommunication.postMessageSource.postMessage(
				encodedData,
				frameCommunication.postMessageOrigin
			);
		}
	}
};

quickAnswersManager.prototype.search = function(hideMessage)
{
	hideMessage = hideMessage !== false;
	if(hideMessage)
	{
		this.searchHideMessage();
	}
	this.showSearchProgress();
	var sectionId = this.getSearchSection();
	var search = this.searchInput.value;
	var showInfo = false;

	BX.ajax({
		url: this.ajaxUrl,
		method: 'POST',
		data: {
			'action': 'search',
			'search': search,
			'sectionId': sectionId,
			'offset': this.searchOffset,
			'lang': BX.message('LANG'),
			'sessid': BX.bitrix_sessid(),
			'lineId': this.lineId
		},
		timeout: 60,
		dataType: 'json',
		processData: true,
		onsuccess: BX.proxy(function(data)
		{
			this.hideSearchProgress();
			this.hideSearchNotFound();
			data = data || {};

			if(this.searchOffset === 0)
			{
				BX.cleanNode(this.resultContainer);
			}

			if(data.result && data.result.length > 0)
			{
				this.allCount = data.allCount || data.result.length;
				BX.removeClass(this.resultContainer, this.hiddenClassName);
				this.renderResults(data.result);

				if(this.allCount > this.searchOffset + data.result.length)
				{
					this.resultContainer.appendChild(BX.create('span', {
						attrs: {id: "quick-result-item-more"},
						props: {className: "quick-result-item-more"},
						events: {click: BX.proxy(function(){
							this.searchOffset += this.PAGE_SIZE;
							this.search();
						}, this)},
						text: BX.message('MORE')
					}));
				}
				else
				{
					BX.remove(BX('quick-result-item-more'));
				}
			}
			else if(this.searchOffset > 0)
			{
				BX.removeClass(this.resultContainer, this.hiddenClassName);
				BX.remove(BX('quick-result-item-more'));
			}
			else
			{
				if(sectionId === 0 && search.length === 0)
				{
					showInfo = true;
				}
				else
				{
					this.showSearchNotFound();
				}
			}

			if(showInfo)
			{
				this.showInfo();
			}
			else
			{
				this.showSearch(hideMessage);
			}

		}, this)
	});
};

quickAnswersManager.prototype.showSearchProgress = function()
{
	//this.resultContainer.style.opacity = 0.5;
	BX.removeClass(BX('quick-search-progress'), this.hiddenClassName);
	BX.addClass(this.resultContainer, this.hiddenClassName);
	BX.addClass(BX('quick-search-not-found'), this.hiddenClassName);
};

quickAnswersManager.prototype.hideSearchProgress = function()
{
	BX.addClass(BX('quick-search-progress'), this.hiddenClassName);
};

quickAnswersManager.prototype.showSearchNotFound = function()
{
	BX.removeClass(BX('quick-search-not-found'), this.hiddenClassName);
	BX.addClass(this.resultContainer, this.hiddenClassName);
	BX.addClass(BX('quick-search-progress'), this.hiddenClassName);
};

quickAnswersManager.prototype.hideSearchNotFound= function()
{
	BX.addClass(BX('quick-search-not-found'), this.hiddenClassName);
};

quickAnswersManager.prototype.getSearchSection = function()
{
	return this.searchSectionId;
};

quickAnswersManager.prototype.setSearchSection = function(sectionId, invokeSearch)
{
	if(this.searchSectionId === sectionId)
	{
		return;
	}
	invokeSearch = !!invokeSearch;
	this.searchSectionId = sectionId;
	if(invokeSearch)
	{
		this.searchOffset = 0;
		this.search();
	}
	this.renderSearchSections();
};

quickAnswersManager.prototype.renderSearchSections = function()
{
	this._markActive(this.searchSectionsContainer, 'imopenlines-iframe-quick-menu-item', 'main-buttons-item-active', this.searchSectionId);
};

quickAnswersManager.prototype._markActive = function(container, commonClassName, activeClassName, sectionId)
{
	var items = BX.findChildren(container, {className: commonClassName});
	var currentSection = 0;
	for(var i in items)
	{
		if(items.hasOwnProperty(i))
		{
			if(items[i].getAttribute('data-id'))
			{
				currentSection = parseInt(items[i].getAttribute('data-id'));
				if(currentSection === sectionId)
				{
					BX.addClass(items[i], activeClassName);
				}
				else
				{
					BX.removeClass(items[i], activeClassName);
				}
			}
		}
	}
};

quickAnswersManager.prototype.renderResults = function(items)
{
	//BX.cleanNode(this.resultContainer);
	var manager = this;

	for (var i = 0; i < items.length; i++)
	{
		if(items.hasOwnProperty(i))
		{
			this.answers[items[i].id] = items[i];
			BX.append(BX.create('div', {
				attrs:
				{
					className: 'imopenlines-iframe-quick-result-block' + (this.freshId == items[i].id? ' imopenlines-iframe-quick-result-block-fresh' : '')
				},
				children: [
					BX.create('div', {
						attrs: {
							'data-bx-item-id': items[i].id,
							'data-bx-item-text': items[i].text,
							className: 'imopenlines-iframe-quick-result-item'
						},
						html: items[i].name,
						events: {
							click: function()
							{
								manager.putMessage(this.getAttribute('data-bx-item-text'), this.getAttribute('data-bx-item-id'));
							}
						}
					}),
					BX.create('a', {
						attrs: {
							'data-bx-item-id': items[i].id,
							className: 'imopenlines-iframe-quick-result-edit'
						},
						events: {
							click: function(e)
							{
								e.preventDefault();
								manager.editMessage(this.getAttribute('data-bx-item-id'));
								return false;
							}
						}
					})
				]
			}), this.resultContainer);
		}
	}

	if(this.freshId > 0)
	{
		setTimeout(BX.delegate(function()
		{
			var fresh = BX.findChildByClassName(this.resultContainer, 'imopenlines-iframe-quick-result-block-fresh');
			if(fresh)
			{
				var coords = fresh.getBoundingClientRect();
				var scrollTo = coords.top - this.resultContainer.offsetTop + this.resultContainer.scrollTop;
				if(scrollTo > 0)
				{
					(new BX.easing({
						duration : 1000,
						start : { scroll : 0 },
						finish : { scroll : scrollTo },
						transition : BX.easing.makeEaseInOut(BX.easing.transitions.quart),
						step : BX.delegate(function(state){
							this.resultContainer.scrollTo(0, state.scroll);
						}, this),
						complete: function()
						{
							BX.removeClass(fresh, 'imopenlines-iframe-quick-result-block-fresh')
						}
					})).animate();
				}
				this.freshId = 0;
			}
		}, this), 200);
	}
};

quickAnswersManager.prototype.openAllUrl = function()
{
	window.open(this.allUrl, '_blank');
};

quickAnswersManager.prototype.putMessage = function(message, answerId)
{
	if(answerId > 0)
	{
		BX.ajax({
			url: this.ajaxUrl,
			method: 'POST',
			data: {
				'action': 'rating',
				'id': answerId,
				'sessid': BX.bitrix_sessid(),
				'lineId': this.lineId
			}
		});
	}
	this.frameCommunicationSend({'action': 'put', 'message': message});
	this.frameCommunicationSend({'action': 'close'});
};

quickAnswersManager.prototype.editMessage = function(id)
{
	this.actionInProgress = false;
	BX.addClass(this.editNotificationContainer, this.hiddenClassName);
	BX.removeClass(this.editTextarea, 'imopenlines-iframe-quick-edit-textarea-result');
	BX.removeClass(this.editContainer, this.hiddenClassName);
	BX.addClass(this.searchContainer, this.hiddenClassName);
	BX.addClass(this.infoContainer, this.hiddenClassName);
	this.editTextarea.focus();
	if(!id)
	{
		this.editTextarea.value = '';
		this.editIdContainer.value = 0;
		this.setEditSection(0);
		this.editSaveButton.innerText = BX.message('IMOL_QUICK_ANSWERS_EDIT_CREATE');
		BX.removeClass(this.editSaveButton, 'imopenlines-iframe-quick-edit-save');
		BX.addClass(this.editSaveButton, 'imopenlines-iframe-quick-edit-cancel');
		this.setEditSection(this.searchSectionId);
	}
	else
	{
		if(this.answers[id])
		{
			this.editTextarea.value = this.answers[id].text;
			this.editIdContainer.value = id;
			this.setEditSection(this.answers[id].section);
			this.editSaveButton.innerText = BX.message('IMOL_QUICK_ANSWERS_EDIT_UPDATE');
			BX.addClass(this.editSaveButton, 'imopenlines-iframe-quick-edit-save');
			BX.removeClass(this.editSaveButton, 'imopenlines-iframe-quick-edit-cancel');
		}
	}
};

quickAnswersManager.prototype.createMessage = function()
{
	this.editMessage(0);
};

quickAnswersManager.prototype.setEditSection = function(sectionId)
{
	this.editSectionId = sectionId;
	this.editCurrentSectionNameContainer.innerText = this.sections[this.editSectionId].NAME;
	this._markActive(this.editSectionListContainer, 'imopenlines-iframe-quick-category-item', 'imopenlines-iframe-quick-category-item-selected', this.editSectionId);
};

quickAnswersManager.prototype.onSearchInputType = function(e, immediately)
{
	if(e.ctrlKey || e.altKey || e.keyCode === 17 || e.keyCode === 18 || e.keyCode === 16)
	{
		return false;
	}

	this.showSearchProgress();
	var timeoutPeriod = this.searchTimeout;
	if(immediately === true)
	{
		timeoutPeriod = 15;
	}

	if(e.keyCode === 27)
	{
		if(this.searchInput.value)
		{
			this.searchInput.value = '';
		}
		else
		{
			this.frameCommunicationSend({
				'action': 'close'
			});
			return false;
		}
	}

	clearTimeout(this.searchTimeoutId);
	this.searchTimeoutId = setTimeout(BX.delegate(function(){
		this.searchOffset = 0;
		this.search();
	}, this), timeoutPeriod);
};

quickAnswersManager.prototype.cancelBubble = function(event)
{
	event = event || window.event;

	if (event.stopPropagation)
	{
		event.stopPropagation();
	}
	else
	{
		event.cancelBubble = true;
	}
};

quickAnswersManager.prototype.closeEditSectionsList = function()
{
	BX.removeClass(this.editSectionsContainer, 'imopenlines-iframe-quick-edit-active');
	BX.unbind(this.editSectionsContainer, 'click', this.cancelBubble);
	BX.unbind(window, 'click', BX.proxy(this.closeEditSectionsList, this));
};

quickAnswersManager.prototype.editSave = function()
{
	if(this.actionInProgress === true)
	{
		return;
	}
	this.actionInProgress = true;
	BX.addClass(this.editNotificationContainer, this.hiddenClassName);
	BX.removeClass(this.editTextarea, 'imopenlines-iframe-quick-edit-textarea-result');
	BX.removeClass(this.editSectionsContainer, 'imopenlines-iframe-quick-edit-active');

	var text = this.editTextarea.value;
	var id = parseInt(this.editIdContainer.value);
	if(text.length === 0)
	{
		this.editShowMessage(BX.message('IMOL_QUICK_ANSWERS_EDIT_ERROR_EMPTY_TEXT'));
		return false;
	}
	var sectionId = this.editSectionId;
	BX.ajax({
		url: this.ajaxUrl,
		method: 'POST',
		data: {
			'action': 'edit',
			'text': text,
			'id': id,
			'sectionId': sectionId,
			'lang': BX.message('LANG'),
			'sessid': BX.bitrix_sessid(),
			'lineId': this.lineId
		},
		timeout: 60,
		dataType: 'json',
		processData: true,
		onsuccess: BX.proxy(function(data)
		{
			this.actionInProgress = false;
			if(!data.error)
			{
				if(data.id && data.id > 0)
				{
					this.freshId = data.id;
				}
				if(id === 0)
				{
					this.allCount++;
					this.setLastPage();
				}
				this.showSearch();
				this.search(false);
				this.searchShowMessage(false);
				BX.addClass(this.editNotificationContainer, this.hiddenClassName);
				BX.removeClass(this.editTextarea, 'imopenlines-iframe-quick-edit-textarea-result');
			}
			else
			{
				this.editShowMessage(data.text);
			}
			BX.addClass(this.editSaveButton, 'imopenlines-iframe-quick-edit-save');
			BX.removeClass(this.editSaveButton, 'imopenlines-iframe-quick-edit-cancel');
		}, this)
	});

	return false;
};

quickAnswersManager.prototype.searchShowMessage = function(message)
{
	if(message)
	{
		this.searchNotificationContainer.innerHTML = message;
	}
	BX.removeClass(this.searchNotificationContainer, this.hiddenClassName);
	BX.addClass(this.resultContainer, 'imopenlines-iframe-quick-result-with-message');
};

quickAnswersManager.prototype.searchHideMessage = function()
{
	BX.addClass(this.searchNotificationContainer, this.hiddenClassName);
	BX.removeClass(this.resultContainer, 'imopenlines-iframe-quick-result-with-message');
};

quickAnswersManager.prototype.editShowMessage = function(message)
{
	if(message)
	{
		this.editNotificationContainer.innerHTML = message;
	}
	BX.removeClass(this.editNotificationContainer, this.hiddenClassName);
	BX.addClass(this.editTextarea, 'imopenlines-iframe-quick-edit-textarea-result');
};

quickAnswersManager.prototype.showInfo = function()
{
	BX.removeClass(this.infoContainer, this.hiddenClassName);
	BX.addClass(this.editContainer, this.hiddenClassName);
	BX.addClass(this.searchContainer, this.hiddenClassName);
	this.hideSearchNotFound();
	this.hideSearchProgress();
};

quickAnswersManager.prototype.showSearch = function(hideMessage)
{
	hideMessage = hideMessage !== false;
	BX.addClass(this.infoContainer, this.hiddenClassName);
	BX.addClass(this.editContainer, this.hiddenClassName);
	BX.removeClass(this.searchContainer, this.hiddenClassName);
	BX.removeClass(this.editSectionsContainer, 'imopenlines-iframe-quick-edit-active');
	this.searchInput.focus();
	setTimeout(BX.delegate(this.getButtonsManager().adjustMoreButtonPosition, this), 100);
	setTimeout(BX.delegate(this.bindMenuEvents, this), 200);
	if(hideMessage)
	{
		this.searchHideMessage();
	}
};

quickAnswersManager.prototype.onEditTextareaType = function(e)
{
	if (e.ctrlKey && e.keyCode === 13)
	{
		this.editSave();
		return;
	}

	if(e.keyCode === 27)
	{
		//this.searchOffset = 0;
		this.showSearch();
		this.search();
	}

	if(this.editTextarea.value.length === 0)
	{
		BX.removeClass(this.editSaveButton, 'imopenlines-iframe-quick-edit-save');
		BX.addClass(this.editSaveButton, 'imopenlines-iframe-quick-edit-cancel');
	}
	else
	{
		BX.addClass(this.editSaveButton, 'imopenlines-iframe-quick-edit-save');
		BX.removeClass(this.editSaveButton, 'imopenlines-iframe-quick-edit-cancel');
	}
};

quickAnswersManager.prototype.setLastPage = function()
{
	if(this.allCount > this.searchOffset)
	{
		this.searchOffset = Math.floor(this.allCount / this.PAGE_SIZE) * this.PAGE_SIZE;
	}
};

quickAnswersManager.prototype._getPopupWindowItems = function()
{
	var popupContainer = BX('menu-popup-main_buttons_popup_search_category_list');
	// BX.findChildren doesn't work here
	if(popupContainer)
	{
		return popupContainer.firstChild.firstChild.firstChild.children;
	}

	return null;
};

quickAnswersManager.prototype.bindMenuEvents = function()
{
	if(!this.getSearchSectionsMenuMoreButton())
	{
		return;
	}
	BX.unbind(this.getSearchSectionsMenuMoreButton(), 'click');
	BX.bind(this.getSearchSectionsMenuMoreButton(), 'click', BX.delegate(function()
	{
		this.getButtonsManager().showSubmenu();
		setTimeout(BX.delegate(function()
		{
			var items = this._getPopupWindowItems();
			var length = items.length;
			for(var i = 0, index = 0; i < length; i++, index++)
			{
				if(items[i].tagName != 'A')
				{
					index--;
					continue;
				}
				if(this.sectionsByIndex.hasOwnProperty(index))
				{
					items[i].setAttribute('data-id', this.sectionsByIndex[index]);
					BX.unbindAll(items[i]);
					BX.bind(items[i], 'click', BX.delegate(function(e)
					{
						var sectionId;
						if(e.target.tagName != 'A')
						{
							var parent = BX.findParent(e.target, {className: 'main-buttons-submenu-item'});
							if(parent)
							{
								sectionId = parent.getAttribute('data-id');
							}
						}
						else
						{
							sectionId = e.target.getAttribute('data-id');
						}
						sectionId = sectionId || 0;
						this.setSearchSection(sectionId, true)
					}, this));
				}
			}
		}, this), 100);
	}, this));
};

quickAnswersManager.prototype.getSearchSectionsMenuMoreButton = function()
{
	return BX('search_category_list_more_button');
};