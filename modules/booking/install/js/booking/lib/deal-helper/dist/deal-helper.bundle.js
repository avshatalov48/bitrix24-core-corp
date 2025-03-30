/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_core,main_sidepanel,booking_core,booking_const,booking_provider_service_bookingService) {
	'use strict';

	var _bookingId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bookingId");
	var _deal = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("deal");
	var _booking = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("booking");
	class DealHelper {
	  constructor(bookingId) {
	    Object.defineProperty(this, _booking, {
	      get: _get_booking,
	      set: void 0
	    });
	    Object.defineProperty(this, _deal, {
	      get: _get_deal,
	      set: void 0
	    });
	    Object.defineProperty(this, _bookingId, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _bookingId)[_bookingId] = bookingId;
	  }
	  hasDeal() {
	    return Boolean(babelHelpers.classPrivateFieldLooseBase(this, _deal)[_deal]);
	  }
	  openDeal() {
	    main_sidepanel.SidePanel.Instance.open(`/crm/deal/details/${babelHelpers.classPrivateFieldLooseBase(this, _deal)[_deal].value}/`, {
	      events: {
	        onClose: () => {
	          var _babelHelpers$classPr;
	          if ((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _deal)[_deal]) != null && _babelHelpers$classPr.value) {
	            void booking_provider_service_bookingService.bookingService.getById(babelHelpers.classPrivateFieldLooseBase(this, _bookingId)[_bookingId]);
	          }
	        }
	      }
	    });
	  }
	  createDeal() {
	    var _babelHelpers$classPr2;
	    const bookingIdParamName = 'bookingId';
	    const createDealUrl = new main_core.Uri('/crm/deal/details/0/');
	    createDealUrl.setQueryParam(bookingIdParamName, babelHelpers.classPrivateFieldLooseBase(this, _bookingId)[_bookingId]);
	    ((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _booking)[_booking].clients) != null ? _babelHelpers$classPr2 : []).forEach(client => {
	      const paramName = {
	        [booking_const.CrmEntity.Contact]: 'contact_id',
	        [booking_const.CrmEntity.Company]: 'company_id'
	      }[client.type.code];
	      createDealUrl.setQueryParam(paramName, client.id);
	    });
	    main_sidepanel.SidePanel.Instance.open(createDealUrl.toString(), {
	      events: {
	        onLoad: ({
	          slider
	        }) => {
	          slider.getWindow().BX.Event.EventEmitter.subscribe('onCrmEntityCreate', event => {
	            const [data] = event.getData();
	            const isDeal = data.entityTypeName === booking_const.CrmEntity.Deal;
	            const bookingId = Number(new main_core.Uri(data.sliderUrl).getQueryParam(bookingIdParamName));
	            if (!isDeal || bookingId !== babelHelpers.classPrivateFieldLooseBase(this, _bookingId)[_bookingId]) {
	              return;
	            }
	            const dealData = this.mapEntityInfoToDeal(data.entityInfo);
	            this.saveDeal(dealData);
	          });
	        },
	        onClose: () => {
	          var _babelHelpers$classPr3;
	          if ((_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _deal)[_deal]) != null && _babelHelpers$classPr3.value) {
	            this.saveDeal(babelHelpers.classPrivateFieldLooseBase(this, _deal)[_deal]);
	          }
	        }
	      }
	    });
	  }
	  mapEntityInfoToDeal(info) {
	    return {
	      moduleId: booking_const.Module.Crm,
	      entityTypeId: info.typeName,
	      value: info.id,
	      data: []
	    };
	  }
	  saveDeal(dealData) {
	    const externalData = dealData ? [dealData] : [];
	    void booking_provider_service_bookingService.bookingService.update({
	      id: babelHelpers.classPrivateFieldLooseBase(this, _bookingId)[_bookingId],
	      externalData
	    });
	  }
	}
	function _get_deal() {
	  var _babelHelpers$classPr4, _babelHelpers$classPr5;
	  return (_babelHelpers$classPr4 = (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _booking)[_booking].externalData) == null ? void 0 : _babelHelpers$classPr5.find(data => data.entityTypeId === booking_const.CrmEntity.Deal)) != null ? _babelHelpers$classPr4 : null;
	}
	function _get_booking() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Bookings}/getById`](babelHelpers.classPrivateFieldLooseBase(this, _bookingId)[_bookingId]);
	}

	exports.DealHelper = DealHelper;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {}),BX,BX,BX.Booking,BX.Booking.Const,BX.Booking.Provider.Service));
//# sourceMappingURL=deal-helper.bundle.js.map
