var CrmFormEditor = function(params)
{

	this.init = function (params)
	{
		this.jsEventsManagerId = params.jsEventsManagerId;
		this.detailPageUrlTemplate = params.detailPageUrlTemplate;
		this.id = params.id;

		this.userBlockController = new CrmFormEditorUserBlockController(params.userBlocks);
		this.externalAnalytics = new CrmFormEditorExternalAnalytics();

		params.fields = params.fields || [];
		this.context = params.context;
		this.templates = params.templates;
		this.mess = params.mess;
		this.currency = params.currency;
		this.actionRequestUrl = params.actionRequestUrl;
		this.canRemoveCopyright = params.canRemoveCopyright;
		this.showRestrictionPopup = params.showRestrictionPopup;

		this.dynamicEntities = params.dynamicEntities;
		this.entityDictionary = params.entityDictionary;
		this.schemesDictionary = params.schemesDictionary;
		this.fieldsDictionary = params.fieldsDictionary;
		this.booleanFieldItems = params.booleanFieldItems;
		this.isFrame = params.isFrame;
		this.isSaved = params.isSaved;
		this.reloadList = params.reloadList;
		this.designPageUrl = params.designPageUrl;
		this.isAvailableDesign = params.isAvailableDesign;
		this.fields = [];

		if(!this.reloadList)
		{
			this.actionRequestUrl = BX.util.add_url_param(this.actionRequestUrl, {RELOAD_LIST: 'N'});
		}

		/* init slider support  */
		if (params.editorChoise.confirm)
		{
			this.showEditorChoise(params.editorChoise);
		}
		BX.bind(BX('crm-webform-editor-choise-btn'), 'click', this.showEditorChoise.bind(this, params.editorChoise))

		/* init slider support  */
		this.initSlider();

		/* init helper */
		this.helper = new CrmFormEditorHelper();

		/* init drag&drop fields */
		this.dragdrop = new CrmFormEditorDragDrop(params.dragdrop);
		BX.addCustomEvent(this.dragdrop, 'onSort', BX.delegate(this.onSort, this));

		/* init existed fields */
		this.initExistedFields(params);

		/* init filter of field selector */
		this.popupFieldSettings = new CrmWebFormEditPopupFieldSettings({
			caller: this,
			content: BX('CRM_WEB_FORM_POPUP_SETTINGS'),
			container: BX('CRM_WEB_FORM_POPUP_SETTINGS_CONTAINER')
		});


		/* init filter of field selector */
		this.fieldSelector = new CrmFormEditorFieldSelector({
			caller: this,
			context: BX('FIELD_SELECTOR')
		});

		/* init form submit button editing */
		this.formButton = new CrmWebFormEditFormButton({
			caller: this,
			context: BX('FORM_BUTTON_CONTAINER')
		});

		/* init entity scheme */
		this.entityScheme = new CrmWebFormEditEntityScheme({
			caller: this,
			context: BX('ENTITY_SCHEME_CONTAINER')
		});

		/* init dependencies */
		this.dependencies = new CrmFormEditorDependencies({
			caller: this,
			dependencies: params.dependencies
		});

		/* init preset fields */
		this.presetFields = new CrmFormEditorFieldPreset({
			caller: this,
			fields: params.presetFields
		});

		this.destination = new CrmFormEditorDestination({
			'caller': this,
			'container': BX('crm-webform-edit-responsible')
		});

		this.adsForm = new CrmFormAdsForm({
			'caller': this,
			'container': BX('CRM_WEBFORM_ADS_FORM')
		});

		/* first fields sorting */
		this.sortFields();

		/* init animation */
		this.initAnimation();

		/* init interface  */
		this.initInterface();

		/* init tooltips  */
		this.initToolTips();

		var backgroundImages = document.getElementsByName('BACKGROUND_IMAGE');
		BX.bind(backgroundImages[0], 'bxchange', function(){
			var parts = [];
			parts = this.value.replace(/\\/g, '/').split( '/' );
			BX('BACKGROUND_IMAGE_TEXT').innerText = parts[parts.length-1];
			BX('BACKGROUND_IMAGE_TEXT').style.display = '';
		});
	};

	this.showEditorChoise = function(editorChoise)
	{
		if (!editorChoise.newLink)
		{
			return;
		}

		if (editorChoise.popup)
		{
			editorChoise.popup.show();
			return;
		}

		var editorValue = editorChoise.new;
		editorChoise.popup = new BX.PopupWindow({
			//titleBar: this.caller.mess.dlgInvoiceEmptyProductTitle || 'xxx',
			autoHide: false,
			lightShadow: true,
			closeByEsc: true,
			width: 600,
			overlay: {backgroundColor: 'black', opacity: 500},
			content: ''
				+ '<div class="crm-webform-ed-choise">'
					+ '<div class="crm-webform-ed-choise-header1">' + this.mess.dlgEditorChoiseH1 + '</div>'
					+ '<div class="crm-webform-ed-choise-header2">' + this.mess.dlgEditorChoiseH2 + '</div>'
					+ '<div class="crm-webform-ed-choise-header3">' + this.mess.dlgEditorChoiseH3 + '</div>'
					+ '<div class="crm-webform-ed-choise-body">'
						+ '<div id="crm-webform-editor-choise-new" class="crm-webform-ed-choise-item selected">'
							+ '<div class="crm-webform-ed-choise-item-chip">&#x2714;</div>'
							+ '<div class="crm-webform-ed-choise-item-title">' + this.mess.dlgEditorChoiseNew + '</div>'
							+ '<div class="crm-webform-ed-choise-item-icon">'
								+ '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 72 62"><g fill="none" fill-rule="evenodd" transform="translate(.62 .5)"><path fill="#2FC6F6" d="M67.76,14.64 C69.4168542,14.64 70.76,15.9831458 70.76,17.64 L70.76,58 C70.76,59.6568542 69.4168542,61 67.76,61 L21.3,61 C19.6431458,61 18.3,59.6568542 18.3,58 L18.3,17.64 C18.3,15.9831458 19.6431458,14.64 21.3,14.64 L67.76,14.64 Z M67.1,24.399 L21.96,24.399 L21.96,56.94 C21.96,57.1332997 22.0971128,57.2945749 22.279386,57.3318734 L22.36,57.34 L66.7,57.34 C66.8932997,57.34 67.0545749,57.2028872 67.0918734,57.020614 L67.1,56.94 L67.1,24.399 Z M49.46,0 C51.1168542,-4.27501655e-15 52.46,1.34314575 52.46,3 L52.459,9.76 L48.8,9.76 L48.8,9.759 L3.66,9.759 L3.66,43.52 C3.66,43.7132997 3.7971128,43.8745749 3.97938605,43.9118734 L4.06,43.92 L13.112,43.92 L13.112,47.58 L3,47.58 C1.34314575,47.58 7.75242269e-15,46.2368542 7.10542736e-15,44.58 L7.10542736e-15,3 C7.34661044e-15,1.34314575 1.34314575,7.48448398e-16 3,0 L49.46,0 Z M13.112,29.28 L13.112,32.94 L9.54,32.94 C8.98771525,32.94 8.54,32.4922847 8.54,31.94 L8.54,30.28 C8.54,29.7277153 8.98771525,29.28 9.54,29.28 L13.112,29.28 Z M12.42,21.96 C12.9722847,21.96 13.42,22.4077153 13.42,22.96 L13.42,24.62 C13.42,25.1722847 12.9722847,25.62 12.42,25.62 L9.54,25.62 C8.98771525,25.62 8.54,25.1722847 8.54,24.62 L8.54,22.96 C8.54,22.4077153 8.98771525,21.96 9.54,21.96 L12.42,21.96 Z M26.84,17.08 C25.4924252,17.08 24.4,18.1724252 24.4,19.52 C24.4,20.8675748 25.4924252,21.96 26.84,21.96 C28.1875748,21.96 29.28,20.8675748 29.28,19.52 C29.28,18.1724252 28.1875748,17.08 26.84,17.08 Z M32.94,17.08 C31.5924252,17.08 30.5,18.1724252 30.5,19.52 C30.5,20.8675748 31.5924252,21.96 32.94,21.96 C34.2875748,21.96 35.38,20.8675748 35.38,19.52 C35.38,18.1724252 34.2875748,17.08 32.94,17.08 Z M39.04,17.08 C37.6924252,17.08 36.6,18.1724252 36.6,19.52 C36.6,20.8675748 37.6924252,21.96 39.04,21.96 C40.3875748,21.96 41.48,20.8675748 41.48,19.52 C41.48,18.1724252 40.3875748,17.08 39.04,17.08 Z M13.64,14.64 C14.1922847,14.64 14.64,15.0877153 14.64,15.64 L14.64,17.3 C14.64,17.8522847 14.1922847,18.3 13.64,18.3 L9.54,18.3 C8.98771525,18.3 8.54,17.8522847 8.54,17.3 L8.54,15.64 C8.54,15.0877153 8.98771525,14.64 9.54,14.64 L13.64,14.64 Z M8.54,2.44 C7.19242521,2.44 6.1,3.53242521 6.1,4.88 C6.1,6.22757479 7.19242521,7.32 8.54,7.32 C9.88757479,7.32 10.98,6.22757479 10.98,4.88 C10.98,3.53242521 9.88757479,2.44 8.54,2.44 Z M14.64,2.44 C13.2924252,2.44 12.2,3.53242521 12.2,4.88 C12.2,6.22757479 13.2924252,7.32 14.64,7.32 C15.9875748,7.32 17.08,6.22757479 17.08,4.88 C17.08,3.53242521 15.9875748,2.44 14.64,2.44 Z M20.74,2.44 C19.3924252,2.44 18.3,3.53242521 18.3,4.88 C18.3,6.22757479 19.3924252,7.32 20.74,7.32 C22.0875748,7.32 23.18,6.22757479 23.18,4.88 C23.18,3.53242521 22.0875748,2.44 20.74,2.44 Z"/><path fill="#9DCF00" d="M53.1657143,28.5 C53.717999,28.5 54.1657143,28.9477153 54.1657143,29.5 L54.1657143,43.5 C54.1657143,44.0522847 53.717999,44.5 53.1657143,44.5 L26.5942857,44.5 C26.042001,44.5 25.5942857,44.0522847 25.5942857,43.5 L25.5942857,29.5 C25.5942857,28.9477153 26.042001,28.5 26.5942857,28.5 L53.1657143,28.5 Z M32.4514286,31.9285714 C29.9266983,31.9285714 27.88,33.9752697 27.88,36.5 C27.88,39.0247303 29.9266983,41.0714286 32.4514286,41.0714286 C34.9761589,41.0714286 37.0228571,39.0247303 37.0228571,36.5 C37.0228571,33.9752697 34.9761589,31.9285714 32.4514286,31.9285714 Z M45.1657143,37.6428571 L40.3085714,37.6428571 C39.7562867,37.6428571 39.3085714,38.0905724 39.3085714,38.6428571 L39.3085714,38.6428571 L39.3085714,38.9285714 C39.3085714,39.4808562 39.7562867,39.9285714 40.3085714,39.9285714 L40.3085714,39.9285714 L45.1657143,39.9285714 C45.717999,39.9285714 46.1657143,39.4808562 46.1657143,38.9285714 L46.1657143,38.9285714 L46.1657143,38.6428571 C46.1657143,38.0905724 45.717999,37.6428571 45.1657143,37.6428571 L45.1657143,37.6428571 Z M50.88,33.0714286 L40.3085714,33.0714286 C39.7562867,33.0714286 39.3085714,33.5191438 39.3085714,34.0714286 L39.3085714,34.0714286 L39.3085714,34.3571429 C39.3085714,34.9094276 39.7562867,35.3571429 40.3085714,35.3571429 L40.3085714,35.3571429 L50.88,35.3571429 C51.4322847,35.3571429 51.88,34.9094276 51.88,34.3571429 L51.88,34.3571429 L51.88,34.0714286 C51.88,33.5191438 51.4322847,33.0714286 50.88,33.0714286 L50.88,33.0714286 Z"/></g></svg>'
							+ '</div>'
						+ '</div>'
						+ '<div id="crm-webform-editor-choise-old" class="crm-webform-ed-choise-item">'
							+ '<div class="crm-webform-ed-choise-item-chip">&#x2714;</div>'
							+ '<div class="crm-webform-ed-choise-item-title">' + this.mess.dlgEditorChoiseOld + '</div>'
							+ '<div class="crm-webform-ed-choise-item-icon">'
								+ '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 56 56"><g fill="none" fill-rule="evenodd"><path fill="#2FC6F6" d="M53,0 C54.6568542,-3.04359188e-16 56,1.34314575 56,3 L56,53 C56,54.6568542 54.6568542,56 53,56 L3,56 C1.34314575,56 2.02906125e-16,54.6568542 0,53 L0,3 C-2.02906125e-16,1.34314575 1.34314575,3.04359188e-16 3,0 L53,0 Z M51.9478261,3.65217391 L4.05217391,3.65217391 C3.83126001,3.65217391 3.65217391,3.83126001 3.65217391,4.05217391 L3.65217391,4.05217391 L3.65217391,51.9478261 C3.65217391,52.16874 3.83126001,52.3478261 4.05217391,52.3478261 L4.05217391,52.3478261 L51.9478261,52.3478261 C52.16874,52.3478261 52.3478261,52.16874 52.3478261,51.9478261 L52.3478261,51.9478261 L52.3478261,4.05217391 C52.3478261,3.83126001 52.16874,3.65217391 51.9478261,3.65217391 L51.9478261,3.65217391 Z"/><path fill="#2FC6F6" d="M38.1027733 9C39.2073428 9 40.1027733 9.8954305 40.1027733 11L40.1027733 16.862069C40.1027733 17.9666385 39.2073428 18.862069 38.1027733 18.862069L11 18.862069C9.8954305 18.862069 9 17.9666385 9 16.862069L9 11C9 9.8954305 9.8954305 9 11 9L38.1027733 9zM26.2065014 12.7931034L13.7930211 12.7931034C13.2407364 12.7931034 12.7930211 13.2408187 12.7930211 13.7931034L12.7930211 13.7931034 12.7930211 14.0689655C12.7930211 14.6212503 13.2407364 15.0689655 13.7930211 15.0689655L13.7930211 15.0689655 26.2065014 15.0689655C26.7587862 15.0689655 27.2065014 14.6212503 27.2065014 14.0689655L27.2065014 14.0689655 27.2065014 13.7931034C27.2065014 13.2408187 26.7587862 12.7931034 26.2065014 12.7931034L26.2065014 12.7931034zM38.1027733 21C39.2073428 21 40.1027733 21.8954305 40.1027733 23L40.1027733 28.862069C40.1027733 29.9666385 39.2073428 30.862069 38.1027733 30.862069L11 30.862069C9.8954305 30.862069 9 29.9666385 9 28.862069L9 23C9 21.8954305 9.8954305 21 11 21L38.1027733 21zM26.2065014 24.7931034L13.7930211 24.7931034C13.2407364 24.7931034 12.7930211 25.2408187 12.7930211 25.7931034L12.7930211 25.7931034 12.7930211 26.0689655C12.7930211 26.6212503 13.2407364 27.0689655 13.7930211 27.0689655L13.7930211 27.0689655 26.2065014 27.0689655C26.7587862 27.0689655 27.2065014 26.6212503 27.2065014 26.0689655L27.2065014 26.0689655 27.2065014 25.7931034C27.2065014 25.2408187 26.7587862 24.7931034 26.2065014 24.7931034L26.2065014 24.7931034z"/><path fill="#9DCF00" d="M25.9547172,33.8730044 C27.0592867,33.8730044 27.9547172,34.7684349 27.9547172,35.8730044 L27.9547172,45.9565217 C27.9547172,47.0610912 27.0592867,47.9565217 25.9547172,47.9565217 L11.2173913,47.9565217 C10.1128218,47.9565217 9.2173913,47.0610912 9.2173913,45.9565217 L9.2173913,35.8730044 C9.2173913,34.7684349 10.1128218,33.8730044 11.2173913,33.8730044 L25.9547172,33.8730044 Z M22.6663504,36.7831394 L17.2498727,41.7551904 L15.0830275,39.8654526 L13.2832589,41.4030009 L17.2498727,45.0005307 L24.5256545,38.3047153 L22.6663504,36.7831394 Z"/></g></svg>'
							+ '</div>'
						+ '</div>'
					+ '</div>'
					+ '<div class="crm-webform-ed-choise-notice">' + this.mess.dlgEditorChoiseNotice + '</div>'
				+ '</div>',
			buttons: [
				new BX.UI.SaveButton({
					text: this.mess.dlgEditorChoiseBtnApply,
					events: {
						click: function ()
						{
							this.setState(BX.UI.Button.State.WAITING);
							BX.ajax.runAction('crm.api.form.setEditor', {json: {editorId: editorValue}})
								.then(function () {
									if (parseInt(editorValue) === parseInt(editorChoise.new))
									{
										top.location.href = editorChoise.newLink;
									}
									else
									{
										this.setState(null);
										this.context.close();
									}
								}.bind(this))
								.catch(function () {
									this.setState(null);
								}.bind(this))
						}
					}
				}),
				new BX.UI.CancelButton({
					text: this.mess.dlgEditorChoiseBtnHoldOver,
					events: {
						click: function ()
						{
							this.context.close();
						}
					}
				})
			]
		});
		editorChoise.popup.show();

		var nodeNew = BX('crm-webform-editor-choise-new');
		var nodeOld = BX('crm-webform-editor-choise-old');
		var setEditor = function (val)
		{
			editorValue = parseInt(val);
			if (editorValue === parseInt(editorChoise.new))
			{
				nodeNew.classList.add('selected');
				nodeOld.classList.remove('selected');
			}
			else
			{
				nodeOld.classList.add('selected');
				nodeNew.classList.remove('selected');
			}
		};
		nodeNew.onclick = setEditor.bind(this, editorChoise.new);
		nodeOld.onclick = setEditor.bind(this, editorChoise.old);
	};

	this.initSlider = function()
	{
		if (!this.isFrame)
		{
			return;
		}

		if (this.isSaved)
		{
			var slider = BX.SidePanel.Instance.getSliderByWindow(window);
			if(slider)
			{
				slider.getData().set('formId', this.id);
			}
			BX.SidePanel.Instance.close(false,
				this.reloadList
					?
					function () {
						window.top.location.reload();
					}
					:
					null
			);
		}

		this.slider.bindClose(BX('CRM_WEBFORM_EDIT_TO_LIST'));
		this.slider.bindClose(BX('CRM_WEBFORM_EDIT_TO_LIST_BOTTOM'));
	};

	this.slider = {
		bindClose: function (element)
		{
			BX.bind(element, 'click', this.close);
		},
		close: function (e)
		{
			e.preventDefault();
			BX.SidePanel.Instance.close();
		},
		open: function (url)
		{
			BX.SidePanel.Instance.open(url);
		}
	};

	this.redirectToUrl = function(url)
	{
		if (!this.isFrame)
		{
			window.location.href = url;
		}
		else
		{
			this.slider.open(url);
		}
	};

	this.initExistedFields = function(params)
	{
		if(!params.fields)
		{
			return;
		}

		params.fields.forEach(function(fieldParams)
		{
			var field = {
				node: BX(fieldParams.CODE),
				name: fieldParams.CODE,
				type: fieldParams.TYPE,
				items: fieldParams.ITEMS || [],
				entity: fieldParams.ENTITY_NAME || '',
				settingsData: fieldParams.SETTINGS_DATA || [],
				getCaption: this.onGetFieldCaption,
				captionValue: fieldParams.CAPTION,
				isRequired: fieldParams.REQUIRED
			};
			this.initField(field);
			this.initFieldSettings(field);
		}, this);
	};

	this.initInterfaceLicence = function()
	{
		var container = BX('LICENCE_CONTAINER');

		// init licence button text live-edititng
		this.bindEditInline(container, 'dynamic-field');

		var enableNode = BX('USE_LICENCE');
		BX.bind(enableNode, 'click', function(){
			if (enableNode.checked)
			{
				BX.removeClass(container, 'crm-webform-display-none');
			}
			else
			{
				BX.addClass(container, 'crm-webform-display-none');
			}
		});

		if (BX.Helper)
		{
			var helpLinkNode = container.querySelector('a[href*="5791365"]');
			if (helpLinkNode)
			{
				BX.bind(helpLinkNode, 'click', function (e) {
					BX.Helper.show('redirect=detail&HD_ID=5791365');
					e.preventDefault();
				});
			}
		}
		//container

	};

	this.initInterface = function()
	{
		// init caption live-edititng
		this.bindEditInline(BX('CAPTION_CONTAINER'), 'dynamic-field');

		// init licence
		this.initInterfaceLicence();

		//checkboxes
		BX.bind(BX('USE_CSS_TEXT'), 'click', function(){
			BX.toggleClass(BX('CSS_TEXT_CONTAINER'), 'crm-webform-display-none');
		});
		BX.bind(BX('USE_RESULT_SUCCESS_TEXT'), 'click', function(){
			BX.toggleClass(BX('RESULT_SUCCESS_TEXT_CONT'), 'crm-webform-display-none');
		});

		var resultRedirectDelayHandler = function () {
			var visibilityClass = 'crm-webform-display-none';
			var isVisible = false;
			if (!BX.hasClass(BX('RESULT_FAILURE_URL_CONT'), visibilityClass))
			{
				isVisible = true;
			}
			if (!BX.hasClass(BX('RESULT_SUCCESS_URL_CONT'), visibilityClass))
			{
				isVisible = true;
			}

			var cont = BX('RESULT_REDIRECT_DELAY_CONTAINER');
			if (isVisible)
			{
				BX.removeClass(cont, visibilityClass);
			}
			else
			{
				BX.addClass(cont, visibilityClass);
			}
		};
		resultRedirectDelayHandler();
		BX.bind(BX('USE_RESULT_SUCCESS_URL'), 'click', function(){
			BX.toggleClass(BX('RESULT_SUCCESS_URL_CONT'), 'crm-webform-display-none');
			resultRedirectDelayHandler();
		});
		BX.bind(BX('USE_RESULT_FAILURE_TEXT'), 'click', function(){
			BX.toggleClass(BX('RESULT_FAILURE_TEXT_CONT'), 'crm-webform-display-none');
		});
		BX.bind(BX('USE_RESULT_FAILURE_URL'), 'click', function(){
			BX.toggleClass(BX('RESULT_FAILURE_URL_CONT'), 'crm-webform-display-none');
			resultRedirectDelayHandler();
		});

		BX.bind(BX('IS_PAY'), 'click', function(){
			BX.toggleClass(BX('PAY_SYSTEM_CONT'), 'crm-webform-display-none');
		});

		BX.bind(BX('IS_CALLBACK_FORM'), 'click', function(){
			BX.toggleClass(BX('CALLBACK_CONTAINER'), 'crm-webform-display-none');
		});

		BX.bind(BX('DESCRIPTION_EDITOR_BUTTON'), 'click', function(){
			BX.toggleClass(BX('DESCRIPTION_EDITOR_BUTTON'), 'crm-webform-display-none');
			BX.toggleClass(BX('DESCRIPTION_EDITOR_CONTAINER'), 'crm-webform-edit-animate-show');
			var htmlEditor = BXHtmlEditor.Get('DESCRIPTION');
			if(htmlEditor)
			{
				htmlEditor.Focus();
			}
		});

		if(!this.canRemoveCopyright)
		{
			BX.bind(BX('COPYRIGHT_REMOVED_CONT'), 'click', BX.proxy(function(e) {
				BX.PreventDefault(e);
				this.showRestrictionPopup();
			}, this));
		}

		var copyButton = BX('CRM_WEBFORM_COPY_BUTTON');
		if(copyButton)
		{
			BX.bind(copyButton, 'click', BX.proxy(function(e){

				BX.ajax({
					url: this.actionRequestUrl,
					method: 'POST',
					data: {
						'action': 'copy',
						'form_id': this.id,
						'sessid': BX.bitrix_sessid()
					},
					timeout: 30,
					dataType: 'json',
					processData: true,
					onsuccess: BX.proxy(function(data){
						data = data || {};
						if(data.error)
						{
							//error
						}
						else
						{
							this.redirectToUrl(
								this.detailPageUrlTemplate
									.replace('#id#', data.copiedId)
									.replace('#form_id#', data.copiedId)
							);
						}
					}, this),
					onfailure: BX.proxy(function(){
						//error
					}, this)
				});

			}, this));
		}

		if (this.isAvailableDesign)
		{
			BX.bind(BX('crm-webform-edit-design-btn'), 'click', function () {
				BX.SidePanel.Instance.open(this.designPageUrl, {
					cacheable: false,
					width: 1050,
				});
			}.bind(this));
		}
	};

	this.submitForm = function()
	{
		BX('crm_webform_edit_form').submit();
	};

	this.initAnimation = function()
	{
		var elementList = document.getElementsByClassName('.crm-webform-edit-animate');
		elementList = BX.convert.nodeListToArray(elementList);
		elementList.forEach(function(element){
			//get element height and set as max-height
		}, this);
	};

	this.bindEditInline = function(node, className)
	{
		var nameContainerNode = node.querySelector('[data-bx-web-form-lbl-cont]');
		var captionNode = node.querySelector('[data-bx-web-form-lbl-caption]');
		var inputNode = node.querySelector('[data-bx-web-form-btn-caption]');
		var buttonEdit = node.querySelector('[data-bx-web-form-lbl-btn-edit]');
		var buttonApply = node.querySelector('[data-bx-web-form-lbl-btn-apply]');
		BX.bind(buttonEdit, 'click', function(){
			BX.addClass(nameContainerNode, className);
		});
		BX.bind(buttonApply, 'click', function(){
			captionNode.innerText = inputNode.value;
			BX.removeClass(nameContainerNode, className);
		});
	};

	this.sendActionRequest = function (action, requestData, callbackSuccess, callbackFailure)
	{
		callbackSuccess = callbackSuccess || null;
		callbackFailure = callbackFailure || null;
		requestData = requestData || {};

		requestData.action = action;
		requestData.form_id = null;
		requestData.sessid = BX.bitrix_sessid();

		BX.ajax({
			url: this.actionRequestUrl,
			method: 'POST',
			data: requestData,
			timeout: 30,
			dataType: 'json',
			processData: true,
			onsuccess: BX.proxy(function(data){
				data = data || {};
				if(data.error)
				{
					callbackFailure.apply(this, [data]);
				}
				else if(callbackSuccess)
				{
					callbackSuccess.apply(this, [data]);
				}
			}, this),
			onfailure: BX.proxy(function(){
				var data = {'error': true, 'text': ''};
				callbackFailure.apply(this, [data]);
			}, this)
		});
	};

	this.onGetFieldCaption = function()
	{
		var inputs = document.getElementsByName('FIELD['+this.name+'][CAPTION]');
		return (inputs && inputs[0]) ? inputs[0].value : '';
	};

	this.getFieldNameList = function()
	{
		var fieldNameList = [];
		this.fields.forEach(function(field){
			fieldNameList.push(field.name);
		});

		return fieldNameList;
	};

	this.fireChangeFieldListEvent = function ()
	{
		BX.onCustomEvent(this, 'change-field-list', [this.fields]);
	};

	this.fireFieldChangeItemsEvent = function (field)
	{
		BX.onCustomEvent(field, 'change-field-items');
		BX.onCustomEvent(this, 'change-field-items', [this.fields, field]);
	};

	this.addFieldByCode = function (fieldCode)
	{
		var dataList = this.fieldsDictionary.filter(function(item){
				return item.name == fieldCode;
		});

		if(dataList[0])
		{
			this.addField(dataList[0]);
		}
	};


	this.addField = function (params, doNotSort)
	{
		if(!params.duplicated && this.findField(params.name))
		{
			return;
		}

		var templateId = this.templates.field.replace('%type%', params.type);
		if(!this.helper.getTemplate(templateId))
		{
			templateId = this.templates.field.replace('%type%', 'string');
		}

		var newFieldStringType = null;
		var newFieldPlaceholder = null;
		switch (params.entity_field_name)
		{
			case 'PHONE':
				newFieldStringType = 'phone';
				newFieldPlaceholder = '111-11-11';
				break;
			case 'WEB':
				newFieldPlaceholder = 'http://';
				break;
			case 'EMAIL':
				newFieldStringType = 'email';
				newFieldPlaceholder = 'my@example.com';
				break;
		}

		params.entity_field_name = params.entity_field_name || '';

		var fieldNode = this.helper.appendNodeByTemplate(
			BX('FIELD_CONTAINER'),
			templateId,
			{
				'id': params.id || '',
				'type': newFieldStringType || params.type,
				'name': params.name,
				'caption': params.caption,
				'placeholder': newFieldPlaceholder || '',
				'multiple': 'N',
				'required': 'N',
				'sort': 1000,
				'value_type': '',
				'value': '',
				'items': '',
				'settings_items': '',
				'settings_data_quantity_min': '',
				'settings_data_quantity_max': '',
				'settings_data_quantity_step': '',
				'url_display_style': params.entity_field_name.substring(0, 3) == 'UF_' ? 'initial' : 'none',
				'entity_field_name': params.entity_field_name,
				'entity_field_caption': params.caption,
				'entity_name': params.entity_name,
				'entity_caption': params.entity_caption
			}
		);

		if(!fieldNode)
		{
			return;
		}

		var field = {
			type: params.type,
			node: fieldNode,
			name: params.name,
			items: params.items || [],
			entity: params.entity_name,
			getCaption: this.onGetFieldCaption
		};

		this.initField(field);
		this.addFieldItems(field);
		this.addFieldSettingsItems(field);
		this.initFieldSettings(field);

		if(!doNotSort)
		{
			this.sortFields();
			this.fireChangeFieldListEvent();
		}

		return field;
	};

	this.addFieldItems = function(field, clean)
	{
		clean = clean || false;
		if(field.items.length == 0 || !field.itemsContainer)
		{
			return;
		}

		if(clean)
		{
			field.itemsContainer.innerHTML = '';
		}

		var templateId = this.templates.field.replace('%type%', field.type);
		var itemTemplateId = templateId + '_item';

		field.items.forEach(function(item){

			var fieldItemNode = this.helper.appendNodeByTemplate(
				field.itemsContainer,
				itemTemplateId,
				{
					'item_id': item.ID,
					'item_value': item.VALUE,
					'field_item_name': field.type + '_' + field.name + '_' + field.randomId + '[]',
					'field_item_id': field.type + '_' + item.ID + this.helper.generateId()
				}
			);

		}, this);
	};

	this.initField = function(field)
	{
		if(!field.node)
		{
			return;
		}

		var dictField = this.findDictionaryField(field.name);
		if(!dictField)
		{
			if (field.type === 'product')
			{
				dictField = {
					code: field.name,
					type: field.type,
					required: false,
					multiple: true
				};
			}
			else
			{
				dictField = {
					code: field.name,
					type: field.type,
					required: false,
					multiple: false
				};
			}
		}
		field.dict = dictField;

		field.randomId = this.helper.generateId();

		var nameInput = field.node.querySelector('[data-bx-web-form-btn-caption]');
		var editButton = field.node.querySelector('[data-bx-web-form-btn-edit]');
		var deleteButton = field.node.querySelector('[data-bx-web-form-btn-delete]');
		field.lblCaption = field.node.querySelector('[data-bx-web-form-lbl-caption]');

		field.itemsContainer = field.node.querySelector('[data-bx-web-form-field-display-cont]');
		field.settingsContainer = field.node.querySelector('[data-bx-web-form-field-settings-cont]');

		var _this = this;
		BX.bind(nameInput, 'change', function(){
			field.lblCaption.innerText = nameInput.value;
			_this.fireChangeFieldListEvent();
		});
		BX.bind(editButton, 'click', function(){
			_this.editField(field);
		});
		BX.bind(deleteButton, 'click', function(){
			_this.deleteField(field);
		});

		this.dragdrop.addItem(field.node);
		this.fields.push(field);

		BX.addCustomEvent(field, 'change-field-items', BX.proxy(function(){
			var firstChild = null;
			if(field.type == 'product')
			{
				firstChild = field.itemsContainer.children[0];
			}
			field.itemsContainer.innerHTML = '';
			if(firstChild)
			{
				field.itemsContainer.appendChild(firstChild);
			}
			this.addFieldItems(field);
		}, this));
	};

	this.initFieldSettings = function(field)
	{
		if(!field.node)
		{
			return;
		}

		var typeNode = field.node.querySelector('[data-bx-web-form-field-type]');
		var multipleValueNode = field.node.querySelector('[data-bx-web-form-btn-multiple-value]');
		var multipleCheckboxNode = field.node.querySelector('[data-bx-web-form-btn-multiple]');
		var multipleNode = field.node.querySelector('[data-bx-web-form-btn-multiple-cont]');
		var multipleAddNode = field.node.querySelector('[data-bx-web-form-btn-add]');
		var requiredValueNode = field.node.querySelector('[data-bx-web-form-btn-required-value]');
		var requiredCheckboxNode = field.node.querySelector('[data-bx-web-form-btn-required]');
		var bigPicValueNode = field.node.querySelector('[data-bx-web-form-btn-big-pic-value]');
		var bigPicCheckboxNode = field.node.querySelector('[data-bx-web-form-btn-big-pic]');
		if(multipleNode && multipleValueNode && multipleCheckboxNode)
		{
			if(!field.dict.multiple)
			{
				this.helper.styleDisplay(multipleNode, false);
				multipleValueNode.value = 'N';
			}
			else
			{
				multipleCheckboxNode.checked = multipleValueNode.value == 'Y';

				if(field.dict.type === 'checkbox')
				{
					var clickHandler = function(){
						multipleValueNode.value = multipleCheckboxNode.checked ? 'Y' : 'N';
						typeNode.value = field.type = multipleCheckboxNode.checked ? 'checkbox' : 'radio';
						this.addFieldItems(field, true);
					};
					BX.bind(multipleCheckboxNode, 'click', BX.proxy(clickHandler, this));
					clickHandler.apply(this, []);
				}
				else
				{
					this.helper.styleDisplay(multipleAddNode, multipleCheckboxNode.checked, 'inline-block');
					BX.bind(multipleCheckboxNode, 'click', BX.proxy(function(){
						multipleValueNode.value = multipleCheckboxNode.checked ? 'Y' : 'N';
						this.helper.styleDisplay(multipleAddNode, multipleCheckboxNode.checked, 'inline-block');
					}, this));
				}
			}
		}
		if(requiredValueNode && requiredCheckboxNode)
		{
			if(requiredValueNode.value === 'Y')
			{
				requiredCheckboxNode.checked = true;
			}
			BX.bind(requiredCheckboxNode, 'change', function(){
				requiredValueNode.value = requiredCheckboxNode.checked ? 'Y' : 'N';
			});
		}
		if(bigPicValueNode && bigPicCheckboxNode)
		{
			if(bigPicValueNode.value === 'Y')
			{
				bigPicCheckboxNode.checked = true;
			}
			BX.bind(bigPicCheckboxNode, 'change', function(){
				bigPicValueNode.value = bigPicCheckboxNode.checked ? 'Y' : 'N';
			});
		}

		var fieldType = field.dict['type'] ? field.dict.type : field.type;
		var fieldInitMethod = 'initFieldType' + fieldType.substring(0,1).toUpperCase() + fieldType.substring(1);

		if(BX.type.isFunction(this[fieldInitMethod]))
		{
			this[fieldInitMethod](field);
		}
	};

	this.addFieldSettingsItems = function(field)
	{
		var settingsItemsContainer = field.node.querySelector('[data-bx-crm-webform-field-settings-items]');
		if(!settingsItemsContainer || field.items.length == 0 || !field.itemsContainer)
		{
			return;
		}


		 var templateId = this.templates.field.replace('%type%', field.type);
		 var itemSettingsTemplateId = templateId + '_settings_item';

		 field.items.forEach(function(item){

			 var fieldItemNode = this.helper.appendNodeByTemplate(
				 settingsItemsContainer,
				 itemSettingsTemplateId,
				 {
					 'name': field.name,
					 'item_id': item.ID,
					 'item_value': item.VALUE,
					 'field_item_name': field.type + '_' + field.name + '_' + field.randomId + '[]',
					 'field_item_id': field.type + '_' + item.ID + this.helper.generateId()
				 }
			 );

		 }, this);
	};

	this.initFieldTypeProduct = function(field)
	{
		field.productSelector = new CrmFormEditorProductSelector({
			jsEventsManagerId: this.jsEventsManagerId,
			context: field.node,
			caller: this,
			id: field.id,
			field: field
		});
	};

	this.initFieldTypeCheckbox = function(field)
	{
		this.initFieldTypeList(field);
	};

	this.initFieldTypeRadio = function(field)
	{
		this.initFieldTypeList(field);
	};

	this.initFieldTypeList = function(field)
	{
		field.fieldListManager = new CrmFormEditorFieldListSettings({
			caller: this,
			context: field.node,
			type: field.type,
			field: field
		});
	};

	this.initFieldTypeSection = function(field)
	{
		this.bindEditInline(field.node, 'dynamic-field');
	};

	this.initFieldTypePage = function(field)
	{
		this.bindEditInline(field.node, 'dynamic-field');
	};

	this.initFieldTypeString = function(field)
	{
		var stringTypeNode = field.node.querySelector('[data-bx-web-form-field-string-type]');
		var typeNode = field.node.querySelector('[data-bx-web-form-field-type]');
		if(stringTypeNode)
		{
			stringTypeNode.value = typeNode.value;
			BX.bind(stringTypeNode, 'bxchange', function(){
				typeNode.value = stringTypeNode.value || 'string';
			});
		}
	};

	this.initFieldTypeTyped_string = function(field)
	{
		this.initFieldTypeString(field);

		if(!field.dict['value_type'])
		{
			return;
		}

		var valueTypesNode = field.node.querySelector('[data-bx-web-form-field-string-value-types]');
		var valueTypeNode = field.node.querySelector('[data-bx-web-form-field-string-value-type]');
		if(valueTypesNode)
		{
			var currentItemId = valueTypeNode.value;
			var items = [];
			field.dict.value_type.forEach(function(item){
				if(!currentItemId)
				{
					currentItemId = item.ID;
				}
				items.push({
					caption: item.VALUE,
					value: item.ID,
					selected: (item.ID == currentItemId)
				});
			}, this);
			this.helper.fillDropDownControl(valueTypesNode, items);
			valueTypeNode.value = currentItemId;

			BX.bind(valueTypesNode, 'bxchange', function(){
				valueTypeNode.value = valueTypesNode.value || 'OTHER';
			});
		}
	};

	this.initFieldTypeDouble = function(field)
	{
		this.initFieldTypeString(field);
	};

	this.initFieldTypeInteger = function(field)
	{
		this.initFieldTypeString(field);
	};

	this.findDictionaryField = function(fieldName)
	{
		var fieldsList = this.fieldsDictionary.filter(function(field){
			return (field.name === fieldName);
		});

		if(fieldsList && fieldsList.length > 0)
		{
			return fieldsList[0];
		}

		return null;
	};

	this.initFieldTypeResourcebooking = function(field)
	{
		BX.Runtime.loadExtension('calendar.resourcebookinguserfield').then(function(exports)
		{
			var ResourcebookingUserfield = exports.ResourcebookingUserfield;
			if (BX.type.isFunction(ResourcebookingUserfield))
			{
				var editFieldController = ResourcebookingUserfield.initCrmFormFieldController({field: field});
				field.edit = editFieldController.showSettingsPopup.bind(editFieldController);
			}
			else if (BX.Calendar && BX.Calendar.UserField
				&& BX.type.isFunction(BX.Calendar.UserField.getResourceBookingCrmFormField))
			{
				var resourcebookingField = new BX.Calendar.UserField.getResourceBookingCrmFormField({field: field});
				if (resourcebookingField)
				{
					resourcebookingField.init();
				}
				field.edit = resourcebookingField.showSettingsPopup.bind(resourcebookingField);
			}
		});
	};

	this.findField = function(fieldName)
	{
		var fieldsList = this.fields.filter(function(field){
			return (field.name === fieldName);
		});

		if(fieldsList && fieldsList.length > 0)
		{
			return fieldsList[0];
		}

		return null;
	};

	this.findFieldByNode = function(node)
	{
		var fieldsList = this.fields.filter(function(field){
			return (field.node == node);
		});

		if(fieldsList && fieldsList.length > 0)
		{
			return fieldsList[0];
		}

		return null;
	};

	this.editField = function(field, startEdit)
	{
		// Custom handler for edit settings (for example: resourcebooking UF)
		if (field && BX.type.isFunction(field.edit))
		{
			field.edit(field);
		}
		else
		{
			this.popupFieldSettings.show(field.settingsContainer);
		}
		BX.onCustomEvent(this, 'edit-field', [field]);
	};

	this.deleteField = function(field)
	{
		BX.onCustomEvent(this, 'delete-field', [field]);

		this.dragdrop.removeItem(field.node);
		BX.remove(field.node);

		var itemIndex = this.fields.indexOf(field);
		if(itemIndex > -1)
		{
			delete this.fields[itemIndex];
		}

		this.sortFields();
		this.fireChangeFieldListEvent();
	};

	this.moveField = function(field, insertAfterField)
	{
		this.sortFields();
	};

	this.sortFields = function ()
	{
		this.fields.forEach(function(field){
			field.sortValue = BX.convert.nodeListToArray(field.node.parentNode.children).indexOf(field.node);
		});

		this.fields.sort(function(fieldA, fieldB){
			return fieldA.sortValue > fieldB.sortValue ? 1 : -1;
		});

		var weight = 10;
		this.fields.forEach(function(field){
			var sortInput = field.node.querySelector('[data-bx-web-form-field-sort]');
			if(sortInput)
			{
				sortInput.value = (field.sortValue + 1) * weight;
			}
		});
	};

	this.onSort = function(dragElement, catcherObj)
	{
		//catcherObj.appendChild(dragElement);
		this.sortFields();
	};

	this.initToolTips = function(nodeList)
	{
		this.popupTooltip = {};
		nodeList = nodeList || BX.convert.nodeListToArray(document.body.querySelectorAll(".crm-webform-context-help"));
		var _this = this;
		for (var i = 0; i < nodeList.length; i++)
		{
			if (nodeList[i].getAttribute('context-help') == 'y')
				continue;

			nodeList[i].setAttribute('data-id', i);
			nodeList[i].setAttribute('context-help', 'y');
			BX.bind(nodeList[i], 'mouseover', function(){
				var id = this.getAttribute('data-id');
				var text = this.getAttribute('data-text');

				_this.showTooltip(id, this, text);
			});
			BX.bind(nodeList[i], 'mouseout', function(){
				var id = this.getAttribute('data-id');
				_this.hideTooltip(id);
			});
		}
	};

	this.showTooltip = function(id, bind, text)
	{
		if (this.popupTooltip[id])
			this.popupTooltip[id].close();

		this.popupTooltip[id] = new BX.PopupWindow('bx-crm-webform-edit-tooltip', bind, {
			lightShadow: true,
			autoHide: false,
			darkMode: true,
			offsetLeft: 0,
			offsetTop: 2,
			bindOptions: {position: "top"},
			zIndex: 200,
			events : {
				onPopupClose : function() {this.destroy()}
			},
			content : BX.create("div", { attrs : { style : "padding-right: 5px; width: 250px;" }, html: text})
		});
		this.popupTooltip[id].setAngle({offset:13, position: 'bottom'});
		this.popupTooltip[id].show();

		return true;
	};

	this.hideTooltip = function(id)
	{
		this.popupTooltip[id].close();
		this.popupTooltip[id] = null;
	};

	this.init(params);
};


