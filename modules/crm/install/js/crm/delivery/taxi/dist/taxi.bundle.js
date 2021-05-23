this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
this.BX.Crm.Delivery = this.BX.Crm.Delivery || {};
(function (exports,ui_vue) {
	'use strict';

	var UseLocalize = {
	  computed: {
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('TIMELINE_DELIVERY_TAXI_');
	    }
	  }
	};

	var UseExternalLangMessages = {
	  props: {
	    langMessages: {
	      required: false,
	      type: Object
	    }
	  },
	  methods: {
	    getLangMessage: function getLangMessage(key) {
	      return this.langMessages.hasOwnProperty(key) ? this.langMessages[key] : key;
	    }
	  }
	};

	var UseActivity = {
	  props: {
	    self: {
	      required: true,
	      type: Object
	    }
	  },
	  mixins: [UseLocalize, UseExternalLangMessages],
	  computed: {
	    data: function data() {
	      return this.self._data;
	    },
	    fields: function fields() {
	      return this.data.ASSOCIATED_ENTITY.SETTINGS.FIELDS;
	    },
	    author: function author() {
	      return this.data.AUTHOR;
	    },
	    statusName: function statusName() {
	      if (this.fields.STATUS === 'initial' || this.fields.STATUS === 'searching') {
	        return '';
	      } else if (this.fields.STATUS === 'on_its_way') {
	        return this.localize.TIMELINE_DELIVERY_TAXI_DELIVERY_STATUS_ON_ITS_WAY;
	      } else if (this.fields.STATUS === 'success') {
	        return this.localize.TIMELINE_DELIVERY_TAXI_DELIVERY_STATUS_SUCCESS;
	      } else if (this.fields.STATUS === 'unknown') {
	        return this.localize.TIMELINE_DELIVERY_TAXI_DELIVERY_STATUS_UNKNOWN;
	      }
	    },
	    statusClass: function statusClass() {
	      var isUnknownStatus = this.fields.STATUS === 'unknown';
	      return {
	        'crm-entity-stream-content-event-process': isUnknownStatus,
	        'crm-entity-stream-content-event-done': !isUnknownStatus
	      };
	    }
	  }
	};

	var AuthorComponent = {
	  props: {
	    author: {
	      required: true,
	      type: Object
	    }
	  },
	  computed: {
	    linkStyle: function linkStyle() {
	      if (!this.author.IMAGE_URL) {
	        return {};
	      }

	      return {
	        'background-image': 'url(' + this.author.IMAGE_URL + ')',
	        'background-size': '21px'
	      };
	    }
	  },
	  template: "\n\t\t<a\n\t\t\tv-if=\"author.SHOW_URL\"\n\t\t\t:href=\"author.SHOW_URL\"\n\t\t\t:style=\"linkStyle\"\n\t\t\tclass=\"crm-entity-stream-content-detail-employee\">\t\n\t\t</a>\n\t"
	};

	var LogoComponent = {
	  props: {
	    logo: {
	      required: true,
	      type: String
	    }
	  },
	  template: "\n\t\t<div\n\t\t\tclass=\"crm-entity-stream-content-delivery-title-logo\"\n\t\t\t:style=\"'background: url(' + logo +') center no-repeat;'\"\n\t\t></div>\n\t"
	};

	var InfoComponent = {
	  props: {
	    name: {
	      required: false,
	      type: String
	    },
	    method: {
	      required: false,
	      type: String
	    }
	  },
	  template: "\n\t\t<div class=\"crm-entity-stream-content-delivery-title-info\">\n\t\t\t<div v-if=\"name\" class=\"crm-entity-stream-content-delivery-title-name\">\n\t\t\t\t{{name}}\n\t\t\t</div>\n\t\t\t<div v-if=\"method\" class=\"crm-entity-stream-content-delivery-title-param\">\n\t\t\t\t{{method}}\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var RouteComponent = {
	  props: {
	    from: {
	      required: true,
	      type: String
	    },
	    to: {
	      required: true,
	      type: String
	    }
	  },
	  mixins: [UseLocalize],
	  template: "\n\t\t<div class=\"crm-entity-stream-content-delivery-order-item\">\n\t\t\t<div class=\"crm-entity-stream-content-delivery-order-label\">\n\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_ROUTE}}\n\t\t\t</div>\n\t\t\t<div class=\"crm-entity-stream-content-delivery-order-value crm-entity-stream-content-delivery-order-value--sm crm-entity-stream-content-delivery-order-value--flex\">\n\t\t\t\t<span v-html=\"from\"></span>\n\t\t\t\t<span class=\"crm-entity-stream-content-delivery-order-arrow\"></span>\n\t\t\t\t<span v-html=\"to\"></span>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var PerformerComponent = {
	  props: {
	    name: {
	      required: true,
	      type: String
	    },
	    phone: {
	      required: false,
	      type: String
	    },
	    phoneExt: {
	      required: false,
	      type: String
	    }
	  },
	  mixins: [UseLocalize],
	  methods: {
	    call: function call() {
	      if (!(this.phone && typeof top.BXIM !== 'undefined')) {
	        return;
	      }

	      top.BXIM.phoneTo(this.phone);
	    }
	  },
	  template: "\n\t\t<div class=\"crm-entity-stream-content-delivery-order-item\">\n\t\t\t<div class=\"crm-entity-stream-content-delivery-order-label\">\n\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DRIVER}}\n\t\t\t</div>\n\t\t\t<div class=\"crm-entity-stream-content-delivery-order-value crm-entity-stream-content-delivery-order-value--sm\">\n\t\t\t\t<span>\n\t\t\t\t\t{{name}}\n\t\t\t\t</span>\n\t\t\t\t<span v-if=\"phone\" @click=\"call\" class=\"crm-entity-stream-content-delivery-link\">\n\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_CALL_DRIVER}}\n\t\t\t\t</span>\n\t\t\t\t<span\n\t\t\t\t\tv-if=\"phoneExt\"\n\t\t\t\t\tclass=\"crm-entity-stream-content-delivery-phone-ext\"\n\t\t\t\t>\n\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_CALL_DRIVER_PHONE_EXT_CODE}}: {{phoneExt}}\n\t\t\t\t</span>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var CarComponent = {
	  props: {
	    car: {
	      required: true,
	      type: String
	    }
	  },
	  mixins: [UseLocalize],
	  template: "\n\t\t<div class=\"crm-entity-stream-content-delivery-order-item\">\n\t\t\t<div class=\"crm-entity-stream-content-delivery-order-label\">\n\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_CAR}}\n\t\t\t</div>\n\t\t\t<div class=\"crm-entity-stream-content-delivery-order-value crm-entity-stream-content-delivery-order-value--sm\">\n\t\t\t\t{{car}}\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var activity = ui_vue.Vue.extend({
	  components: {
	    'author': AuthorComponent,
	    'logo': LogoComponent,
	    'info': InfoComponent,
	    'route': RouteComponent,
	    'performer': PerformerComponent,
	    'car': CarComponent
	  },
	  mixins: [UseActivity, UseLocalize],
	  data: function data() {
	    return {
	      isMakingRequest: false,
	      isCancelling: false
	    };
	  },
	  methods: {
	    completeActivity: function completeActivity() {
	      if (this.self.canComplete()) {
	        this.self.setAsDone(!this.self.isDone());
	      }
	    },
	    makeRequest: function makeRequest() {
	      var _this = this;

	      this.isMakingRequest = true;
	      BX.ajax.runAction('sale.taxidelivery.sendrequest', {
	        analyticsLabel: 'saleDeliveryTaxiCall',
	        data: {
	          shipmentId: this.fields.SHIPMENT_ID
	        }
	      }).then(function (result) {}).catch(function (result) {
	        _this.isMakingRequest = false;

	        _this.showError(result.errors.map(function (item) {
	          return item.message;
	        }).join());
	      });
	    },
	    cancelRequest: function cancelRequest() {
	      var _this2 = this;

	      if (this.isCancelling) {
	        return;
	      }

	      this.isCancelling = true;
	      BX.ajax.runAction('sale.taxidelivery.cancelrequest', {
	        data: {
	          shipmentId: this.fields.SHIPMENT_ID,
	          requestId: this.fields.REQUEST_ID
	        }
	      }).then(function (result) {
	        _this2.isCancelling = false;
	      }).catch(function (result) {
	        _this2.isCancelling = false;

	        _this2.showError(result.errors.map(function (item) {
	          return item.message;
	        }).join());
	      });
	    },
	    checkRequestStatus: function checkRequestStatus() {
	      BX.ajax.runAction('sale.taxidelivery.checkrequeststatus');
	    },
	    startCheckingRequestStatus: function startCheckingRequestStatus() {
	      var _this3 = this;

	      clearTimeout(this._checkRequestStatusTimeoutId);
	      this._checkRequestStatusTimeoutId = setInterval(function () {
	        return _this3.checkRequestStatus();
	      }, 10 * 1000);
	    },
	    stopCheckingRequestStatus: function stopCheckingRequestStatus() {
	      clearTimeout(this._checkRequestStatusTimeoutId);
	    },
	    showError: function showError(message) {
	      BX.loadExt('ui.notification').then(function () {
	        BX.UI.Notification.Center.notify({
	          content: message
	        });
	      });
	    },
	    showContextMenu: function showContextMenu(event) {
	      var _this4 = this;

	      var popup = BX.PopupMenu.create('taxi_activity_context_menu_' + this.self.getId(), event.target, [{
	        id: 'delete',
	        text: this.getLangMessage('menuDelete'),
	        onclick: function onclick() {
	          popup.close();
	          var deletionDlgId = 'entity_timeline_deletion_' + _this4.self.getId() + '_confirm';
	          var dlg = BX.Crm.ConfirmationDialog.get(deletionDlgId);

	          if (!dlg) {
	            dlg = BX.Crm.ConfirmationDialog.create(deletionDlgId, {
	              title: _this4.getLangMessage('removeConfirmTitle'),
	              content: _this4.getLangMessage('deliveryRemove')
	            });
	          }

	          dlg.open().then(function (result) {
	            if (result.cancel) {
	              return;
	            }

	            _this4.self.remove();
	          }, function (result) {});
	        }
	      }], {
	        autoHide: true,
	        offsetTop: 0,
	        offsetLeft: 16,
	        angle: {
	          position: "top",
	          offset: 0
	        },
	        events: {
	          onPopupShow: function onPopupShow() {
	            return BX.addClass(event.target, 'active');
	          },
	          onPopupClose: function onPopupClose() {
	            return BX.removeClass(event.target, 'active');
	          }
	        }
	      });
	      popup.show();
	    }
	  },
	  created: function created() {
	    this._checkRequestStatusTimeoutId = null;

	    if (this.isSearchingCar) {
	      this.startCheckingRequestStatus();
	    }
	  },
	  computed: {
	    isExpectedPriceReceived: function isExpectedPriceReceived() {
	      return this.fields.hasOwnProperty('EXPECTED_PRICE_DELIVERY');
	    },
	    isSendRequestButtonVisible: function isSendRequestButtonVisible() {
	      if (this.isMakingRequest) {
	        return false;
	      }

	      if (!this.fields.STATUS) {
	        return false;
	      }

	      if (this.fields.STATUS) {
	        if (this.fields.STATUS === 'initial') {
	          return true;
	        }
	      }

	      return false;
	    },
	    isSearchingCar: function isSearchingCar() {
	      return this.isMakingRequest || this.fields.STATUS && this.fields.STATUS === 'searching';
	    },
	    isRequestCancellationLinkVisible: function isRequestCancellationLinkVisible() {
	      return this.fields && this.fields.REQUEST_CANCELLATION_AVAILABLE;
	    },
	    cancelRequestButtonStyle: function cancelRequestButtonStyle() {
	      return {
	        'ui-btn': true,
	        'ui-btn-sm': true,
	        'ui-btn-light-border': true,
	        'ui-btn-wait': this.isCancelling
	      };
	    }
	  },
	  watch: {
	    isSearchingCar: function isSearchingCar(value) {
	      if (value) {
	        this.startCheckingRequestStatus();
	      } else {
	        this.stopCheckingRequestStatus();
	      }
	    }
	  },
	  template: "\n\t\t<div class=\"crm-entity-stream-section crm-entity-stream-section-new crm-entity-stream-section-planned\">\n\t\t\t<div class=\"crm-entity-stream-section-icon crm-entity-stream-section-icon-new crm-entity-stream-section-icon-taxi\"></div>\n\t\t\t<div @click=\"showContextMenu\" class=\"crm-entity-stream-section-context-menu\"></div>\n\t\t\t<div class=\"crm-entity-stream-section-content\">\n\t\t\t\t<div class=\"crm-entity-stream-content-event\">\n\t\t\t\t\t<div class=\"crm-entity-stream-content-header\">\n\t\t\t\t\t\t<span class=\"crm-entity-stream-content-event-title\">\n\t\t\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_SERVICE}}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t<span v-if=\"statusName\":class=\"statusClass\">\n\t\t\t\t\t\t\t{{statusName}}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t<span class=\"crm-entity-stream-content-event-time\">{{this.createdAt}}</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"crm-entity-stream-content-detail crm-entity-stream-content-delivery\">\n\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-row crm-entity-stream-content-delivery-row--flex\">\n\t\t\t\t\t\t\t<span v-if=\"isSendRequestButtonVisible\" @click=\"makeRequest\" class=\"ui-btn ui-btn-sm ui-btn-primary\">\n\t\t\t\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_SEND_REQUEST}}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t<span v-if=\"isSearchingCar\" class=\"crm-entity-stream-content-delivery-status\">\n\t\t\t\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_SEARCHING_CAR}}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-title\">\n\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-icon crm-entity-stream-content-delivery-icon--car\"></div>\n\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-title-contnet\">\n\t\t\t\t\t\t\t\t\t<logo v-if=\"fields.DELIVERY_SYSTEM_LOGO\" :logo=\"fields.DELIVERY_SYSTEM_LOGO\"></logo>\n\t\t\t\t\t\t\t\t\t<info\n\t\t\t\t\t\t\t\t\t\tv-if=\"fields.DELIVERY_SYSTEM_NAME || fields.DELIVERY_METHOD\"\n\t\t\t\t\t\t\t\t\t\t:name=\"fields.DELIVERY_SYSTEM_NAME\"\n\t\t\t\t\t\t\t\t\t\t:method=\"fields.DELIVERY_METHOD\"\n\t\t\t\t\t\t\t\t\t></info>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-row\">\n\t\t\t\t\t\t\t<table class=\"crm-entity-stream-content-delivery-order\">\n\t\t\t\t\t\t\t\t<tr v-if=\"fields.ADDRESS_FROM && fields.ADDRESS_TO\">\n\t\t\t\t\t\t\t\t\t<td colspan=\"2\">\n\t\t\t\t\t\t\t\t\t\t<route\n\t\t\t\t\t\t\t\t\t\t\t:from=\"fields.ADDRESS_FROM\"\n\t\t\t\t\t\t\t\t\t\t\t:to=\"fields.ADDRESS_TO\"\n\t\t\t\t\t\t\t\t\t\t></route>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t\t<tr>\n\t\t\t\t\t\t\t\t\t<td>\n\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-item\">\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-label\">\n\t\t\t\t\t\t\t\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_CLIENT_DELIVERY_PRICE}}\n\t\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-value crm-entity-stream-content-delivery-order-value--sm\">\n\t\t\t\t\t\t\t\t\t\t\t\t<span v-html=\"fields.DELIVERY_PRICE\"></span>\n\t\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t\t<td>\n\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-item\">\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-label\">\n\t\t\t\t\t\t\t\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_EXPECTED_DELIVERY_PRICE}}\n\t\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-value crm-entity-stream-content-delivery-order-value--sm\">\t\t\t\t\t\t\t\t\t\t\t\t\n\t\t\t\t\t\t\t\t\t\t\t\t<span v-if=\"isExpectedPriceReceived\">\n\t\t\t\t\t\t\t\t\t\t\t\t\t<span v-html=\"fields.EXPECTED_PRICE_DELIVERY\"></span></span>\n\t\t\t\t\t\t\t\t\t\t\t\t<span v-else>\n\t\t\t\t\t\t\t\t\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_EXPECTED_PRICE_NOT_RECEIVED}}\n\t\t\t\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t\t<tr v-if=\"this.fields.PERFORMER_NAME\">\n\t\t\t\t\t\t\t\t\t<td colspan=\"2\">\n\t\t\t\t\t\t\t\t\t\t<performer\n\t\t\t\t\t\t\t\t\t\t\t:name=\"fields.PERFORMER_NAME\"\n\t\t\t\t\t\t\t\t\t\t\t:phone=\"fields.PERFORMER_PHONE\"\n\t\t\t\t\t\t\t\t\t\t\t:phoneExt=\"fields.PERFORMER_PHONE_EXT\"\n\t\t\t\t\t\t\t\t\t\t></performer>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t\t<tr v-if=\"fields.PERFORMER_CAR\">\n\t\t\t\t\t\t\t\t\t<td colspan=\"2\">\n\t\t\t\t\t\t\t\t\t\t<car :car=\"fields.PERFORMER_CAR\"></car>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t\t<tr v-if=\"isRequestCancellationLinkVisible\">\n\t\t\t\t\t\t\t\t\t<td colspan=\"2\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-item\">\n\t\t\t\t\t\t\t\t\t\t\t<span @click=\"cancelRequest\" :class=\"cancelRequestButtonStyle\">\n\t\t\t\t\t\t\t\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_CANCEL_REQUEST}}\n\t\t\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t</table>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"crm-entity-stream-content-detail-planned-action\">\n\t\t\t\t\t\t<input @click=\"completeActivity\" type=\"checkbox\" class=\"crm-entity-stream-planned-apply-btn\">\n\t\t\t\t\t</div>\n\t\t\t\t\t<author v-if=\"author\" :author=\"author\"></author>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

	var activitycompleted = ui_vue.Vue.extend({
	  components: {
	    'author': AuthorComponent,
	    'logo': LogoComponent,
	    'info': InfoComponent,
	    'route': RouteComponent,
	    'performer': PerformerComponent,
	    'car': CarComponent
	  },
	  mixins: [UseActivity, UseLocalize],
	  computed: {
	    isExpectedPriceReceived: function isExpectedPriceReceived() {
	      return this.fields.hasOwnProperty('EXPECTED_PRICE_DELIVERY');
	    }
	  },
	  template: "\n\t\t<div class=\"crm-entity-stream-section crm-entity-stream-section-new\">\n\t\t\t<div class=\"crm-entity-stream-section-icon crm-entity-stream-section-icon-new crm-entity-stream-section-icon-taxi\"></div>\n\t\t\t<div class=\"crm-entity-stream-section-content\">\n\t\t\t\t<div class=\"crm-entity-stream-content-event crm-entity-stream-content-event--delivery\">\n\t\t\t\t\t<div class=\"crm-entity-stream-content-header\">\n\t\t\t\t\t\t<span class=\"crm-entity-stream-content-event-title\">\n\t\t\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_SERVICE}}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t<span v-if=\"statusName\" :class=\"statusClass\">\n\t\t\t\t\t\t\t{{statusName}}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t<span class=\"crm-entity-stream-content-event-time\">{{this.createdAt}}</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"crm-entity-stream-content-detail crm-entity-stream-content-delivery\">\n\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-row crm-entity-stream-content-delivery-row--flex\">\n\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-title\">\n\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-icon crm-entity-stream-content-delivery-icon--car\"></div>\n\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-title-contnet\">\n\t\t\t\t\t\t\t\t\t<logo v-if=\"fields.DELIVERY_SYSTEM_LOGO\" :logo=\"fields.DELIVERY_SYSTEM_LOGO\"></logo>\n\t\t\t\t\t\t\t\t\t<info\n\t\t\t\t\t\t\t\t\t\tv-if=\"fields.DELIVERY_SYSTEM_NAME || fields.DELIVERY_METHOD\"\n\t\t\t\t\t\t\t\t\t\t:name=\"fields.DELIVERY_SYSTEM_NAME\"\n\t\t\t\t\t\t\t\t\t\t:method=\"fields.DELIVERY_METHOD\"\n\t\t\t\t\t\t\t\t\t></info>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-row\">\n\t\t\t\t\t\t\t<table class=\"crm-entity-stream-content-delivery-order\">\n\t\t\t\t\t\t\t\t<tr v-if=\"fields.ADDRESS_FROM && fields.ADDRESS_TO\">\n\t\t\t\t\t\t\t\t\t<td colspan=\"2\">\n\t\t\t\t\t\t\t\t\t\t<route\n\t\t\t\t\t\t\t\t\t\t\t:from=\"fields.ADDRESS_FROM\"\n\t\t\t\t\t\t\t\t\t\t\t:to=\"fields.ADDRESS_TO\"\n\t\t\t\t\t\t\t\t\t\t></route>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t\t<tr>\n\t\t\t\t\t\t\t\t\t<td>\n\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-item\">\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-label\">\n\t\t\t\t\t\t\t\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_CLIENT_DELIVERY_PRICE}}\n\t\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-value crm-entity-stream-content-delivery-order-value--sm\">\n\t\t\t\t\t\t\t\t\t\t\t\t<span v-html=\"fields.DELIVERY_PRICE\"></span>\n\t\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t\t<td>\n\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-item\">\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-label\">\n\t\t\t\t\t\t\t\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_EXPECTED_DELIVERY_PRICE}}\n\t\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-value crm-entity-stream-content-delivery-order-value--sm\">\n\t\t\t\t\t\t\t\t\t\t\t\t<span v-if=\"isExpectedPriceReceived\">\n\t\t\t\t\t\t\t\t\t\t\t\t\t<span v-html=\"fields.EXPECTED_PRICE_DELIVERY\"></span></span>\n\t\t\t\t\t\t\t\t\t\t\t\t<span v-else>\n\t\t\t\t\t\t\t\t\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_EXPECTED_PRICE_NOT_RECEIVED}}\n\t\t\t\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t\t<tr v-if=\"this.fields.PERFORMER_NAME\">\n\t\t\t\t\t\t\t\t\t<td colspan=\"2\">\n\t\t\t\t\t\t\t\t\t\t<performer\n\t\t\t\t\t\t\t\t\t\t\t:name=\"fields.PERFORMER_NAME\"\n\t\t\t\t\t\t\t\t\t\t\t:phone=\"fields.PERFORMER_PHONE\"\n\t\t\t\t\t\t\t\t\t\t\t:phoneExt=\"fields.PERFORMER_PHONE_EXT\"\n\t\t\t\t\t\t\t\t\t\t></performer>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t\t<tr v-if=\"fields.PERFORMER_CAR\">\n\t\t\t\t\t\t\t\t\t<td colspan=\"2\">\n\t\t\t\t\t\t\t\t\t\t<car :car=\"fields.PERFORMER_CAR\"></car>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t</table>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<author v-if=\"author\" :author=\"author\"></author>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

	var UseAuthor = {
	  computed: {
	    author: function author() {
	      return this.data.AUTHOR ? this.data.AUTHOR : null;
	    }
	  }
	};

	var UseHistoryItem = {
	  mixins: [UseLocalize, UseAuthor],
	  props: {
	    self: {
	      required: true,
	      type: Object
	    },
	    createdAt: {
	      required: true,
	      type: String
	    }
	  },
	  computed: {
	    data: function data() {
	      return this.self._data;
	    },
	    fields: function fields() {
	      return this.data.FIELDS ? this.data.FIELDS : null;
	    }
	  }
	};

	var HistoryItem = {
	  components: {
	    'author': AuthorComponent
	  },
	  props: {
	    author: {
	      required: false,
	      type: Object
	    },
	    createdAt: {
	      required: false,
	      type: String
	    }
	  },
	  template: "\n\t\t<div class=\"crm-entity-stream-section crm-entity-stream-section-new\">\n\t\t\t<div class=\"crm-entity-stream-section-icon crm-entity-stream-section-icon-new crm-entity-stream-section-icon-taxi\"></div>\n\t\t\t<div class=\"crm-entity-stream-section-content\">\n\t\t\t\t<div class=\"crm-entity-stream-content-event\">\n\t\t\t\t\t<div class=\"crm-entity-stream-content-header\">\n\t\t\t\t\t\t<span class=\"crm-entity-stream-content-event-title\">\n\t\t\t\t\t\t\t<slot name=\"title\"></slot>\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t<slot name=\"status\"></slot>\n\t\t\t\t\t\t<span class=\"crm-entity-stream-content-event-time\">\n\t\t\t\t\t\t\t<span v-html=\"createdAt\"></span>\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"crm-entity-stream-content-detail\">\n\t\t\t\t\t\t<slot></slot>\n\t\t\t\t\t</div>\n\t\t\t\t\t<author v-if=\"author\" :author=\"author\"></author>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var CarLogoInfo = {
	  props: {
	    logo: {
	      required: false
	    },
	    serviceName: {
	      required: false
	    },
	    methodName: {
	      required: false
	    }
	  },
	  components: {
	    'logo': LogoComponent,
	    'info': InfoComponent
	  },
	  mixins: [UseLocalize],
	  template: "\n\t\t<div class=\"crm-entity-stream-content-delivery-row\">\n\t\t\t<div class=\"crm-entity-stream-content-delivery-title\">\n\t\t\t\t<div class=\"crm-entity-stream-content-delivery-icon crm-entity-stream-content-delivery-icon--car\"></div>\n\t\t\t\t<div class=\"crm-entity-stream-content-delivery-title-contnet\">\n\t\t\t\t\t<logo v-if=\"logo\" :logo=\"logo\"></logo>\n\t\t\t\t\t<info\n\t\t\t\t\t\tv-if=\"serviceName || methodName\"\n\t\t\t\t\t\t:name=\"serviceName\"\n\t\t\t\t\t\t:method=\"methodName\"\n\t\t\t\t\t></info>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<slot name=\"bottom\"></slot>\n\t\t</div>\n\t"
	};

	var callrequest = ui_vue.Vue.extend({
	  mixins: [UseHistoryItem],
	  components: {
	    'history-item': HistoryItem,
	    'car-logo-info': CarLogoInfo
	  },
	  computed: {
	    isExpectedPriceReceived: function isExpectedPriceReceived() {
	      return this.fields.hasOwnProperty('EXPECTED_PRICE_DELIVERY');
	    }
	  },
	  template: "\n\t\t<history-item :author=\"author\" :createdAt=\"createdAt\">\n\t\t\t<template v-slot:title>\n\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_SEND_REQUEST_HISTORY_TITLE}}\t\t\t\t\n\t\t\t</template>\t\n\t\t\t<template v-slot:default>\n\t\t\t\t<car-logo-info\n\t\t\t\t\t:logo=\"fields.DELIVERY_SYSTEM_LOGO\"\n\t\t\t\t\t:service-name=\"fields.DELIVERY_SYSTEM_NAME\"\n\t\t\t\t\t:method-name=\"fields.DELIVERY_METHOD\"\n\t\t\t\t>\n\t\t\t\t\t<template v-slot:bottom>\n\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-description\">\n\t\t\t\t\t\t\t<template v-if=\"isExpectedPriceReceived\">\n\t\t\t\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_ESTIMATED_DELIVERY_PRICE_RECEIVED}}:\n\t\t\t\t\t\t\t\t<span v-html=\"fields.EXPECTED_PRICE_DELIVERY\"></span>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_EXPECTED_PRICE_NOT_RECEIVED_FULL}}\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</template>\n\t\t\t\t</car-logo-info>\n\t\t\t</template>\n\t\t</history-item>\n\t"
	});

	var estimationrequest = ui_vue.Vue.extend({
	  mixins: [UseHistoryItem],
	  components: {
	    'history-item': HistoryItem
	  },
	  computed: {
	    isExpectedPriceReceived: function isExpectedPriceReceived() {
	      return this.fields.hasOwnProperty('EXPECTED_PRICE_DELIVERY');
	    }
	  },
	  template: "\n\t\t<history-item :author=\"author\" :createdAt=\"createdAt\">\n\t\t\t<template v-slot:title>\n\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_PRICE_CALCULATION}}\n\t\t\t</template>\n\t\t\t<template v-slot:status>\n\t\t\t\t<span v-if=\"!isExpectedPriceReceived\" class=\"crm-entity-stream-content-event-missing\">\n\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_ERROR}}\n\t\t\t\t</span>\n\t\t\t</template>\t\t\t\n\t\t\t<template v-slot:default>\n\t\t\t\t<div class=\"crm-entity-stream-content-detail-description\">\n\t\t\t\t\t<template v-if=\"isExpectedPriceReceived\">\n\t\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_ESTIMATED_DELIVERY_PRICE_RECEIVED}}:\n\t\t\t\t\t\t<span v-html=\"fields.EXPECTED_PRICE_DELIVERY\"></span>\n\t\t\t\t\t</template>\n\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_EXPECTED_PRICE_NOT_RECEIVED_FULL}}\n\t\t\t\t\t</template>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-entity-stream-content-detail-description crm-entity-stream-content-delivery-order-value--flex\">\n\t\t\t\t\t<span v-html=\"fields.ADDRESS_FROM\"></span>\n\t\t\t\t\t<span class=\"crm-entity-stream-content-detail-description--arrow\"></span>\n\t\t\t\t\t<span v-html=\"fields.ADDRESS_TO\"></span>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</history-item>\n\t"
	});

	var cancelledbymanager = ui_vue.Vue.extend({
	  mixins: [UseHistoryItem],
	  components: {
	    'history-item': HistoryItem,
	    'car-logo-info': CarLogoInfo
	  },
	  template: "\n\t\t<history-item :author=\"author\" :createdAt=\"createdAt\">\n\t\t\t<template v-slot:title>\n\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_CANCELLED_BY_MANAGER}}\n\t\t\t</template>\n\t\t\t<template v-slot:status>\n\t\t\t\t<span class=\"crm-entity-stream-content-event-process\">{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_CANCELLATION}}</span>\n\t\t\t</template>\t\t\t\n\t\t\t<template v-slot:default>\n\t\t\t\t<car-logo-info\n\t\t\t\t\t:logo=\"fields.DELIVERY_SYSTEM_LOGO\"\n\t\t\t\t\t:service-name=\"fields.DELIVERY_SYSTEM_NAME\"\n\t\t\t\t\t:method-name=\"fields.DELIVERY_METHOD\"\n\t\t\t\t></car-logo-info>\n\t\t\t\t<div v-if=\"fields.IS_PAID\" class=\"crm-entity-stream-content-detail-notice\">\n\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_PAID_CANCELLATION}}\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</history-item>\n\t"
	});

	var cancelledbydriver = ui_vue.Vue.extend({
	  mixins: [UseHistoryItem],
	  components: {
	    'history-item': HistoryItem,
	    'car-logo-info': CarLogoInfo
	  },
	  template: "\n\t\t<history-item :author=\"author\" :createdAt=\"createdAt\">\n\t\t\t<template v-slot:title>\n\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_CANCELLED_BY_DRIVER}}\n\t\t\t</template>\n\t\t\t<template v-slot:status>\n\t\t\t\t<span class=\"crm-entity-stream-content-event-process\">{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_CANCELLATION}}</span>\n\t\t\t</template>\t\t\t\n\t\t\t<template v-slot:default>\n\t\t\t\t<car-logo-info\n\t\t\t\t\t:logo=\"fields.DELIVERY_SYSTEM_LOGO\"\n\t\t\t\t\t:service-name=\"fields.DELIVERY_SYSTEM_NAME\"\n\t\t\t\t\t:method-name=\"fields.DELIVERY_METHOD\"\n\t\t\t\t></car-logo-info>\n\t\t\t</template>\n\t\t</history-item>\n\t"
	});

	var performernotfound = ui_vue.Vue.extend({
	  mixins: [UseHistoryItem],
	  components: {
	    'history-item': HistoryItem,
	    'car-logo-info': CarLogoInfo
	  },
	  template: "\n\t\t<history-item :author=\"author\" :createdAt=\"createdAt\">\n\t\t\t<template v-slot:title>\n\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_PERFORMER_NOT_FOUND}}\n\t\t\t</template>\n\t\t\t<template v-slot:status>\n\t\t\t\t<span class=\"crm-entity-stream-content-event-missing\">\n\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_ERROR}}\n\t\t\t\t</span>\n\t\t\t</template>\t\t\t\n\t\t\t<template v-slot:default>\n\t\t\t\t<car-logo-info\n\t\t\t\t\t:logo=\"fields.DELIVERY_SYSTEM_LOGO\"\n\t\t\t\t\t:service-name=\"fields.DELIVERY_SYSTEM_NAME\"\n\t\t\t\t\t:method-name=\"fields.DELIVERY_METHOD\"\n\t\t\t\t></car-logo-info>\n\t\t\t</template>\n\t\t</history-item>\n\t"
	});

	var returnedfinish = ui_vue.Vue.extend({
	  mixins: [UseHistoryItem],
	  components: {
	    'history-item': HistoryItem,
	    'car-logo-info': CarLogoInfo
	  },
	  template: "\n\t\t<history-item :author=\"author\" :createdAt=\"createdAt\">\n\t\t\t<template v-slot:title>\n\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_RETURNED_FINISH_TITLE}}\n\t\t\t</template>\n\t\t\t<template v-slot:status>\n\t\t\t\t<span class=\"crm-entity-stream-content-event-process\">{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_RETURN}}</span>\n\t\t\t</template>\t\t\t\n\t\t\t<template v-slot:default>\n\t\t\t\t<car-logo-info\n\t\t\t\t\t:logo=\"fields.DELIVERY_SYSTEM_LOGO\"\n\t\t\t\t\t:service-name=\"fields.DELIVERY_SYSTEM_NAME\"\n\t\t\t\t\t:method-name=\"fields.DELIVERY_METHOD\"\n\t\t\t\t></car-logo-info>\n\t\t\t</template>\n\t\t</history-item>\n\t"
	});

	var smsproviderissue = ui_vue.Vue.extend({
	  mixins: [UseHistoryItem],
	  components: {
	    'history-item': HistoryItem,
	    'car-logo-info': CarLogoInfo
	  },
	  methods: {
	    setupSmsProvider: function setupSmsProvider() {
	      if (!this.fields.SMS_PROVIDER_SETUP_LINK) {
	        return;
	      }

	      BX.SidePanel.Instance.open(this.fields.SMS_PROVIDER_SETUP_LINK, {
	        cacheable: false
	      });
	    }
	  },
	  template: "\n\t\t<history-item :author=\"author\" :createdAt=\"createdAt\">\n\t\t\t<template v-slot:title>\n\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_SMS_PROVIDER_ISSUE_TITLE}}\n\t\t\t</template>\n\t\t\t<template v-slot:status>\n\t\t\t\t<span class=\"crm-entity-stream-content-event-missing\">\n\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_ERROR}}\n\t\t\t\t</span>\n\t\t\t</template>\t\t\t\n\t\t\t<template v-slot:default>\n\t\t\t\t<div class=\"crm-entity-stream-content-delivery-description\">\n\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_SMS_PROVIDER_ISSUE_DETAIL}}\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-entity-stream-content-detail-notice\">\n\t\t\t\t\t<a v-if=\"fields.SMS_PROVIDER_SETUP_LINK\" @click=\"setupSmsProvider\" href=\"#\" class=\"crm-entity-stream-content-detail-target\">\n\t\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_SMS_PROVIDER_SETUP}}\t\t\t\t\t\t\t\n\t\t\t\t\t</a>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</history-item>\n\t"
	});

	exports.Activity = activity;
	exports.ActivityCompleted = activitycompleted;
	exports.CallRequest = callrequest;
	exports.EstimationRequest = estimationrequest;
	exports.CancelledByManager = cancelledbymanager;
	exports.CancelledByDriver = cancelledbydriver;
	exports.PerformerNotFound = performernotfound;
	exports.ReturnedFinish = returnedfinish;
	exports.SmsProviderIssue = smsproviderissue;

}((this.BX.Crm.Delivery.Taxi = this.BX.Crm.Delivery.Taxi || {}),BX));
//# sourceMappingURL=taxi.bundle.js.map
