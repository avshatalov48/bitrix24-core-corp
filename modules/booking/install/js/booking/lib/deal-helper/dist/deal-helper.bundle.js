/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_core,main_sidepanel,booking_provider_service_bookingService,booking_core,booking_const,booking_provider_service_waitListService) {
	'use strict';

	const SidePanel = main_sidepanel.SidePanel || BX.SidePanel;
	class DealHelper {
	  openDealSidePanel({
	    deal,
	    onClose
	  }) {
	    SidePanel.Instance.open(`/crm/deal/details/${deal.value}/`, {
	      events: {
	        onClose: () => onClose(deal)
	      }
	    });
	  }
	  createCrmDeal({
	    itemId,
	    itemIdQueryParamName,
	    queryParams = {},
	    clients,
	    onLoad,
	    onClose
	  }) {
	    const createDealUrl = new main_core.Uri('/crm/deal/details/0/');
	    createDealUrl.setQueryParam(itemIdQueryParamName, itemId);
	    Object.keys(queryParams).forEach(queryParamsKey => {
	      createDealUrl.setQueryParam(queryParamsKey, queryParams[queryParamsKey]);
	    });
	    clients.forEach(client => {
	      const paramName = {
	        [booking_const.CrmEntity.Contact]: 'contact_id',
	        [booking_const.CrmEntity.Company]: 'company_id'
	      }[client.type.code];
	      createDealUrl.setQueryParam(paramName, client.id);
	    });
	    SidePanel.Instance.open(createDealUrl.toString(), {
	      events: {
	        onLoad: ({
	          slider
	        }) => {
	          slider.getWindow().BX.Event.EventEmitter.subscribe('onCrmEntityCreate', event => {
	            const [data] = event.getData();
	            onLoad({
	              isDeal: data.entityTypeName === booking_const.CrmEntity.Deal,
	              isCanceled: data.isCanceled,
	              itemIdFromQuery: parseInt(new main_core.Uri(data.sliderUrl).getQueryParam(itemIdQueryParamName), 10),
	              dealData: this.mapEntityInfoToDeal(data.entityInfo)
	            });
	          });
	        },
	        onClose: () => onClose()
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
	}

	var _bookingId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bookingId");
	var _deal = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("deal");
	var _booking = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("booking");
	class BookingDealHelper extends DealHelper {
	  constructor(bookingId) {
	    super();
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
	    super.openDealSidePanel({
	      deal: babelHelpers.classPrivateFieldLooseBase(this, _deal)[_deal],
	      onClose: async deal => {
	        if (deal != null && deal.value) {
	          void booking_provider_service_bookingService.bookingService.getById(babelHelpers.classPrivateFieldLooseBase(this, _bookingId)[_bookingId]);
	        }
	      }
	    });
	  }
	  createDeal() {
	    var _babelHelpers$classPr;
	    const itemIdQueryParamName = 'bookingId';
	    super.createCrmDeal({
	      itemIdQueryParamName,
	      itemId: babelHelpers.classPrivateFieldLooseBase(this, _bookingId)[_bookingId],
	      clients: ((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _booking)[_booking]) == null ? void 0 : _babelHelpers$classPr.clients) || [],
	      onLoad: async data => {
	        if (!data.isDeal || babelHelpers.classPrivateFieldLooseBase(this, _bookingId)[_bookingId] !== data.itemIdFromQuery) {
	          return;
	        }
	        await this.saveDeal(data.dealData);
	      },
	      onClose: async () => {
	        var _babelHelpers$classPr2;
	        if ((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _deal)[_deal]) != null && _babelHelpers$classPr2.value) {
	          await this.saveDeal(babelHelpers.classPrivateFieldLooseBase(this, _deal)[_deal]);
	        }
	      }
	    });
	  }
	  saveDeal(dealData) {
	    const externalData = babelHelpers.classPrivateFieldLooseBase(this, _booking)[_booking].externalData.filter(data => data.entityTypeId !== booking_const.CrmEntity.Deal);
	    if (dealData) {
	      externalData.push(dealData);
	    }
	    void booking_provider_service_bookingService.bookingService.update({
	      id: babelHelpers.classPrivateFieldLooseBase(this, _bookingId)[_bookingId],
	      externalData
	    });
	  }
	}
	function _get_deal() {
	  var _babelHelpers$classPr3, _babelHelpers$classPr4;
	  return ((_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _booking)[_booking]) == null ? void 0 : (_babelHelpers$classPr4 = _babelHelpers$classPr3.externalData) == null ? void 0 : _babelHelpers$classPr4.find(data => data.entityTypeId === booking_const.CrmEntity.Deal)) || null;
	}
	function _get_booking() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.Bookings}/getById`](babelHelpers.classPrivateFieldLooseBase(this, _bookingId)[_bookingId]);
	}

	var _waitListItemId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("waitListItemId");
	var _deal$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("deal");
	var _waitListItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("waitListItem");
	class WaitListDealHelper extends DealHelper {
	  constructor(waitListItemId) {
	    super();
	    Object.defineProperty(this, _waitListItem, {
	      get: _get_waitListItem,
	      set: void 0
	    });
	    Object.defineProperty(this, _deal$1, {
	      get: _get_deal$1,
	      set: void 0
	    });
	    Object.defineProperty(this, _waitListItemId, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _waitListItemId)[_waitListItemId] = waitListItemId;
	  }
	  hasDeal() {
	    return Boolean(babelHelpers.classPrivateFieldLooseBase(this, _deal$1)[_deal$1]);
	  }
	  openDeal() {
	    super.openDealSidePanel({
	      deal: babelHelpers.classPrivateFieldLooseBase(this, _deal$1)[_deal$1],
	      onClose: async deal => {
	        if (deal != null && deal.value) {
	          await booking_provider_service_waitListService.waitListService.getById(babelHelpers.classPrivateFieldLooseBase(this, _waitListItemId)[_waitListItemId]);
	        }
	      }
	    });
	  }
	  createDeal() {
	    var _babelHelpers$classPr;
	    const itemIdQueryParamName = 'waitListItemId';
	    super.createCrmDeal({
	      itemIdQueryParamName,
	      itemId: babelHelpers.classPrivateFieldLooseBase(this, _waitListItemId)[_waitListItemId],
	      clients: ((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _waitListItem)[_waitListItem]) == null ? void 0 : _babelHelpers$classPr.clients) || [],
	      onLoad: async data => {
	        if (!data.isDeal || babelHelpers.classPrivateFieldLooseBase(this, _waitListItemId)[_waitListItemId] !== data.itemIdFromQuery) {
	          return;
	        }
	        await this.saveDeal(data.dealData);
	      },
	      onClose: async () => {
	        var _babelHelpers$classPr2;
	        if ((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _deal$1)[_deal$1]) != null && _babelHelpers$classPr2.value) {
	          await this.saveDeal(babelHelpers.classPrivateFieldLooseBase(this, _deal$1)[_deal$1]);
	        }
	      }
	    });
	  }
	  async saveDeal(dealData) {
	    const externalData = dealData ? [dealData] : [];
	    await booking_provider_service_waitListService.waitListService.update({
	      id: babelHelpers.classPrivateFieldLooseBase(this, _waitListItemId)[_waitListItemId],
	      externalData
	    });
	  }
	}
	function _get_deal$1() {
	  var _babelHelpers$classPr3, _babelHelpers$classPr4;
	  return ((_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _waitListItem)[_waitListItem]) == null ? void 0 : (_babelHelpers$classPr4 = _babelHelpers$classPr3.externalData) == null ? void 0 : _babelHelpers$classPr4.find(data => data.entityTypeId === booking_const.CrmEntity.Deal)) || null;
	}
	function _get_waitListItem() {
	  return booking_core.Core.getStore().getters[`${booking_const.Model.WaitList}/getById`](babelHelpers.classPrivateFieldLooseBase(this, _waitListItemId)[_waitListItemId]);
	}

	exports.DealHelper = DealHelper;
	exports.BookingDealHelper = BookingDealHelper;
	exports.WaitListDealHelper = WaitListDealHelper;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {}),BX,BX.SidePanel,BX.Booking.Provider.Service,BX.Booking,BX.Booking.Const,BX.Booking.Provider.Service));
//# sourceMappingURL=deal-helper.bundle.js.map