function CrmWebFormEditEntityScheme(params)
{
	this.caller = params.caller;
	this.context = params.context;
	this.submitButtonEnabled = true;

	this.init();
}
CrmWebFormEditEntityScheme.prototype =
{
	init: function ()
	{
		this.helper = BX.CrmFormEditorHelper;
		this.descriptionNode = this.context.querySelector('[data-bx-webform-edit-scheme-desc]');
		this.descriptionTopNode = BX('ENTITY_SCHEMES_TOP_DESCRIPTION');
		this.radioList = BX.convert.nodeListToArray(this.context.querySelectorAll('[data-bx-web-form-entity-scheme-value]'));
		this.invoiceCheckNode = this.context.querySelector('[data-bx-web-form-entity-scheme-invoice]');
		this.dealCategoryNode = this.context.querySelector('[data-bx-web-form-entity-scheme-deal-cat]');
		this.dynamicCategoryNode = this.context.querySelector('[data-bx-web-form-entity-scheme-dyn-cat]');
		this.dealDcNode = this.context.querySelector('[data-bx-web-form-entity-scheme-deal-dc]');

		var valueNodes = document.getElementsByName('ENTITY_SCHEME');
		this.valueNode = valueNodes[0];

		BX.bind(this.invoiceCheckNode, 'change', BX.proxy(this.onChange, this));
		this.radioList.forEach(function(schemeNode){
			BX.bind(schemeNode, 'change', BX.proxy(this.onChange, this));
		}, this);

		this.submitButtonNode = BX('CRM_WEBFORM_SUBMIT_APPLY');
		BX.bind(this.submitButtonNode, 'click', BX.proxy(this.checkCreateFields, this));
		this.initInvoiceBlock();

		this.actualizeDynamicCategory();
	},

	initInvoiceBlock: function ()
	{
		this.invoice = {
			node: this.context.querySelector('[data-bx-crm-webform-invoice]'),
			payerNode: this.context.querySelector('[data-bx-crm-webform-invoice-payer]'),
			payerTextNoNode: this.context.querySelector('[data-bx-crm-webform-invoice-payer-text-no]'),
			productNode: this.context.querySelector('[data-bx-crm-webform-invoice-product]'),
			productButton: this.context.querySelector('[data-bx-crm-webform-invoice-product-btn]'),
			productDraw: this.context.querySelector('[data-bx-crm-webform-invoice-product-draw]'),
			productField: null,
			payerTypeNodes: BX.convert.nodeListToArray(document.getElementsByName('INVOICE_SETTINGS[PAYER]')),
			hasFieldsContact: false,
			hasFieldsCompany: false
		};

		BX.bind(this.invoice.productButton, 'click', BX.proxy(function(){
			this.actualizeInvoiceProductField(true);
			if(this.invoice.productField)
			{
				this.caller.editField(this.invoice.productField);
			}
		}, this));

		BX.addCustomEvent(this.caller, 'change-field-list', BX.proxy(this.actualizeInvoiceSettings, this));
		this.invoice.payerTypeNodes.forEach(function(payerTypeNode){
			BX.bind(payerTypeNode, 'change', BX.proxy(this.onChange, this));
		}, this);


		this.actualizeInvoiceSettings();
	},

	isInvoiceChecked: function ()
	{
		return this.invoiceCheckNode.checked;
	},

	showPopupCreateProductField: function ()
	{
		if(!this.popupCreateProductField)
		{
			this.popupCreateProductField = BX.PopupWindowManager.create(
				'crm_webform_edit_create_prod_field',
				null,
				{
					content: this.caller.mess.dlgInvoiceEmptyProduct,
					titleBar: this.caller.mess.dlgInvoiceEmptyProductTitle,
					autoHide: false,
					lightShadow: true,
					closeByEsc: true,
					overlay: {backgroundColor: 'black', opacity: 500}
				}
			);
			this.popupCreateProductField.setButtons([
				new BX.PopupWindowButton({
					text: this.caller.mess.dlgContinue,
					className: 'webform-small-button-accept',
					events: {
						click: BX.proxy(function(){
							this.popupCreateProductField.close();
						}, this)
					}
				})
			]);
		}

		this.popupCreateProductField.show();
		this.popupCreateProductField.resizeOverlay();
	},

	showPopupCreateFields: function ()
	{
		if(!this.popupConfirmCreateFields)
		{
			this.popupConfirmCreateFields = BX.PopupWindowManager.create(
				'crm_webform_edit_create_fields',
				null,
				{
					titleBar: this.caller.mess.dlgTitleFieldCreate,
					content: BX('CRM_WEB_FORM_POPUP_CONFIRM_FIELD_CREATE'),
					autoHide: false,
					lightShadow: true,
					closeByEsc: true,
					overlay: {backgroundColor: 'black', opacity: 500},
					onPopupClose: BX.proxy(this.onClose)
				}
			);
			this.popupConfirmCreateFields.setButtons([
				new BX.PopupWindowButton({
					text: this.caller.mess.dlgContinue,
					className: 'webform-small-button-accept',
					events: {
						click: BX.proxy(function(){
							this.caller.submitForm();
						}, this)
					}
				}),
				new BX.PopupWindowButton({
					text: this.caller.mess.dlgCancel,
					className: 'webform-small-button-cancel',
					events: {
						click: BX.proxy(function(){
							this.submitButtonEnabled = true;
							BX.removeClass(this.submitButtonNode, 'ui-btn-wait');
							this.popupConfirmCreateFields.close();
						}, this)
					}
				})
			]);
		}

		this.popupConfirmCreateFields.show();
	},

	checkCreateFields: function (e)
	{
		if(!this.submitButtonEnabled)
		{
			BX.PreventDefault(e);
			return false;
		}

		var fieldCaptionList = [];
		var currentScheme = this.getCurrent();

		var hasProductFieldNotEmpty = false;
		var fieldCodes = [];
		var fieldCodeToCaption = {};
		this.caller.fields.forEach(function(field){
			if(field.type == 'product' && field.items.length > 0)
			{
				hasProductFieldNotEmpty = true;
			}

			if(!field.dict['caption'])
			{
				return;
			}
			if(!field.dict['entity_name'])
			{
				return;
			}
			fieldCodes.push(field.name);
			fieldCodeToCaption[field.name] = field.dict.caption;
			if(BX.util.in_array(field.dict.entity_name, currentScheme.ENTITIES))
			{
				return;
			}
			fieldCaptionList.push(
				'<li class="crm-p-s-f-item">' + BX.util.htmlspecialchars(field.dict.caption) + '</li>'
			);
		}, this);


		if(this.isInvoiceChecked() && !hasProductFieldNotEmpty)
		{
			BX.PreventDefault(e);
			this.showPopupCreateProductField();
			return false;
		}


		if(fieldCaptionList.length == 0)
		{
			return true;
		}

		BX.PreventDefault(e);
		this.submitButtonEnabled = false;
		BX.addClass(this.submitButtonNode, 'crm-webform-submit-button-loader');

		var popupEntityCaptionCont = BX('CRM_WEB_FORM_POPUP_CONFIRM_FIELD_CREATE_ENTITY');
		if(currentScheme.ID == 2 || currentScheme.ID == 5)
		{
			if(this.invoice.hasFieldsContact)
			{
				popupEntityCaptionCont.innerText = this.caller.entityDictionary['CONTACT'];
			}
			else
			{
				popupEntityCaptionCont.innerText = this.caller.entityDictionary['COMPANY'];
			}
		}
		else
		{
			popupEntityCaptionCont.innerText = currentScheme.NAME;
		}

		var popupFieldListCont = BX('CRM_WEB_FORM_POPUP_CONFIRM_FIELD_CREATE_LIST');
		this.caller.sendActionRequest(
			'get_create_fields',
			{
				'schemeId': currentScheme.ID,
				'fieldCodes': fieldCodes
			},
			BX.proxy(function(data){
				data = data || {};

				var fieldSyncCaptionList = [];
				var syncFieldCodes = data['syncFieldCodes'] ? data.syncFieldCodes : [];

				if(syncFieldCodes.length == 0)
				{
					this.caller.submitForm();
					return;
				}

				syncFieldCodes.forEach(function(syncFieldCode){
					if(!fieldCodeToCaption[syncFieldCode])
					{
						return;
					}

					fieldSyncCaptionList.push(
						'<li class="crm-p-s-f-item">' + BX.util.htmlspecialchars(fieldCodeToCaption[syncFieldCode]) + '</li>'
					);
				});

				popupFieldListCont.innerHTML = fieldSyncCaptionList.join('');
				this.showPopupCreateFields();
			}, this),
			BX.proxy(function()
			{
				popupFieldListCont.innerHTML = fieldCaptionList.join('');
				this.showPopupCreateFields();
			}, this)
		);

		return false;
	},

	setCurrentId: function (value)
	{
		if(this.valueNode)
		{
			this.valueNode.value = value;
		}
	},


	getCurrentPayerEntityType: function ()
	{
		return this._currentPayerEntityType();
	},

	setCurrentPayerEntityType: function (value)
	{
		return this._currentPayerEntityType(value);
	},


	_currentPayerEntityType: function (value)
	{
		value = value || null;
		this.invoice.payerTypeNodes.forEach(function(node){
			if(value)
			{
				node.checked = value == node.value;
			}
			else if(node.checked)
			{
				value = node.value;
			}
		}, this);

		return value;
	},

	getCurrent: function ()
	{
		var section = this.invoiceCheckNode.checked ? 'BY_INVOICE' : 'BY_NON_INVOICE';
		var schemeId = null;
		this.radioList.forEach(function(radioNode){

			if(radioNode.checked)
			{
				schemeId = radioNode.value;
			}
		});
		return this.caller.schemesDictionary[section][schemeId];
	},

	onChange: function ()
	{
		var scheme = this.getCurrent();
		this.setCurrentId(scheme.ID);
		this.actualizeInvoiceSettings(scheme);
		this.actualizeDealCategory(scheme);
		this.actualizeDynamicCategory(scheme);

		BX.onCustomEvent(this.caller, 'change-entity-scheme', [scheme]);
	},

	getCurrentWillCreatedEntities: function()
	{
		var scheme = this.getCurrent();
		var entityTypes = [];
		var currentPayerEntityTypeName = this.getCurrentPayerEntityType();
		scheme.ENTITIES.forEach(function(entityTypeName){

			var isPayer = currentPayerEntityTypeName == entityTypeName;
			var isContact = entityTypeName == 'CONTACT';
			var isCompany = entityTypeName == 'COMPANY';

			if(isContact && !this.invoice.hasFieldsContact && !isPayer)
			{
				return;
			}
			if(isCompany && !this.invoice.hasFieldsCompany && !isPayer)
			{
				return;
			}

			if(!this.caller.entityDictionary[entityTypeName])
			{
				return;
			}

			entityTypes.push(entityTypeName);
		}, this);

		return entityTypes;
	},

	actualizeDealCategory: function()
	{
		var scheme = this.getCurrent();
		var isAdd = BX.util.in_array('DEAL', scheme.ENTITIES);
		this.helper.changeClass(this.dealCategoryNode, 'crm-webform-edit-animate-show-120', isAdd);
		this.helper.changeClass(this.dealDcNode, 'crm-webform-edit-animate-show-120', isAdd);
		//this.helper.styleDisplay(this.dealCategoryNode, BX.util.in_array('DEAL', scheme.ENTITIES));
	},

	actualizeDynamicCategory: function()
	{
		var scheme = this.getCurrent();
		var isAdd = scheme.DYNAMIC;
		this.helper.changeClass(this.dynamicCategoryNode, 'crm-webform-edit-animate-show-120', isAdd);
		if (!isAdd)
		{
			return;
		}

		var entity = this.caller.dynamicEntities.filter(function (entity) {
			return entity.id === scheme.MAIN_ENTITY;
		})[0];
		if (!entity)
		{
			return;
		}

		var select = BX('DYNAMIC_CATEGORY');
		var selectedCatId = parseInt(select.value || 0);
		select.innerHTML = '';
		entity.categories.forEach(function (category) {
			var option = document.createElement('option');
			option.value = category.id;
			option.selected = parseInt(category.id) === selectedCatId;
			option.textContent = category.name;
			select.appendChild(option);
		}.bind(this));
	},

	actualizeTextWillCreatedEntities: function()
	{
		var entityTypeCaptions = [];
		this.getCurrentWillCreatedEntities().forEach(function(entityTypeName){
			entityTypeCaptions.push(this.caller.entityDictionary[entityTypeName]);
		}, this);

		var scheme = this.getCurrent();
		var description = entityTypeCaptions.length > 0 ? entityTypeCaptions.join(', ') : scheme['DESCRIPTION'];
		if(this.descriptionNode)
		{
			this.descriptionNode.innerText = description;
		}
		if(this.descriptionTopNode)
		{
			this.descriptionTopNode.innerText = description;
		}
	},

	actualizeInvoiceProductField: function(isInvoiceChecked)
	{
		var productFields = this.caller.fields.filter(function(field){
			return field.type == 'product';
		}, this);

		if(productFields.length > 0 && !this.invoice.productField)
		{
			this.invoice.productField = productFields[0];
			BX.addCustomEvent(this.invoice.productField, 'change-field-items', BX.proxy(this.actualizeInvoiceSettings, this));
		}
		else if(productFields.length == 0)
		{
			if(this.invoice.productField)
			{
				this.invoice.productField = null;
			}
			else if(isInvoiceChecked)
			{
				this.invoice.productField = this.caller.fieldSelector.addSpecialFormField('product');
				BX.addCustomEvent(this.invoice.productField, 'change-field-items', BX.proxy(this.actualizeInvoiceSettings, this));
				this.invoice.productField.productSelector.addFieldItem({
					'id': 'n' + this.helper.generateId(),
					'name': this.caller.mess.defaultProductName,
					'price': 1
				});
			}
		}

		if(!isInvoiceChecked && this.invoice.productField)
		{
			//this.caller.deleteField
		}

		if(this.invoice.productDraw)
		{
			this.invoice.productDraw.innerHTML = '';

			if(this.invoice.productField)
			{
				this.invoice.productField.items.forEach(function(item){
					this.helper.appendNodeByTemplate(
						this.invoice.productDraw,
						'tmpl_field_product_items_draw',
						{
							'name': item.VALUE || '',
							'price': item.PRICE || '',
							'discount': item.DISCOUNT || '',
							'custom_price': item.CUSTOM_PRICE || ''
						}
					);
				}, this);
			}
		}

		this.helper.styleDisplay(this.invoice.productNode, isInvoiceChecked);
	},

	actualizePayer: function()
	{
		// actualize payer
		var hasFieldsContact = false, hasFieldsCompany = false;
		var clientFields = this.caller.fields.filter(function(field){
			if(!field.dict['entity_name']) return false;
			if(field.dict.entity_name == 'CONTACT') hasFieldsContact = true;
			if(field.dict.entity_name == 'COMPANY') hasFieldsCompany = true;

			return (BX.util.in_array(field.dict.entity_name, ['CONTACT', 'COMPANY']));
		}, this);

		this.invoice.hasFieldsCompany = hasFieldsCompany;
		this.invoice.hasFieldsContact = hasFieldsContact;

		var hasContactAndCompany = (hasFieldsContact && hasFieldsCompany);
		if(!hasContactAndCompany)
		{
			if(hasFieldsContact)
			{
				this.setCurrentPayerEntityType('CONTACT');
			}
			else if(hasFieldsCompany)
			{
				this.setCurrentPayerEntityType('COMPANY');
			}
		}

		var showBlock = (clientFields.length == 0 || hasContactAndCompany);
		this.helper.styleDisplay(this.invoice.payerNode, showBlock);
		this.helper.styleDisplay(this.invoice.payerTextNoNode, !hasContactAndCompany);
	},

	actualizeInvoiceSettings: function()
	{
		var scheme = this.getCurrent();
		var isInvoiceChecked = this.isInvoiceChecked();

		// show block
		this.helper.changeClass(this.invoice.node, 'crm-webform-display-none', !isInvoiceChecked);

		// actualize products
		this.actualizeInvoiceProductField(isInvoiceChecked);

		// actualize payer
		this.actualizePayer();

		// actualize description text of entities that will created
		this.actualizeTextWillCreatedEntities();
	}
};


function CrmWebFormEditFormButton(params)
{
	this.caller = params.caller;
	this.context = params.context;
	this.helper = BX.CrmFormEditorHelper;

	this.buttonNode = BX('FORM_BUTTON');
	this.buttonInputNode = BX('FORM_BUTTON_INPUT');
	this.buttonTextErrorNode = BX('FORM_BUTTON_INPUT_ERROR');
	this.buttonColorBgNode = BX('BUTTON_COLOR_BG');
	this.buttonColorFontNode = BX('BUTTON_COLOR_FONT');
	this.popupButtonNameNode = BX('CRM_WEB_FORM_POPUP_BUTTON_NAME');

	top.BX.addCustomEvent('crm-webform-design-save', this.onChangeDesign.bind(this));

	this.init();
}
CrmWebFormEditFormButton.prototype =
{
	onChangeDesign: function(design)
	{
		this.setTextColor(design.color.primaryText.substr(0, 7));
		this.setBackgroundColor(design.color.primary.substr(0, 7));
	},
	init: function()
	{
		BX.bind(this.buttonNode.parentNode, 'click', BX.proxy(this.editButton, this));
		BX.bind(this.buttonInputNode, 'bxchange', BX.proxy(this.onButtonCaptionChange, this));

		//this.initColorPicker();

		this.picker = new BX.ColorPicker({
			'popupOptions': {
				'offsetLeft': 15,
				'offsetTop': 5
			}
		});

		BX.bind(
			this.buttonColorBgNode.nextElementSibling,
			'click',
			this.showPickerBackground.bind(this)
		);
		BX.bind(
			this.buttonColorFontNode.nextElementSibling,
			'click',
			this.showPickerTextColor.bind(this)
		);

		this.setTextColor(this.buttonColorFontNode.value);
		this.setBackgroundColor(this.buttonColorBgNode.value);
	},

	editButton: function()
	{
		this.caller.popupFieldSettings.show(this.popupButtonNameNode, BX.proxy(this.onEditButtonClosePopup, this));
	},

	onEditButtonClosePopup: function()
	{
		var isFilled = !!BX.util.trim(this.buttonInputNode.value);
		this.helper.styleDisplay(this.buttonTextErrorNode, !isFilled, 'block');

		return isFilled;
	},

	onButtonCaptionChange: function()
	{
		this.buttonNode.innerText = this.buttonInputNode.value;
	},

	updateButtonColors: function()
	{
		this.buttonNode.style.background = this.buttonColorBgNode.value;
		this.buttonNode.style.color = this.buttonColorFontNode.value;
	},

	showPickerBackground: function ()
	{
		this.picker.close();
		this.picker.open({
			defaultColor: '',
			allowCustomColor: true,
			bindElement: this.buttonColorBgNode.nextElementSibling,
			onColorSelected: this.setBackgroundColor.bind(this)
		});
	},

	showPickerTextColor: function ()
	{
		this.picker.close();
		this.picker.open({
			defaultColor: '',
			allowCustomColor: true,
			bindElement: this.buttonColorFontNode.nextElementSibling,
			onColorSelected: this.setTextColor.bind(this)
		});
	},

	setBackgroundColor: function(value)
	{
		this.buttonColorBgNode.value = value;
		this.buttonColorBgNode.nextElementSibling.style.background = value;
		this.updateButtonColors();
	},

	setTextColor: function(value)
	{
		this.buttonColorFontNode.value = value;
		this.buttonColorFontNode.nextElementSibling.style.background = value;
		this.updateButtonColors();
	},
};


function CrmWebFormEditPopupFieldSettings(params)
{
	this.caller = params.caller;
	this.popup = null;
	this.popupContent = params.content;
	this.popupContainer = params.container;
	this.popupCloseHandler = null;

	this.fieldContainer = null;
}
CrmWebFormEditPopupFieldSettings.prototype =
{
	setAutoHide: function (doAutoHide)
	{
		if(this.popup)
		{
			this.popup.params.autoHide = doAutoHide;
		}
	},

	show: function (fieldContainer, popupCloseHandler)
	{
		this.popupCloseHandler = popupCloseHandler || null;

		this.initPopup();
		this.moveSettingsBack();

		this.fieldContainer = fieldContainer;
		this.moveSettingsOn();
		this.popup.show();
	},

	moveSettingsOn: function ()
	{
		if(!this.fieldContainer)
		{
			return;
		}

		this.replaceSettingsNode(this.fieldContainer, this.popupContainer);
	},

	moveSettingsBack: function ()
	{
		if(!this.fieldContainer)
		{
			return;
		}

		this.replaceSettingsNode(this.popupContainer, this.fieldContainer);
		this.fieldContainer = null;
	},

	_popupClose: function (event)
	{
		if(this.popupCloseHandler && !this.popupCloseHandler.apply(this, []))
		{
			return;
		}

		this.onClose();
		BX.PopupWindow.prototype.close.apply(this.popup, [event]);
	},

	onClose: function ()
	{
		/*
		if(this.popup.isShown())
		{
			this.popup.close();
		}
		*/

		this.moveSettingsBack();
		this.popupCloseHandler = null;
	},

	replaceSettingsNode: function (source, destination)
	{
		if(!source || !source.firstElementChild)
		{
			return;
		}

		destination.appendChild(source.firstElementChild);
	},

	initPopup: function ()
	{
		if(this.popup)
		{
			return;
		}

		var popup = BX.PopupWindowManager.create(
			'crm_webform_edit_field_settings',
			null,
			{
				content: this.popupContent,
				autoHide: false,
				lightShadow: true,
				closeByEsc: true,
				overlay: {backgroundColor: 'black', opacity: 500},
				zIndex: -400,
				titleBar: this.caller.mess.dlgTitle,
				closeIcon: true
			}
		);
		popup.setButtons([
			new BX.PopupWindowButton({
				text: this.caller.mess.dlgClose,
				className: 'webform-small-button-accept crm-webform-edit-popup-button',
				events: {
					click: BX.proxy(function(){
						this.popup.close();
					}, this)
				}
			})
		]);

		popup.close = BX.proxy(this._popupClose, this);
		this.popup = popup;
	}
};

function CrmFormEditorFieldSelector(params)
{
	this.caller = params.caller;
	this.context = params.context;

	this.attributeSearch = 'data-bx-crm-wf-selector-search';
	this.attributeSearchButton = 'data-bx-crm-wf-selector-search-btn';
	this.attributeGroup = 'data-bx-crm-wf-selector-field-group';
	this.attributeField = 'data-bx-crm-wf-selector-field-name';
	this.attributeAddButton = 'data-bx-crm-wf-selector-btn-add';

	this.classFieldSelected = 'crm-webform-edit-right-inner-list-active';
	this.classSearchActive = 'crm-webform-edit-constructor-right-search-active';
	this.classGroupShow = 'crm-webform-edit-open';

	this.helper = BX.CrmFormEditorHelper;
	this.init(params);
}
CrmFormEditorFieldSelector.prototype =
{
	getFieldNode: function (fieldName)
	{
		return this.context.querySelector('[' + this.attributeField + '="' + fieldName + '"]');
	},

	init: function (params)
	{
		// init groups
		this.groupNodeList = this.context.querySelectorAll('[' + this.attributeGroup + ']');
		this.groupNodeList = BX.convert.nodeListToArray(this.groupNodeList);
		this.groupNodeList.forEach(this.initGroup, this);

		// init fields
		this.fieldNodeList = this.context.querySelectorAll('[' + this.attributeField + ']');
		this.fieldNodeList = BX.convert.nodeListToArray(this.fieldNodeList);
		this.fieldNodeList.forEach(this.initField, this);

		// init special adding
		var specialAddNodeList = this.context.querySelectorAll('[' + this.attributeAddButton + ']');
		specialAddNodeList = BX.convert.nodeListToArray(specialAddNodeList);
		specialAddNodeList.forEach(function(specialAddNode){
			BX.bind(specialAddNode, 'click', BX.proxy(function() {
				this.addSpecialFormField(specialAddNode.getAttribute(this.attributeAddButton));
			}, this));
		}, this);

		// init delete callback
		BX.addCustomEvent(this.caller, 'delete-field', BX.proxy(this.onCallerFieldDelete, this));

		// init search button
		this.searchButton = this.context.querySelector('[' + this.attributeSearchButton + ']');
		BX.bind(this.searchButton, 'click', BX.proxy(this.onSearchButtonClick, this));

		// init search input
		this.searchInput = this.context.querySelector('[' + this.attributeSearch + ']');
		BX.bind(this.searchInput, 'bxchange', BX.proxy(this.onSearchChange, this));
	},

	showSearchResult: function (q)
	{
		q = q || '';
		q = q.toLowerCase();
		this.fieldNodeList.forEach(function(fieldNode){
			var hasSubstring = !q ? true : fieldNode.innerText.toLowerCase().indexOf(q) > -1;
			this.helper.styleDisplay(fieldNode, hasSubstring, 'list-item');
		}, this);
	},

	onSearchChange: function ()
	{
		this.showSearchResult(this.searchInput.value);
	},

	onSearchButtonClick: function ()
	{
		var isActive = BX.hasClass(this.searchButton, this.classSearchActive);
		if(isActive)
		{
			this.collapseGroups();
			this.showSearchResult();
		}
		else
		{
			this.expandGroups();
			this.showSearchResult(this.searchInput.value);
		}


		BX.toggleClass(this.searchButton, this.classSearchActive);
		this.searchInput.focus();
	},

	initGroup: function (groupNode)
	{
		BX.bind(groupNode.children[0], 'click', BX.proxy(function() {
			BX.toggleClass(groupNode, this.classGroupShow);
		}, this));
	},

	expandGroups: function ()
	{
		this.groupNodeList.forEach(function(groupNode){
			BX.addClass(groupNode, this.classGroupShow);
		}, this);
	},

	collapseGroups: function ()
	{
		this.groupNodeList.forEach(function(groupNode){
			BX.removeClass(groupNode, this.classGroupShow);
		}, this);
	},

	initField: function (fieldNode)
	{
		var fieldName = fieldNode.getAttribute(this.attributeField);
		var filteredFields = this.caller.fields.filter(function(field){
			return field.name == fieldName;
		});
		if(filteredFields.length > 0)
		{
			BX.addClass(fieldNode, this.classFieldSelected);
		}

		BX.bind(fieldNode, 'click', BX.proxy(function(e) {

			BX.addClass(fieldNode, this.classFieldSelected);
			var fieldName = fieldNode.getAttribute(this.attributeField);
			this.caller.addFieldByCode(fieldName);
			BX.PreventDefault(e);
			return true;

		}, this));
	},

	addSpecialFormField: function (type)
	{
		var fieldParams = {
			entity_caption: '-',
			caption: '',
			type: type,
			name: type + '_' + this.helper.generateId()
		};
		switch(type)
		{
			case 'product':
				fieldParams.caption = this.caller.mess.newFieldProductsCaption;
				fieldParams.id = 'n' + this.helper.generateId();
				break;
			case 'section':
				fieldParams.caption = this.caller.mess.newFieldSectionCaption;
				break;
			case 'page':
				fieldParams.caption = this.caller.mess.newFieldPageCaption;
				break;
			case 'hr':
				break;
			case 'br':
				break;
		}
		fieldParams.entity_field_caption = fieldParams.caption;

		return this.caller.addField(fieldParams);
	},

	onCallerFieldDelete: function (field)
	{
		var selectorFieldNode = this.getFieldNode(field.name);
		if(selectorFieldNode)
		{
			BX.removeClass(selectorFieldNode, this.classFieldSelected);
		}
	}
};

function CrmFormEditorDragDrop(params)
{
	this.init(params);
}
CrmFormEditorDragDrop.prototype =
{
	addItem: function(node)
	{
		this.dragdrop.addDragItem([node]);
		this.dragdrop.addSortableItem(node);
	},

	removeItem: function(node)
	{
		this.dragdrop.removeSortableItem(node);
	},

	init: function(params)
	{
		this.dragdrop = BX.DragDrop.create({

			sortable: {
				rootElem: BX('FIELD_CONTAINER'),
				gagClass: 'gag-class'
			},
			dragActiveClass: 'crm-webform-field-drag',
			dragItemClassName: 'field-selected',
			dragDrop: BX.delegate(function(catcherObj, dragElement, event)
			{
				BX.onCustomEvent(this, 'onSort', [dragElement, catcherObj]);
			}, this),
			dragStart: BX.delegate(function(eventObj, dragElement, event)
			{
				/*
				if(!dragElement)
				{
					dragElement = eventObj.dragElement;
				}
				if(!dragElement)
				{
					return;
				}

				if(eventObj.event.dataTransfer && eventObj.event.dataTransfer.setDragImage)
				{
					var dragIcon = document.createElement('img');
					eventObj.event.dataTransfer.setDragImage(dragIcon, -10, -10);
				}
				*/
			}, this),
			dragOver: BX.delegate(function(catcherObj, dragElement, event){}, this),
			dragEnter: BX.delegate(function(catcherObj, dragElement, event){}, this),
			dragLeave: BX.delegate(function(catcherObj, dragElement, event){}, this),
			dragEnd: BX.delegate(function(catcherObj, dragElement, event)
			{
				BX.onCustomEvent(this, 'onSort', [dragElement, catcherObj]);
			}, this)
		});
	}
};




function CrmFormEditorDependencies(params)
{
	params = params || {};
	params.dependencies = params.dependencies || [];
	this.helper = BX.CrmFormEditorHelper;

	this.deps = [];
	this.container = BX('DEPENDENCY_CONTAINER');
	this.buttonAdd = BX('DEPENDENCY_BUTTON_ADD');
	this.init(params);
}
CrmFormEditorDependencies.prototype =
{
	add: function (params)
	{
		var id = 'n' + this.helper.generateId();
		var data = {
			'ID': id,
			'IF_FIELD_CODE': '',
			'IF_VALUE': '',
			'DO_FIELD_CODE': '',
			'DO_ACTION': ''
		};
		var depNode = this.helper.appendNodeByTemplate(this.container, this.caller.templates.dependency, data);

		this.bind(data);

		return depNode;
	},
	bind: function (dep)
	{
		dep.node = BX('DEPENDENCIES_' + dep.ID);

		dep.ifValueNodeCtrlS = BX('DEPENDENCIES_' + dep.ID + '_IF_VALUE_CTRL_S');
		dep.ifValueNodeCtrlI = BX('DEPENDENCIES_' + dep.ID + '_IF_VALUE_CTRL_I');
		dep.ifFieldNodeCtrl = BX('DEPENDENCIES_' + dep.ID + '_IF_FIELD_CODE_CTRL');
		dep.doFieldNodeCtrl = BX('DEPENDENCIES_' + dep.ID + '_DO_FIELD_CODE_CTRL');

		dep.ifValueNode = BX('DEPENDENCIES_' + dep.ID + '_IF_VALUE');
		dep.ifFieldNode = BX('DEPENDENCIES_' + dep.ID + '_IF_FIELD_CODE');
		dep.doFieldNode = BX('DEPENDENCIES_' + dep.ID + '_DO_FIELD_CODE');

		dep.actionNodeCtrl = BX('DEPENDENCIES_' + dep.ID + '_DO_ACTION');
		dep.elseHideTextNode = BX('DEPENDENCIES_' + dep.ID + '_ELSE_HIDE');
		dep.elseShowTextNode = BX('DEPENDENCIES_' + dep.ID + '_ELSE_SHOW');

		var _this = this;
		BX.bind(BX('DEPENDENCIES_' + dep.ID + '_BTN_REMOVE'), 'click', function(){
			_this.remove(dep);
		});
		BX.bind(dep.ifFieldNodeCtrl, 'change', function(){
			if(_this.canChangeFields)
			{
				dep.ifFieldNode.value = this.value;
				_this.actualizeFieldValues(dep);
			}
		});
		BX.bind(dep.doFieldNodeCtrl, 'change', function(){
			if(_this.canChangeFields)
			{
				dep.doFieldNode.value = this.value;
			}

			var caption = '';
			if(this.selectedOptions && this.selectedOptions.length > 0)
			{
				caption = this.selectedOptions[0].innerText;
				caption = '"' + caption + '"';
			}
			dep.elseShowTextNode.children[0].innerText = caption;
			dep.elseHideTextNode.children[0].innerText = caption;
		});
		BX.bind(dep.ifValueNodeCtrlS, 'change', function(){
			if(_this.canChangeFields)
			{
				dep.ifValueNode.value = this.value;
			}
		});
		BX.bind(dep.ifValueNodeCtrlI, 'change', function(){
			if(_this.canChangeFields)
			{
				dep.ifValueNode.value = this.value;
			}
		});
		BX.bind(dep.actionNodeCtrl, 'change', BX.proxy(function(){
			var isActionHide = dep.actionNodeCtrl.value == 'hide';
			this.helper.styleDisplay(dep.elseHideTextNode, !isActionHide);
			this.helper.styleDisplay(dep.elseShowTextNode, isActionHide);
		}, this));

		this.actualize(dep);

		this.deps.push(dep);
	},
	remove: function (dep)
	{
		BX.remove(dep.node);

		var itemIndex = BX.util.array_search(dep, this.deps);
		if(itemIndex > -1)
		{
			delete this.deps[itemIndex];
		}
	},
	actualizeFieldList: function(node, values, isDo)
	{
		this.canChangeFields = false;

		var exceptFieldTypes = ['hr', 'br'];
		var fields;
		var defaultOptionText = '';

		isDo = isDo || false;
		if (isDo)
		{
			defaultOptionText = this.caller.mess.selectFieldOrSection;
			fields = this.caller.fields.filter(function(field){
				return !BX.util.in_array(field.type, exceptFieldTypes);
			}, this);
		}
		else
		{
			defaultOptionText = this.caller.mess.selectField;
			fields = this.caller.fields.filter(function(field){
				return (field.type != 'section' && field.type != 'page' && !BX.util.in_array(field.type, exceptFieldTypes));
			}, this);
		}

		fields = BX.util.array_merge(
			[{caption: defaultOptionText, value: '', selected: false}],
			fields.map(function(field){
				return {
					value: field.name || '',
					caption: field.getCaption(),
					selected: BX.util.in_array(field.name, values)
				};
			})
		);

		this.helper.fillDropDownControl(node, fields);


		this.canChangeFields = true;
	},
	actualizeFieldValues: function (dep)
	{
		this.canChangeFields = false;

		var listTypes = ['product', 'list', 'checkbox', 'radio'];

		var fieldName = dep.ifFieldNode.value;
		if(!fieldName) return;

		var field = this.caller.findField(fieldName);
		if(!field) return;

		var isList = BX.util.in_array(field.type, listTypes);
		dep.ifValueNodeCtrlI.style.display = isList ? 'none' : '';
		dep.ifValueNodeCtrlS.style.display = !isList ? 'none' : '';

		var defaultOptionText = this.caller.mess.selectValue;

		var values = [dep.ifValueNode.value];
		if(isList)
		{
			var items;
			if(field.items && field.items.length > 0)
			{
				items = field.items;
			}
			else if (field.type == 'checkbox')
			{
				items = this.caller.booleanFieldItems;
			}
			else
			{
				items = [];
			}

			items = items.map(function(item){
				return {
					value: item.ID,
					caption: item.VALUE,
					selected: BX.util.in_array(item.ID, values)
				};
			});

			this.helper.fillDropDownControl(
				dep.ifValueNodeCtrlS,
				BX.util.array_merge(
					[{caption: defaultOptionText, value: '', selected: false}],
					items
				)
			);
		}
		else if(!isList)
		{
			dep.ifValueNodeCtrlI.value = values[0];
		}

		this.canChangeFields = true;
	},
	actualizeFieldListConditional: function (node, values)
	{
		this.actualizeFieldList(node, values, false);
	},
	actualizeFieldListOperational: function (node, values)
	{
		this.actualizeFieldList(node, values, true);
	},
	actualize: function (dep)
	{
		// get selected dependence field names
		var valueIf = dep.ifFieldNode.value;
		var valueDo = dep.doFieldNode.value;

		// get added field names
		var fieldNameList = this.caller.getFieldNameList();

		if(
			(valueIf && !BX.util.in_array(valueIf, fieldNameList))
			||
			(valueDo && !BX.util.in_array(valueDo, fieldNameList))
		)
		{
			// if field deleted, delete existed dependence
			this.remove(dep);
		}
		else
		{
			// actualize field list in dependency selectors
			this.actualizeFieldListConditional(dep.ifFieldNodeCtrl, [valueIf]);
			this.actualizeFieldValues(dep);
			this.actualizeFieldListOperational(dep.doFieldNodeCtrl, [valueDo]);
		}
	},
	onChangeFormFields: function()
	{
		this.deps.forEach(this.actualize, this);
	},
	init: function (params)
	{
		this.caller = params.caller;
		this.helper = new CrmFormEditorHelper();

		this.canChangeFields = true;
		// init add button
		BX.bind(this.buttonAdd, 'click', BX.proxy(this.add, this));

		// init existed deps
		params.dependencies.forEach(this.bind, this);

		// listen events of changing form fields
		BX.addCustomEvent(this.caller, 'change-field-list', BX.proxy(this.onChangeFormFields, this));
		BX.addCustomEvent(this.caller, 'change-field-items', BX.proxy(this.onChangeFormFields, this));
	}
};



function CrmFormEditorFieldPreset(params)
{
	params = params || {};
	params.fields = params.fields || [];

	this.fields = [];
	this.container = BX('PRESET_FIELD_CONTAINER');
	this.buttonAdd = BX('PRESET_FIELD_SELECTOR_BTN');
	this.toggler = BX('USE_PRESET_FIELDS');
	this.context = BX('PRESET_FIELDS');
	this.dealCatrgoryNode = BX('DEAL_CATEGORY');
	this.init(params);
}
CrmFormEditorFieldPreset.prototype =
{
	isExists: function (code)
	{
		var isExists = false;
		this.fields.forEach(function(field){
			if(field.CODE == code)
			{
				isExists = true;
			}
		});

		return isExists;
	},
	add: function (params)
	{
		if(this.isExists(params.CODE))
		{
			return null;
		}

		var fieldData = this.caller.findDictionaryField(params.CODE);
		if(!fieldData)
		{
			return null;
		}

		var data = {
			'CODE': fieldData.name,
			'ENTITY_CAPTION': fieldData.entity_caption,
			'ENTITY_FIELD_CAPTION': fieldData.caption,
			'ENTITY_NAME': fieldData.entity_name,
			'ENTITY_FIELD_NAME': fieldData.entity_field_name,
			'VALUE': ''
		};
		var node = this.helper.appendNodeByTemplate(this.container, this.caller.templates.presetField, data);
		data.ITEMS = fieldData.items || null;
		this.bind(data);

		return node;
	},
	bind: function (field)
	{
		field.node = BX('FIELD_PRESET_' + field.CODE);

		field.valueNodeCtrl = BX('FIELD_PRESET_' + field.CODE + '_VALUE');
		field.valueNodeCtrlS = BX('FIELD_PRESET_' + field.CODE + '_VALUE_CTRL_S');
		field.valueNodeCtrlI = BX('FIELD_PRESET_' + field.CODE + '_VALUE_CTRL_I');
		field.valueNodeCtrlIMacros = BX('FIELD_PRESET_' + field.CODE + '_VALUE_CTRL_I_M');
		field.valueNodeCtrlIMacrosHint = field.node.querySelector('.crm-webform-context-help');

		this.caller.initToolTips([field.valueNodeCtrlIMacrosHint]);

		var _this = this;
		BX.bind(BX('FIELD_PRESET_' + field.CODE + '_BTN_REMOVE'), 'click', function(){
			_this.remove(field);
		});

		BX.bind(field.valueNodeCtrlS, 'change', function(){
			if(_this.canChangeFields)
			{
				field.valueNodeCtrl.value = this.value;
			}
		});

		BX.bind(field.valueNodeCtrlI, 'change', function(){
			if(_this.canChangeFields)
			{
				field.valueNodeCtrl.value = this.value;
			}
		});

		BX.bind(field.valueNodeCtrlIMacros, 'click', function(){
			_this.popupMacrosCurrentInput = field.valueNodeCtrlI;
			_this.popupMacros.setBindElement(field.valueNodeCtrlIMacros);
			_this.popupMacros.show();
		});

		if (field.CODE == 'DEAL_STAGE_ID' && this.dealCatrgoryNode)
		{
			BX.bind(this.dealCatrgoryNode, 'click', function(){
				_this.actualize(field);
			});
		}

		this.actualize(field);

		this.fields.push(field);

		if (this.isFieldTypeList(field))
		{
			BX.fireEvent(field.valueNodeCtrlS, 'change');
		}
		else
		{
			BX.fireEvent(field.valueNodeCtrlI, 'change');
		}

		if (this.isFieldTypeEquals(field, ['date', 'datetime']))
		{
			var self = this;
			BX.bind(field.valueNodeCtrlI, 'click', function () {
				BX.calendar({
					node: field.valueNodeCtrlI,
					field: field.valueNodeCtrlI,
					bTime: self.isFieldTypeEquals(field, ['datetime'])
				});
			});
		}
	},
	removeAll: function ()
	{
		this.fields.forEach(this.remove, this);
	},
	remove: function (field)
	{
		BX.remove(field.node);

		var itemIndex = BX.util.array_search(field, this.fields);
		if(itemIndex > -1)
		{
			delete this.fields[itemIndex];
		}
	},
	isFieldTypeEquals: function (field, types)
	{
		types = BX.type.isArray(types) ? types : [types];

		var fieldName = field.CODE;
		if(!fieldName) return false;

		var fieldData = this.caller.findDictionaryField(fieldName);
		if(!fieldData) return false;

		return BX.util.in_array(fieldData.type, types);
	},
	isFieldTypeList: function (field)
	{
		return this.isFieldTypeEquals(field, ['list', 'checkbox', 'radio']);
	},
	actualize: function (field)
	{
		this.canChangeFields = false;

		var fieldName = field.CODE;
		if(!fieldName) return;

		var fieldData = this.caller.findDictionaryField(fieldName);
		if(!fieldData) return;

		var isList = this.isFieldTypeList(field);
		field.valueNodeCtrlI.style.display = isList ? 'none' : '';
		field.valueNodeCtrlIMacros.style.display = isList ? 'none' : '';
		field.valueNodeCtrlIMacrosHint.style.display = isList ? 'none' : '';
		field.valueNodeCtrlS.style.display = !isList ? 'none' : '';

		var values = [field.valueNodeCtrl.value];
		if(isList)
		{
			var fieldDataItems;
			if (!fieldData.items || fieldData.items.length == 0)
			{
				if (fieldData.type == 'checkbox')
				{
					fieldDataItems = this.caller.booleanFieldItems;
				}
			}
			else
			{
				fieldDataItems = fieldData.items;
			}

			if (fieldDataItems)
			{
				if (fieldName == 'DEAL_STAGE_ID' && this.dealCatrgoryNode)
				{
					var dealCategoryId = this.dealCatrgoryNode.value;
					if (dealCategoryId && fieldData.itemsByCategory && fieldData.itemsByCategory[dealCategoryId])
					{
						fieldDataItems = fieldData.itemsByCategory[dealCategoryId];
					}
				}

				this.helper.fillDropDownControl(
					field.valueNodeCtrlS,
					fieldDataItems.map(function(item){
						return {
							value: item.ID,
							caption: item.VALUE,
							selected: BX.util.in_array(item.ID, values)
						};
					})
				);
			}
		}
		else if(!isList)
		{
			field.valueNodeCtrlI.value = values[0];
		}

		this.canChangeFields = true;
	},

	onChangeEntityScheme: function(schemeData)
	{
		var entityTypes = this.caller.entityScheme.getCurrentWillCreatedEntities();
		entityTypes.push('ACTIVITY');
		var fieldsForDelete = this.fields.filter(function(field){
			return !BX.util.in_array(field.ENTITY_NAME, entityTypes);
		}, this);

		if(fieldsForDelete)
		{
			fieldsForDelete.reverse();
			fieldsForDelete.forEach(function(field){this.remove(field); }, this);
		}

		var firstVisibleOption;
		var optGroupList = BX.convert.nodeListToArray(BX('PRESET_FIELD_SELECTOR').querySelectorAll('optgroup'));
		optGroupList.forEach(function(optGroup){
			var isVisible = BX.util.in_array(optGroup.getAttribute('data-bx-crm-wf-entity'), entityTypes);
			optGroup.style.display = isVisible ? 'block' : 'none';
			if(!firstVisibleOption && isVisible) firstVisibleOption = optGroup.querySelector('option');
		}, this);

		if(firstVisibleOption)
		{
			firstVisibleOption.selected = true;
		}
	},
	onButtonAddClick: function()
	{
		this.add({CODE: BX('PRESET_FIELD_SELECTOR').value});
	},
	toggle: function ()
	{
		BX.toggleClass(this.context, 'crm-webform-edit-animate-show');
		if (!this.toggler.checked)
		{
			this.removeAll();
		}
	},
	onToggle: function (e)
	{
		if (!this.toggler.checked && this.fields.length > 0 && !confirm(this.caller.mess.dlgFieldPresetRemoveConfirm))
		{
			e.preventDefault();
			e.stopPropagation();
			return false;
		}

		this.toggle();
	},
	init: function (params)
	{
		this.caller = params.caller;
		this.helper = new CrmFormEditorHelper();

		this.canChangeFields = true;
		// init add button
		BX.bind(this.buttonAdd, 'click', BX.proxy(this.onButtonAddClick, this));

		// init existed fields
		if(params.fields)
		{
			params.fields.forEach(this.bind, this);
		}

		// listen events of changing form fields
		BX.addCustomEvent(this.caller, 'change-entity-scheme', BX.proxy(this.onChangeEntityScheme, this));
		this.onChangeEntityScheme(this.caller.entityScheme.getCurrent());

		BX.bind(this.toggler, 'click', this.onToggle.bind(this));

		this.initMacros();
	},
	initMacros: function ()
	{
		this.popupMacrosCurrentInput = null;

		var attributeName = 'data-bx-preset-macros';
		var popupContainer = BX('CRM_WEB_FORM_POPUP_PRESET_MACROS');
		var macrosNodeList = BX.convert.nodeListToArray(popupContainer.querySelectorAll('[' + attributeName + ']'));
		macrosNodeList.forEach(BX.proxy(function (macrosNode) {
			BX.bind(macrosNode, 'click', BX.delegate(function () {
				if(!this.popupMacrosCurrentInput)
				{
					return;
				}
				this.popupMacrosCurrentInput.value += ' ' + macrosNode.getAttribute(attributeName);
				BX.fireEvent(this.popupMacrosCurrentInput, 'change');
				this.popupMacros.close();
			}, this));
		}, this));

		this.popupMacros = BX.PopupWindowManager.create(
			'crm_webform_edit_preset_macros',
			null,
			{
				content: popupContainer,
				autoHide: true,
				lightShadow: true,
				closeByEsc: true
			}
		);
	}
};

function CrmFormEditorExternalAnalytics(params)
{
	this.context = BX('CRM_WEBFORM_EXTERNAL_ANALYTICS');

	this.classNameExisted = 'crm-webform-edit-task-options-metric-exist';
	this.attributeItem = 'data-bx-crm-webform-ext-an';
	this.attributeClose = 'data-bx-crm-webform-ext-an-close';
	this.attributeAdd = 'data-bx-crm-webform-ext-an-add';
	this.attributeValue = 'data-bx-crm-webform-ext-an-val';

	var itemNodeList = this.context.querySelectorAll('[' + this.attributeItem + ']');
	itemNodeList = BX.convert.nodeListToArray(itemNodeList);
	itemNodeList.forEach(this.bind, this);
}
CrmFormEditorExternalAnalytics.prototype =
{
	bind: function(itemNode)
	{
		var closeBtnNode = itemNode.querySelector('[' + this.attributeClose + ']');
		var addBtnNode = itemNode.querySelector('[' + this.attributeAdd + ']');
		var valueNode = itemNode.querySelector('[' + this.attributeValue + ']');

		BX.bind(closeBtnNode, 'click', BX.proxy(function(){
			BX.removeClass(itemNode, this.classNameExisted);
			valueNode.value = '';
		}, this));
		BX.bind(addBtnNode, 'click',BX.proxy(function(){
			BX.addClass(itemNode, this.classNameExisted);
			valueNode.focus();
		}, this));
	}
};

function CrmFormEditorUserBlockController(params)
{
	this.context = BX('CRM_WEBFORM_ADDITIONAL_OPTIONS');
	this.additionalOptionContainer = BX('ADDITIONAL_OPTION_CONTAINER');

	this.attributeOption = 'data-bx-crm-webform-edit-option';
	this.attributeNav = 'data-bx-crm-webform-edit-option-nav';
	this.attributePin = 'data-bx-crm-webform-edit-option-pin';

	this.userOptionPin = params.optionPinName;
	this.blocks = [];

	this.helper = BX.CrmFormEditorHelper;

	var blockNodeList = this.context.querySelectorAll('[' + this.attributeOption + ']');
	blockNodeList = BX.convert.nodeListToArray(blockNodeList);
	blockNodeList.forEach(this.initBlock, this);

	BX.bind(BX('ADDITIONAL_OPTION_BUTTON'), 'click', BX.proxy(function(){
		BX.toggleClass(this.additionalOptionContainer, 'crm-webform-edit-open');
	}, this));
}
CrmFormEditorUserBlockController.prototype =
{
	initBlock: function (blockNode)
	{
		var id = blockNode.getAttribute(this.attributeOption);
		var pinNode = blockNode.querySelector('[' + this.attributePin + ']');
		var block = {
			'id': id,
			'node': blockNode,
			'navNode': this.context.querySelector('[' + this.attributeNav + '="' + id + '"]'),
			'pinNode': pinNode,
			'isFixed': this.isBlockPinFixed(pinNode)
		};
		this.blocks.push(block);


		var _this = this;

		//bind block pin
		BX.bind(block.pinNode, 'click', function(){
			_this.onClickBlockPin(block);
		});

		//bind nav button
		BX.bind(block.navNode, 'click', function(e){
			BX.PreventDefault(e);
			_this.highLightBlock(block);
			return true;
		});

		if(block.id == 'ENTITY_SCHEME')
		{
			//bind top nav button to document
			BX.bind(BX('CRM_WEBFORM_STICKER_ENTITY_SCHEME_NAV'), 'click', function(e){
				BX.PreventDefault(e);
				_this.highLightBlock(block);
				return true;
			});
		}
	},
	isBlockPinFixed: function (pinNode)
	{
		return BX.hasClass(pinNode, 'task-option-fixed-state');
	},
	onClickBlockPin: function (block)
	{
		// set pin state of block
		block.isFixed = !this.isBlockPinFixed(block.pinNode);

		// save pin state of block
		this.saveBlockPinState(block.id, block.isFixed);

		//change icon state indicator
		this.helper.changeClass(block.pinNode, 'task-option-fixed-state', block.isFixed);

		// move block
		this.moveBlock(block);

		// change visibility of nav button
		this.helper.changeClass(block.navNode, 'crm-webform-display-none', block.isFixed);
	},
	show: function ()
	{
		BX.addClass(this.additionalOptionContainer, 'crm-webform-edit-open');
	},
	hide: function ()
	{
		BX.removeClass(this.additionalOptionContainer, 'crm-webform-edit-open');
	},
	highLightBlock: function (block)
	{
		this.show();
		var highlightClassName = 'crm-webform-edit-highlight';
		BX.addClass(block.node, highlightClassName);


		setTimeout(function(){
			var position = BX.pos(block.node);
			window.scrollTo(0, position.top);
		}, 600);

		setTimeout(function(){
			BX.removeClass(block.node, highlightClassName);
		}, 3000);
	},
	moveBlock: function (block)
	{
		this.show();
		//hide block
		BX.addClass(block.node, 'crm-webform-display-none');

		//append block
		var target = block.isFixed ? BX('FIXED_OPTION_PLACE') : BX('ADDITIONAL_OPTION_PLACE_' + block.id);
		target.appendChild(block.node);

		//show block
		BX.removeClass(block.node, 'crm-webform-display-none');
	},
	saveBlockPinState: function (blockId, isPinned)
	{
		BX.userOptions.save('crm', this.userOptionPin, blockId, isPinned ? 'Y' : 'N');
	}
};



function CrmFormEditorFieldListSettings(params)
{
	this.type = params.type;
	this.context = params.context;
	this.caller = params.caller;
	this.field = params.field;

	this.helper = BX.CrmFormEditorHelper;
	this.nodeItemsContainer = this.context.querySelector('[data-bx-crm-webform-field-settings-items]');

	this.attributeItem = 'data-bx-crm-webform-field-settings-item';
	this.attributeItemCheck = 'data-bx-crm-webform-field-settings-item-check';
	this.attributeItemRadio = 'data-bx-crm-webform-field-settings-item-radio';
	this.attributeItemClear = 'data-bx-crm-webform-field-settings-item-clear';
	this.attributeItemInput = 'data-bx-crm-webform-field-settings-item-input';

	this.init();
}
CrmFormEditorFieldListSettings.prototype =
{
	init: function()
	{
		if(!this.nodeItemsContainer)
		{
			return;
		}

		var items = this.nodeItemsContainer.querySelectorAll('[' + this.attributeItem + ']');
		items = BX.convert.nodeListToArray(items);
		items.forEach(function(item){
			var clearButton = item.querySelector('[' + this.attributeItemClear + ']');
			var input = item.querySelector('[' + this.attributeItemInput + ']');
			var itemId = item.getAttribute(this.attributeItem);
			BX.bind(clearButton, 'click', function(){
				input.value = '';
				BX.fireEvent(input, 'change');
			});
			BX.bind(input, 'change', BX.proxy(function(){
				this.field.items.forEach(function(fieldItem){
					if(fieldItem.ID == itemId)
					{
						fieldItem.VALUE = input.value;
						this.caller.fireFieldChangeItemsEvent(this.field);
					}
				}, this);

			}, this));

		}, this);


		if(this.type == 'checkbox')
		{
			this.showControls(this.attributeItemCheck);
		}
		else if(this.type == 'radio')
		{
			this.showControls(this.attributeItemRadio);
		}

	},

	showControls: function(attribute)
	{
		var controlList = this.nodeItemsContainer.querySelectorAll('[' + attribute + ']')
		controlList = BX.convert.nodeListToArray(controlList);
		controlList.forEach(function(control){
			control.disabled = false;
			this.helper.styleDisplay(control, true);
		}, this);
	}
};




function CrmFormEditorProductSelector(params)
{
	this.helper = BX.CrmFormEditorHelper;

	this.caller = params.caller;
	this.id = params.id;
	this.field = params.field;
	this.context = params.context;
	this.node = this.context.querySelector('[data-bx-crm-webform-product]');
	if(!this.node)
	{
		return;
	}

	this.nodeItems = this.node.querySelector('[data-bx-crm-webform-product-items]');
	this.nodeSelect = this.node.querySelector('[data-bx-crm-webform-product-select]');
	this.nodeAddRow = this.node.querySelector('[data-bx-crm-webform-product-add-row]');

	this.attributeItem = 'data-bx-crm-webform-product-item';
	this.attributeItemDelete = 'data-bx-crm-webform-product-item-del';
	this.attributeItemInput = 'data-bx-crm-webform-product-item-input';

	this.random = Math.random();
	this.jsEventsManagerId = params.jsEventsManagerId;
	this.caller = params.caller;
	BX.bind(this.nodeSelect, 'click', BX.proxy(this.onClick, this));
	BX.bind(this.nodeAddRow, 'click', BX.proxy(this.onClickAddRow, this));
	this._choiceBtnEnabled = true;

	BX.addCustomEvent('CrmProductSearchDialog_SelectProduct', BX.proxy(this.onProductClick, this));

	this.isCallSearchDialog = false;
	this.initItems();
}
CrmFormEditorProductSelector.prototype =
{
	onShow: function(e)
	{
		/*
		var choiceBtn = document.getElementById(this.getSetting('choiceBtnID', ''));
		if(choiceBtn)
			BX.removeClass(choiceBtn, "webform-small-button-wait");
		*/
		this.isCallSearchDialog = true;
		this.helper.overlay.removeOverlay();
		this.caller.popupFieldSettings.setAutoHide(false);
	},
	onClose: function(e)
	{
		this.isCallSearchDialog = false;
		this._choiceBtnEnabled = true;
		this.caller.popupFieldSettings.setAutoHide(false);
	},
	onClick: function(e)
	{
		if (!this._choiceBtnEnabled)
			return;

		this._choiceBtnEnabled = false;

		this.helper.overlay.createOverlay(2000);

		/*
		var choiceBtn = document.getElementById(this.getSetting('choiceBtnID', ''));
		if(choiceBtn)
			BX.addClass(choiceBtn, "webform-small-button-wait");
		*/

		var caller = 'crm_productrow_list';
		var dlg = BX.CrmProductSearchDialogWindow.create({
			content_url: "/bitrix/components/bitrix/crm.webform.edit/product_choice_dialog.php" +
				"?caller=" + caller + "&JS_EVENTS_MANAGER_ID=" + BX.util.urlencode(this.jsEventsManagerId) +
				"&sessid=" + BX.bitrix_sessid(),
			closeWindowHandler: BX.delegate(this.onClose, this),
			showWindowHandler: BX.delegate(this.onShow, this),
			jsEventsManagerId: this.jsEventsManagerId,
			height: Math.max(500, window.innerHeight - 400),
			width: Math.max(800, window.innerWidth - 400),
			minHeight: 500,
			minWidth: 800,
			draggable: true,
			resizable: true
		});
		dlg.show();
	},

	onClickAddRow: function()
	{
		this.addFieldItem({
			'id': 'n' + this.helper.generateId(),
			'name': '',
			'price': '',
			'discount': '',
			'custom_price': '',
		});
	},

	initItems: function()
	{
		this.field.items.forEach(function(fieldItem){
			var itemNode = this.nodeItems.querySelector('[' + this.attributeItem + '="' + fieldItem.ID + '"' + ']');
			if(!itemNode)
			{
				return;
			}

			this.initProductItem(itemNode, fieldItem);

		}, this);
	},


	handleProductChoice: function(data, skipFocus)
	{
		skipFocus = !!skipFocus;
		var item = typeof(data['product']) != 'undefined' && typeof(data['product'][0]) != 'undefined' ? data['product'][0] : null;
		if(!item)
		{
			return;
		}

		var customData = typeof(item['customData']) !== 'undefined' ? item['customData'] : {};
		var measure = typeof(customData['measure']) !== 'undefined' ? customData['measure'] : {};
		var itemData =
		{
			id: item['id'],
			name: item['title'],
			quantity: 1.0,
			price: typeof(customData['price']) != 'undefined' ? parseFloat(customData['price']) : 0.0,
			customized: false,
			measureCode: typeof(measure['code']) !== 'undefined' ? parseInt(measure['code']) : 0,
			measureName: typeof(measure['name']) !== 'undefined' ? measure['name'] : '',
			tax: typeof(customData['tax']) !== 'undefined' ? customData['tax'] : {}
		};

		this.addFieldItem(itemData);
		/*
		 if (this._viewMode)
		 this.toggleMode();
		 this._addItem(itemData, true);
		 if (!skipFocus)
		 this.focusLastRow();
		 */
	},

	addFieldItem: function(itemData)
	{
		var itemNode = this.helper.getNodeByTemplate('tmpl_field_product_settings_item', {
			'id': this.id,
			'name': this.field.name,
			'item_id': itemData.id,
			'item_value': itemData.name,
			'item_price': itemData.price,
			'item_discount': itemData.discount || '',
			'item_custom_price': itemData.custom_price === 'Y' ? 'checked' : '',
			'currency_short_name': this.caller.currency.SHORT_NAME
		});

		var fieldItem = {
			'ID': itemData.id,
			'PRICE': itemData.price,
			'DISCOUNT': itemData.discount,
			'CUSTOM_PRICE': itemData.custom_price,
			'VALUE': itemData.name
		};

		this.initProductItem(itemNode, fieldItem);
		this.field.items.push(fieldItem);

		this.nodeItems.appendChild(itemNode);
		this.fireFieldItemsChange();
	},

	fireFieldItemsChange: function()
	{
		this.caller.fireFieldChangeItemsEvent(this.field);
	},

	initProductItem: function(itemNode, fieldItem)
	{
		var itemId = itemNode.getAttribute(this.attributeItem);
		var itemDeleteNode = itemNode.querySelector('[' + this.attributeItemDelete + ']');
		var itemInputNode = itemNode.querySelector('[' + this.attributeItemInput + ']');
		BX.bind(itemDeleteNode, 'click', BX.proxy(function(){
			this.removeProductItem(itemNode, fieldItem);
		}, this));

		BX.bind(itemInputNode, 'change', BX.proxy(function(){
			this.field.items.forEach(function(fieldItem){
				if(fieldItem.ID == itemId)
				{
					fieldItem.VALUE = itemInputNode.value;
					this.caller.fireFieldChangeItemsEvent(this.field);
				}
			}, this);

		}, this));
	},

	removeProductItem: function(itemNode, fieldItem)
	{
		var index =BX.util.array_search(fieldItem, this.field.items);
		if(index > -1)
		{
			this.field.items = BX.util.deleteFromArray(this.field.items, index);
		}
		BX.remove(itemNode);

		this.fireFieldItemsChange();
	},

	onProductClick: function(productId)
	{
		if(!this.isCallSearchDialog)
		{
			return;
		}

		productId = parseInt(productId);
		if (productId <= 0)
		{
			return;
		}

		var currencyID = '';//this.getCurrencyId();
		BX.ajax({
			'url': '/bitrix/components/bitrix/crm.product.list/list.ajax.php',
			'method': 'POST',
			'dataType': 'json',
			'data':
			{
				"sessid": BX.bitrix_sessid(),
				"MODE": "SEARCH",
				"RESULT_WITH_VALUE" : "Y",
				"CURRENCY_ID": currencyID,
				"ENABLE_RAW_PRICES": "Y",
				"ENABLE_SEARCH_BY_ID": "N",
				"MULTI": "N",
				"VALUE": "[" + productId + "]",
				"LIMIT": 1
			},
			onsuccess: BX.delegate(this.onProductChoiceByIdSuccess, this),
			onfailure: BX.delegate(this.onProductChoiceByIdFailure, this)
		});
	},
	onProductChoiceByIdSuccess: function(response)
	{
		var data;
		if (response && response["data"])
		{
			data = response["data"];
			if (data[0])
			{
				data = {"product": [data[0]]};
				this.handleProductChoice(data, true);
			}
		}
	},
	onProductChoiceByIdFailure: function(data)
	{
	}
};

function CrmFormEditorHelper(){}
CrmFormEditorHelper.prototype =
{
	generateId: function (min, max)
	{
		min = min || 1000000;
		max = max || 9999999;
		return Math.floor(Math.random() * (max - min)) + min;
	},

	fillDropDownControl: function(node, items)
	{
		items = items || [];
		node.innerHTML = '';

		items.forEach(function(item){
			if(!item || !item.caption)
			{
				return;
			}

			var option = document.createElement('option');
			option.value = item.value;
			option.selected = !!item.selected;
			option.innerText = item.caption;
			node.appendChild(option);
		});
	},

	appendNodeByTemplate: function(container, templateId, replaceData)
	{
		var node = this.getNodeByTemplate(templateId, replaceData);
		if(node)
		{
			container.appendChild(node);
		}

		return node;
	},

	getNodeByTemplate: function(id, replaceData)
	{
		var tmpl = this.getTemplate(id, replaceData);
		if(!tmpl)
		{
			return null;
		}

		var div = BX.create('div', {html: tmpl});
		return div.firstElementChild;
	},

	getTemplate: function(id, replaceData)
	{
		var tmplNode = BX(id);
		if(!tmplNode)
		{
			return null;
		}

		var tmpl = tmplNode.innerHTML;
		if(replaceData)
		{
			for(var i in replaceData)
			{
				if(replaceData[i] === undefined)
				{
					continue;
				}

				var replaceFrom = i;
				var replaceTo = BX.util.htmlspecialchars(replaceData[i]);
				tmpl = tmpl.replace(new RegExp('%' + replaceFrom + '%','g'), replaceTo);
			}
		}

		return tmpl;
	},

	changeClass: function (node, className, isAdd)
	{
		isAdd = isAdd || false;
		if(!node)
		{
			return;
		}

		if(isAdd)
		{
			BX.addClass(node, className);
		}
		else
		{
			BX.removeClass(node, className);
		}
	},

	styleDisplay: function (node, isShow, displayValue)
	{
		isShow = isShow || false;
		displayValue = displayValue || '';
		if(!node)
		{
			return;
		}

		node.style.display = isShow ? displayValue : 'none';
	},

	overlay: {

		createOverlay: function(zIndex)
		{
			zIndex = parseInt(zIndex);
			if (!this._overlay)
			{
				var windowSize = BX.GetWindowScrollSize();
				this._overlay = document.body.appendChild(BX.create("DIV", {
					style: {
						position: 'absolute',
						top: '0px',
						left: '0px',
						zIndex: zIndex || (parseInt(this.DIV.style.zIndex)-2),
						width: windowSize.scrollWidth + "px",
						height: windowSize.scrollHeight + "px"
					}
				}));
				BX.unbind(window, 'resize', BX.proxy(this._resizeOverlay, this));
				BX.bind(window, 'resize', BX.proxy(this._resizeOverlay, this));
			}
		},
		removeOverlay: function()
		{
			if (this._overlay && this._overlay.parentNode)
			{
				this._overlay.parentNode.removeChild(this._overlay);
				BX.unbind(window, 'resize', BX.proxy(this._resizeOverlay, this));
				this._overlay = null;
			}
		},
		_resizeOverlay: function()
		{
			var windowSize = BX.GetWindowScrollSize();
			this._overlay.style.width = windowSize.scrollWidth + "px";
		}
	}
};
BX.CrmFormEditorHelper = new CrmFormEditorHelper();


if (typeof(BX.CrmProductSearchDialogWindow) === "undefined")
{
	BX.CrmProductSearchDialogWindow = function()
	{
		this._settings = {};
		this.popup = null;
		this.random = "";
		this.contentContainer = null;
		this.zIndex = 100;
		this.jsEventsManager = null;
		this.pos = null;
		this.height = 0;
		this.width = 0;
		this.resizeCorner = null;
	};

	BX.CrmProductSearchDialogWindow.prototype = {
		initialize: function (settings)
		{
			this.random = Math.random().toString().substring(2);

			this._settings = settings ? settings : {};

			var size = BX.CrmProductSearchDialogWindow.size;

			this._settings.width = size.width || this._settings.width || 1100;
			this._settings.height = size.height || this._settings.height || 530;
			this._settings.minWidth = this._settings.minWidth || 500;
			this._settings.minHeight = this._settings.minHeight || 800;
			this._settings.draggable = !!this._settings.draggable || true;
			this._settings.resizable = !!this._settings.resizable || true;
			if (typeof(this._settings.closeWindowHandler) !== "function")
				this._settings.closeWindowHandler = null;
			if (typeof(this._settings.showWindowHandler) !== "function")
				this._settings.showWindowHandler = null;

			this.jsEventsManager = BX.Crm[this._settings.jsEventsManagerId] || null;

			this.contentContainer = BX.create(
				"DIV",
				{
					attrs: {
						className: "crm-catalog",
						style: "display: block; background-color: #f3f6f7; height: " + this._settings.height +
						"px; overflow: hidden; width: " + this._settings.width + "px;"
					}
				}
			);
		},
		_handleCloseDialog: function(popup)
		{
			if(popup)
				popup.destroy();
			this.popup = null;
			if (this.jsEventsManager)
			{
				this.jsEventsManager.unregisterEventHandlers("CrmProduct_SelectSection");
			}
			if (typeof(this._settings.closeWindowHandler) === "function")
				this._settings.closeWindowHandler();
		},
		_handleAfterShowDialog: function(popup)
		{
			popup.popupContainer.style.position = "fixed";
			popup.popupContainer.style.top =
				(parseInt(popup.popupContainer.style.top) - BX.GetWindowScrollPos().scrollTop) + 'px';
			if (typeof(this._settings.showWindowHandler) === "function")
				this._settings.showWindowHandler();
		},
		setContent: function (htmlData)
		{
			if (BX.type.isString(htmlData) && BX.type.isDomNode(this.contentContainer))
				this.contentContainer.innerHTML = htmlData;
		},
		show: function ()
		{
			BX.ajax({
				method: "GET",
				dataType: 'html',
				url: this._settings.content_url,
				data: {},
				skipAuthCheck: true,
				onsuccess: BX.delegate(function(data) {
					this.setContent(data || "&nbsp;");
					this.showWindow();
				}, this),
				onfailure: BX.delegate(function() {
					if (typeof(this._settings.showWindowHandler) === "function")
						this._settings.showWindowHandler();
				}, this)
			});
		},
		showWindow: function ()
		{
			this.popup = new BX.PopupWindow(
				"CrmProductSearchDialogWindow_" + this.random,
				null,
				{
					overlay: {opacity: 82},
					autoHide: false,
					draggable: this._settings.draggable,
					offsetLeft: 0,
					offsetTop: 0,
					bindOptions: { forceBindPosition: false },
					bindOnResize: false,
					zIndex: this.zIndex - 300,
					closeByEsc: true,
					closeIcon: { top: '10px', right: '15px' },
					"titleBar":
					{
						"content": BX.create("SPAN", { "attrs":
								{ "className": "popup-window-titlebar-text" },
								"text": BX.message('CRM_WEBFORM_EDIT_JS_PRODUCT_CHOICE')
							})
					},
					events:
					{
						onPopupClose: BX.delegate(this._handleCloseDialog, this),
						onAfterPopupShow: BX.delegate(this._handleAfterShowDialog, this)
					},
					"content": this.contentContainer
				}
			);
			if (this.popup.popupContainer)
			{
				this.resizeCorner = BX.create(
					'SPAN',
					{
						attrs: {className: "bx-crm-dialog-resize"},
						events: {mousedown : BX.delegate(this.resizeWindowStart, this)}
					}
				);
				this.popup.popupContainer.appendChild(this.resizeCorner);
				if (!this._settings.resizable)
					this.resizeCorner.style.display = "none";
			}
			this.popup.show();
		},
		setResizable: function(resizable)
		{
			resizable = !!resizable;
			if (this._settings.resizable !== resizable)
			{
				this._settings.resizable = resizable;
				if (this.resizeCorner)
				{
					if (resizable)
						this.resizeCorner.style.display = "inline-block";
					else
						this.resizeCorner.style.display = "none";
				}
			}
		},
		resizeWindowStart: function(e)
		{
			if (!this._settings.resizable)
				return;

			e =  e || window.event;
			BX.PreventDefault(e);

			this.pos = BX.pos(this.contentContainer);

			BX.bind(document, "mousemove", BX.proxy(this.resizeWindowMove, this));
			BX.bind(document, "mouseup", BX.proxy(this.resizeWindowStop, this));

			if (document.body.setCapture)
				document.body.setCapture();

			try { document.onmousedown = false; } catch(e) {}
			try { document.body.onselectstart = false; } catch(e) {}
			try { document.body.ondragstart = false; } catch(e) {}
			try { document.body.style.MozUserSelect = "none"; } catch(e) {}
			try { document.body.style.cursor = "nwse-resize"; } catch(e) {}
		},
		resizeWindowMove: function(e)
		{
			var windowScroll = BX.GetWindowScrollPos();
			var x = e.clientX + windowScroll.scrollLeft;
			var y = e.clientY + windowScroll.scrollTop;

			BX.CrmProductSearchDialogWindow.size.height = this.height = Math.max(y-this.pos.top, this._settings.minHeight);
			BX.CrmProductSearchDialogWindow.size.width = this.width = Math.max(x-this.pos.left, this._settings.minWidth);

			this.contentContainer.style.height = this.height+'px';
			this.contentContainer.style.width = this.width+'px';
		},
		resizeWindowStop: function(e)
		{
			if(document.body.releaseCapture)
				document.body.releaseCapture();

			BX.unbind(document, "mousemove", BX.proxy(this.resizeWindowMove, this));
			BX.unbind(document, "mouseup", BX.proxy(this.resizeWindowStop, this));

			try { document.onmousedown = null; } catch(e) {}
			try { document.body.onselectstart = null; } catch(e) {}
			try { document.body.ondragstart = null; } catch(e) {}
			try { document.body.style.MozUserSelect = ""; } catch(e) {}
			try { document.body.style.cursor = "auto"; } catch(e) {}
		}
	};

	BX.CrmProductSearchDialogWindow.create = function(settings)
	{
		var self = new BX.CrmProductSearchDialogWindow();
		self.initialize(settings);
		return self;
	};
	BX.CrmProductSearchDialogWindow.loadCSS = function(settings)
	{
		BX.ajax({
			method: "GET",
			dataType: 'html',
			url: settings.content_url,
			data: {},
			skipAuthCheck: true
		});
	};


	BX.CrmProductSearchDialogWindow.size = {width: 0, height: 0};
}

BX.namespace("BX.Crm");
if (typeof(BX.Crm.PageEventsManagerClass) === "undefined")
{
	BX.Crm.PageEventsManagerClass = function()
	{
		this._settings = {};
	};

	BX.Crm.PageEventsManagerClass.prototype = {
		initialize: function (settings)
		{
			this._settings = settings ? settings : {};
			this.eventHandlers = {};
		},
		registerEventHandler: function(eventName, eventHandler)
		{
			if (!this.eventHandlers[eventName])
				this.eventHandlers[eventName] = [];
			this.eventHandlers[eventName].push(eventHandler);
			BX.addCustomEvent(this, eventName, eventHandler);
		},
		fireEvent: function(eventName, eventParams)
		{
			BX.onCustomEvent(this, eventName, eventParams);
		},
		unregisterEventHandlers: function(eventName)
		{
			if (this.eventHandlers[eventName])
			{
				for (var i = 0; i < this.eventHandlers[eventName].length; i++)
				{
					BX.removeCustomEvent(this, eventName, this.eventHandlers[eventName][i]);
					delete this.eventHandlers[eventName][i];
				}
			}
		}
	};

	BX.Crm.PageEventsManagerClass.create = function(settings)
	{
		var self = new BX.Crm.PageEventsManagerClass();
		self.initialize(settings);
		return self;
	};
}

function CrmFormEditorDestination (params)
{
	var me = this;

	this.caller = params.caller;
	var container = params.container;

	var config, configString = container.getAttribute('data-config');
	if (configString)
	{
		config = BX.parseJSON(configString);
	}

	if (!BX.type.isPlainObject(config))
		config = {};

	this.container = container;
	this.itemsNode = BX.create('span');
	this.inputBoxNode = BX.create('span', {
		attrs: {
			className: 'feed-add-destination-input-box',
			style: 'display: none;'
		}
	});
	this.inputNode = BX.create('input', {
		props: {
			type: 'text'
		},
		attrs: {
			className: 'feed-add-destination-inp'
		}
	});

	this.inputBoxNode.appendChild(this.inputNode);

	this.tagNode = BX.create('a', {
		attrs: {
			className: 'feed-add-destination-link'
		}
	});

	BX.addClass(container, 'crm-webform-popup-autocomplete');

	container.appendChild(this.itemsNode);
	container.appendChild(this.inputBoxNode);
	container.appendChild(this.tagNode);

	this.itemTpl = config.itemTpl;

	this.data = null;
	this.dialogId = 'crm_webform_edit_responsible_';
	this.createValueNode(config.valueInputName || '');
	this.selected = config.selected ? BX.clone(config.selected) : [];
	this.selectOne = !config.multiple;
	this.required = config.required || false;
	this.additionalFields = BX.type.isArray(config.additionalFields) ? config.additionalFields : [];

	BX.bind(this.tagNode, 'focus', function(e) {
		me.openDialog({bByFocusEvent: true});
		return BX.PreventDefault(e);
	});
	BX.bind(this.container, 'click', function(e) {
		me.openDialog();
		return BX.PreventDefault(e);
	});

	this.addItems(this.selected);

	this.tagNode.innerHTML = (
		this.selected.length <= 0
			? this.caller.mess.dlgChoose
			: this.caller.mess.dlgChange
	);
}
CrmFormEditorDestination.prototype = {
	getData: function(next)
	{
		var me = this;

		if (me.ajaxProgress)
			return;

		me.ajaxProgress = true;

		this.caller.sendActionRequest('get_destination_data', {}, function (response) {
			me.data = response.DATA || {};
			me.ajaxProgress = false;
			me.initDialog(next);
		}, function () {

		});
	},
	initDialog: function(next)
	{
		var i, me = this, data = this.data;

		if (!data)
		{
			me.getData(next);
			return;
		}

		var itemsSelected = {};
		for (i = 0; i < me.selected.length; ++i)
		{
			itemsSelected[me.selected[i].id] = me.selected[i].entityType
		}

		var items = {
			users : data.USERS || {},
			department : data.DEPARTMENT || {},
			departmentRelation : data.DEPARTMENT_RELATION || {},
			bpuserroles : data.ROLES || {}
		};
		var itemsLast =  {
			users: data.LAST.USERS || {},
			bpuserroles : data.LAST.ROLES || {}
		};

		for (i = 0; i < this.additionalFields.length; ++i)
		{
			items.bpuserroles[this.additionalFields[i]['id']] = this.additionalFields[i];
		}

		if (!items["departmentRelation"])
		{
			items["departmentRelation"] = BX.SocNetLogDestination.buildDepartmentRelation(items["department"]);
		}

		if (!me.inited)
		{
			me.inited = true;
			var destinationInput = me.inputNode;
			destinationInput.id = me.dialogId + 'input';

			var destinationInputBox = me.inputBoxNode;
			destinationInputBox.id = me.dialogId + 'input-box';

			var tagNode = this.tagNode;
			tagNode.id = this.dialogId + 'tag';

			var itemsNode = me.itemsNode;

			BX.SocNetLogDestination.init({
				name : me.dialogId,
				searchInput : destinationInput,
				extranetUser :  false,
				bindMainPopup : {node: me.container, offsetTop: '5px', offsetLeft: '15px'},
				bindSearchPopup : {node: me.container, offsetTop : '5px', offsetLeft: '15px'},
				departmentSelectDisable: true,
				sendAjaxSearch: true,
				callback : {
					select : function(item, type, search, bUndeleted)
					{
						me.addItem(item, type);
						if (me.selectOne)
							BX.SocNetLogDestination.closeDialog();
					},
					unSelect : function (item)
					{
						if (me.selectOne)
							return;
						me.unsetValue(item.entityId);
						BX.SocNetLogDestination.BXfpUnSelectCallback.call({
							formName: me.dialogId,
							inputContainerName: itemsNode,
							inputName: destinationInput.id,
							tagInputName: tagNode.id,
							tagLink1: me.caller.mess.dlgChoose,
							tagLink2: me.caller.mess.dlgChange
						}, item)
					},
					openDialog : BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback, {
						inputBoxName: destinationInputBox.id,
						inputName: destinationInput.id,
						tagInputName: tagNode.id
					}),
					closeDialog : BX.delegate(
						BX.SocNetLogDestination.BXfpCloseDialogCallback,
						{
							inputBoxName: destinationInputBox.id,
							inputName: destinationInput.id,
							tagInputName: tagNode.id
						}
					),
					openSearch : BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback, {
						inputBoxName: destinationInputBox.id,
						inputName: destinationInput.id,
						tagInputName: tagNode.id
					}),
					closeSearch : BX.delegate(BX.SocNetLogDestination.BXfpCloseSearchCallback, {
						inputBoxName: destinationInputBox.id,
						inputName: destinationInput.id,
						tagInputName: tagNode.id
					})
				},
				items : items,
				itemsLast : itemsLast,
				itemsSelected : itemsSelected,
				useClientDatabase: false,
				destSort: data.DEST_SORT || {},
				allowAddUser: false
			});

			BX.bind(destinationInput, 'keyup', BX.delegate(BX.SocNetLogDestination.BXfpSearch, {
				formName: me.dialogId,
				inputName: destinationInput.id,
				tagInputName: tagNode.id
			}));
			BX.bind(destinationInput, 'keydown', BX.delegate(BX.SocNetLogDestination.BXfpSearchBefore, {
				formName: me.dialogId,
				inputName: destinationInput.id
			}));

			BX.SocNetLogDestination.BXfpSetLinkName({
				formName: me.dialogId,
				tagInputName: tagNode.id,
				tagLink1: me.caller.mess.dlgChoose,
				tagLink2: me.caller.mess.dlgChange
			});
		}
		next();
	},
	addItem: function(item, type)
	{
		var me = this;
		var destinationInput = this.inputNode;
		var tagNode = this.tagNode;
		var items = this.itemsNode;

		if (!BX.findChild(items, { attr : { 'data-id' : item.id }}, false, false))
		{
			if (me.selectOne && me.inited)
			{
				var toRemove = [];
				for (var i = 0; i < items.childNodes.length; ++i)
				{
					toRemove.push({
						itemId: items.childNodes[i].getAttribute('data-id'),
						itemType: items.childNodes[i].getAttribute('data-type')
					})
				}

				me.initDialog(function() {
					for (var i = 0; i < toRemove.length; ++i)
					{
						BX.SocNetLogDestination.deleteItem(toRemove[i].itemId, toRemove[i].itemType, me.dialogId);
					}
				});

				BX.cleanNode(items);
				me.cleanValue();
			}

			var container = this.createItemNode({
				text: item.name,
				deleteEvents: {
					click: function(e) {
						if (me.selectOne && me.required)
						{
							me.openDialog();
						}
						else
						{
							me.initDialog(function() {
								BX.SocNetLogDestination.deleteItem(item.id, type, me.dialogId);
								BX.remove(container);
								me.unsetValue(item.entityId);
							});
						}
						BX.PreventDefault(e);
					}
				}
			});

			this.setValue(item.entityId);

			container.setAttribute('data-id', item.id);
			container.setAttribute('data-type', type);

			items.appendChild(container);

			if (!item.entityType)
				item.entityType = type;
		}

		destinationInput.value = '';
		tagNode.innerHTML = this.caller.mess.dlgChange;
	},
	addItems: function(items)
	{
		for(var i = 0; i < items.length; ++i)
		{
			this.addItem(items[i], items[i].entityType)
		}
	},
	openDialog: function(params)
	{
		var me = this;
		this.initDialog(function()
		{
			BX.SocNetLogDestination.openDialog(me.dialogId, params);
		})
	},
	destroy: function()
	{
		if (this.inited)
		{
			if (BX.SocNetLogDestination.isOpenDialog())
			{
				BX.SocNetLogDestination.closeDialog();
			}
			BX.SocNetLogDestination.closeSearch();
		}
	},
	createItemNode: function(options)
	{
		return BX.create('span', {
			attrs: {
				className: 'crm-webform-popup-autocomplete-item'
			},
			children: [
				BX.create('span', {
					attrs: {
						className: 'crm-webform-popup-autocomplete-name'
					},
					html: options.text || ''
				}),
				BX.create('span', {
					attrs: {
						className: 'crm-webform-popup-autocomplete-delete'
					},
					events: options.deleteEvents
				})
			]
		});
	},
	createValueNode: function(valueInputName)
	{
		this.valueNode = BX.create('input', {
			props: {
				type: 'hidden',
				name: valueInputName
			}
		});

		this.container.appendChild(this.valueNode);
	},
	setValue: function(value)
	{
		if (/^\d+$/.test(value) !== true)
			return;

		if (this.selectOne)
			this.valueNode.value = value;
		else
		{
			var i, newVal = [], pairs = this.valueNode.value.split(',');
			for (i = 0; i < pairs.length; ++i)
			{
				if (!pairs[i] || value == pairs[i])
					continue;
				newVal.push(pairs[i]);
			}
			newVal.push(value);
			this.valueNode.value = newVal.join(',');
		}

	},
	unsetValue: function(value)
	{
		if (/^\d+$/.test(value) !== true)
			return;

		if (this.selectOne)
			this.valueNode.value = '';
		else
		{
			var i, newVal = [], pairs = this.valueNode.value.split(',');
			for (i = 0; i < pairs.length; ++i)
			{
				if (!pairs[i] || value == pairs[i])
					continue;
				newVal.push(pairs[i]);
			}
			this.valueNode.value = newVal.join(',');
		}
	},
	cleanValue: function()
	{
		this.valueNode.value = '';
	}
};

function CrmFormAdsForm (params)
{
	this.caller = params.caller;
	this.container = params.container;

	this.init();
}
CrmFormAdsForm.prototype = {

	init: function (params)
	{
		if (!this.container || !this.caller.id)
		{
			return;
		}

		this.attributeButton = 'data-bx-ads-button';
		var buttonNodes = this.container.querySelectorAll('[' + this.attributeButton + ']');
		buttonNodes = BX.convert.nodeListToArray(buttonNodes);
		buttonNodes.forEach(function (buttonNode) {
			BX.bind(buttonNode, 'click', BX.proxy(function (e) {
				e.preventDefault();
				this.caller.slider.open(buttonNode.href);
			}, this));
		}, this);

		this.adsPopup = null;
	}
};