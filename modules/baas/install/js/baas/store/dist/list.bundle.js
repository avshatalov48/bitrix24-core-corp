this.BX = this.BX || {};
this.BX.Baas = this.BX.Baas || {};
(function (exports,ui_icons_b24,ui_progressbar,main_date,ui_label,bitrix24_license,ui_notification,ui_analytics,ui_buttons,main_popup,ui_iconSet_api_core,ui_popupWithHeader,ui_infoHelper,main_core,main_core_events) {
	'use strict';

	class PresenterDefault {
	  constructor(serviceCode, serviceData, dataProviderPromiseCreator = null) {
	    this.serviceData = null;
	    this.dataProviderPromiseCreator = null;
	    this.serviceCode = serviceCode;
	    this.serviceData = {
	      ...serviceData
	    };
	    this.dataProviderPromiseCreator = dataProviderPromiseCreator;
	  }
	  adjustServiceData(serviceData) {}
	  getPopup() {
	    return new main_popup.Popup();
	  }
	  toggle(node) {}
	  show(node) {}
	  hide() {}
	}

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4,
	  _t5,
	  _t6;
	var _purchasedPackages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("purchasedPackages");
	class PurchaseTileDefault extends main_core_events.EventEmitter {
	  // 'Y-m-d';

	  constructor(purchasedPackages) {
	    super();
	    this.FORMAT_DATE = 'YYYY-MM-DD';
	    Object.defineProperty(this, _purchasedPackages, {
	      writable: true,
	      value: []
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _purchasedPackages)[_purchasedPackages] = purchasedPackages;
	    this.setEventNamespace('BX.Baas');
	  }
	  prepareGroupedPurchases(purchasedPackages) {
	    const groupedPackageInfo = {
	      startDate: new Date(),
	      expirationDate: new Date(),
	      actual: 'N',
	      services: {}
	    };
	    purchasedPackages.forEach(pack => {
	      const startDate = main_date.DateTimeFormat.parse(pack.startDate, false, this.FORMAT_DATE);
	      const expirationDate = main_date.DateTimeFormat.parse(pack.expirationDate, false, this.FORMAT_DATE);
	      if (startDate < groupedPackageInfo.startDate) {
	        groupedPackageInfo.startDate = startDate;
	      }
	      if (groupedPackageInfo.expirationDate < expirationDate) {
	        groupedPackageInfo.expirationDate = expirationDate;
	      }
	      const actual = pack.actual === 'Y' || pack.actual === true;
	      groupedPackageInfo.actual = actual ? 'Y' : groupedPackageInfo.actual;
	      const services = main_core.Type.isArray(pack.services) ? pack.services : Object.values(pack.services);
	      services.forEach(service => {
	        const serviceCode = service.code;
	        groupedPackageInfo.services[serviceCode] = groupedPackageInfo.services[serviceCode] || {
	          code: serviceCode,
	          current: 0,
	          maximal: 0
	        };
	        groupedPackageInfo.services[serviceCode].current += parseInt(service.current, 10);
	        groupedPackageInfo.services[serviceCode].maximal += parseInt(service.maximal, 10);
	      });
	    });
	    return groupedPackageInfo;
	  }
	  getGroupedPurchases() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _purchasedPackages)[_purchasedPackages];
	  }
	  getPurchaseCodes() {
	    return this.getGroupedPurchases().map(pack => pack.purchaseCode).join(',');
	  }
	  getContainer() {
	    const pack = this.prepareGroupedPurchases(this.getGroupedPurchases());
	    pack.startDate = main_date.DateTimeFormat.format(main_date.DateTimeFormat.getFormat('LONG_DATE_FORMAT'), pack.startDate);
	    pack.expirationDate = main_date.DateTimeFormat.format(main_date.DateTimeFormat.getFormat('LONG_DATE_FORMAT'), pack.expirationDate);
	    const count = this.getGroupedPurchases().length;
	    const multiple = this.getGroupedPurchases().length > 1;
	    const serviceGrouped = {
	      current: 0,
	      maximal: 0,
	      active: 'N'
	    };
	    Object.keys(pack.services).forEach(serviceCode => {
	      const service = pack.services[serviceCode];
	      serviceGrouped.current += parseInt(service.current, 10);
	      serviceGrouped.maximal += parseInt(service.maximal, 10);
	    });
	    if (multiple) {
	      const modifiedClass = count > 2 ? '--more' : '--two';
	      return main_core.Tag.render(_t || (_t = _`
				<div class="ui-popupcomponentmaker__content--section ${0}">
					<div class="ui-popupcomponentmaker__content--section-item">
						<div class="ui-popupconstructor-content-item-wrapper">
							<div class="ui-popupconstructor-content-item-wrapper_information">
								<div class="ui-popupconstructor-content-item-wrapper-title">
									<div class="ui-popupconstructor-content-item__title">
										${0}
									</div>
									<div class="ui-popupconstructor-content-item-subject">
										<div class="ui-label ui-label-success ui-label-sm --active ui-label-fill">
											<div class="ui-label-status"></div>
											<span class="ui-label-inner">${0}</span>
										</div>
										<button class="ui-popupconstructor-content-item-menu" style="display: none;" type="button"></button>
									</div>
								</div>
								<div class="ui-popupconstructor-content-item-progressbar">${0}</div>
								<div class="ui-popupconstructor-content-item-limit">
									${0}
								</div>
							</div>
						</div>
					</div>
				</div>
			`), modifiedClass, [main_core.Loc.getMessage('BAAS_WIDGET_PURCHASES_TITLE'), ': ', count].join(''), main_core.Loc.getMessage('BAAS_WIDGET_PURCHASES_ARE_ACTIVE'), this.createProgressBar(serviceGrouped.current, serviceGrouped.maximal).getContainer(), this.getLeftUnitsString(serviceGrouped, pack));
	    }
	    return main_core.Tag.render(_t2 || (_t2 = _`
			<div class="ui-popupcomponentmaker__content--section">
				<div class="ui-popupcomponentmaker__content--section-item">
					<div class="ui-popupconstructor-content-item-wrapper">
						<div class="ui-popupconstructor-content-item-wrapper_information">
							<div class="ui-popupconstructor-content-item-wrapper-title">
								<div class="ui-popupconstructor-content-item__title">
									${0}
								</div>
								<div class="ui-popupconstructor-content-item-subject">
									${0}
									<button class="ui-popupconstructor-content-item-menu" style="display: none;" type="button"></button>
								</div>
							</div>
							<div class="ui-popupconstructor-content-item-progressbar">${0}</div>
							<div class="ui-popupconstructor-content-item-limit">
								${0}
							</div>
						</div>
					</div>
				</div>
			</div>
		`), main_core.Loc.getMessage('BAAS_WIDGET_PURCHASE_TITLE'), this.renderStatusLabel(pack), this.createProgressBar(serviceGrouped.current, serviceGrouped.maximal).getContainer(), this.getLeftUnitsString(serviceGrouped, pack));
	  }
	  getLeftUnitsString(serviceGrouped, pack) {
	    return `
			${main_core.Loc.getMessage('BAAS_WIDGET_PURCHASE_LEFT_STATUS', {
      '#left#': `<span class="ui-popupconstructor-content-item-num">${serviceGrouped.current}</span>`,
      '#total#': `<span class="ui-popupconstructor-content-item-num">${serviceGrouped.maximal}</span>`,
      '#date#': String(pack.expirationDate)
    })}
		`;
	  }
	  getData() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _purchasedPackages)[_purchasedPackages];
	  }
	  createProgressBar(current, maximal) {
	    return new ui_progressbar.ProgressBar({
	      value: Math.round(current / maximal * 100),
	      size: 4
	    });
	  }
	  renderStatusLabel(pack) {
	    const firstService = Object.values(pack.services)[0];
	    const isActual = pack.actual === 'Y';
	    const current = firstService.current;
	    if (isActual && current > 0) {
	      const limitPercentLeft = current / firstService.maximal * 100;
	      if (limitPercentLeft <= 20) {
	        return main_core.Tag.render(_t3 || (_t3 = _`
					<div class="ui-label ui-label-success ui-label-sm --active ui-label-fill">
						<div class="ui-label-status"></div>
						<span class="ui-label-inner">${0}</span>
					</div>
				`), main_core.Loc.getMessage('BAAS_WIDGET_PURCHASE_LIMIT_IS_ALMOST_EXCEEDED'));
	      }
	      return main_core.Tag.render(_t4 || (_t4 = _`
				<div class="ui-label ui-label-success ui-label-sm --active ui-label-fill">
					<div class="ui-label-status"></div>
					<span class="ui-label-inner">${0}</span>
				</div>
			`), main_core.Loc.getMessage('BAAS_WIDGET_PURCHASE_IS_ACTIVE'));
	    }
	    if (isActual && current <= 0) {
	      return main_core.Tag.render(_t5 || (_t5 = _`
				<div class="ui-label ui-label-sm --exceeded ui-label-fill">
					<div class="ui-label-status"></div>
					<span class="ui-label-inner">${0}</span>
				</div>
			`), main_core.Loc.getMessage('BAAS_WIDGET_PURCHASE_LIMIT_IS_EXCEEDED'));
	    }
	    return main_core.Tag.render(_t6 || (_t6 = _`
			<div class="ui-label ui-label-success ui-label-sm --paid ui-label-fill">
				<div class="ui-label-status"></div>
				<span class="ui-label-inner">${0}</span>
			</div>
		`), main_core.Loc.getMessage('BAAS_WIDGET_PURCHASES_ARE_PAID'));
	  }
	}

	let _$1 = t => t,
	  _t$1,
	  _t2$1,
	  _t3$1;
	var _groupPackages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("groupPackages");
	var _calculateActiveResult = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("calculateActiveResult");
	var _calculateNextResult = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("calculateNextResult");
	var _prepareActiveAndFuturePurchases = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareActiveAndFuturePurchases");
	var _getStatusLabels = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getStatusLabels");
	var _createLabel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createLabel");
	class PurchaseTileAnnual extends PurchaseTileDefault {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _createLabel, {
	      value: _createLabel2
	    });
	    Object.defineProperty(this, _getStatusLabels, {
	      value: _getStatusLabels2
	    });
	    Object.defineProperty(this, _prepareActiveAndFuturePurchases, {
	      value: _prepareActiveAndFuturePurchases2
	    });
	    Object.defineProperty(this, _calculateNextResult, {
	      value: _calculateNextResult2
	    });
	    Object.defineProperty(this, _calculateActiveResult, {
	      value: _calculateActiveResult2
	    });
	    Object.defineProperty(this, _groupPackages, {
	      value: _groupPackages2
	    });
	  }
	  getContainer() {
	    const packageInfo = babelHelpers.classPrivateFieldLooseBase(this, _prepareActiveAndFuturePurchases)[_prepareActiveAndFuturePurchases](this.getGroupedPurchases());
	    const count = this.getGroupedPurchases().length;
	    const modifiedClass = count > 2 ? '--more' : '--two';
	    const current = packageInfo.active ? packageInfo.active.current : packageInfo.next.current;
	    const maximal = packageInfo.active ? packageInfo.active.maximal : packageInfo.next.maximal;
	    const theDate = packageInfo.active ? packageInfo.active.expirationDate : packageInfo.next.startDate;
	    const expirationDate = packageInfo.expirationDate;
	    return main_core.Tag.render(_t$1 || (_t$1 = _$1`
			<div class="ui-popupcomponentmaker__content--section ${0}">
				<div class="ui-popupcomponentmaker__content--section-item">
					<div class="ui-popupconstructor-content-item-wrapper">
						<div class="ui-popupconstructor-content-item-wrapper_information">
							<div class="ui-popupconstructor-content-item-wrapper-title">
								<div class="ui-popupconstructor-content-item__title">
									${0}
								</div>
								<div class="ui-popupconstructor-content-item-subject">
									${0}
								</div>
							</div>
							<div class="ui-popupconstructor-content-item-progressbar">${0}</div>
							${0}
							<div class="ui-popupconstructor-content-item-limit">
								${0}
							</div>
						</div>
					</div>
				</div>
			</div>
		`), modifiedClass, main_core.Loc.getMessage('BAAS_WIDGET_PURCHASE_TITLE'), babelHelpers.classPrivateFieldLooseBase(this, _getStatusLabels)[_getStatusLabels](packageInfo).map(label => label.render()), this.createProgressBar(current, maximal).getContainer(), packageInfo.active ? main_core.Tag.render(_t2$1 || (_t2$1 = _$1`
								<div class="ui-popupconstructor-content-item-limit">
									${0}
								</div>
							`), main_core.Loc.getMessage('BAAS_WIDGET_PURCHASE_LEFT_STATUS', {
	      '#left#': `<span class="ui-popupconstructor-content-item-num">${current}</span>`,
	      '#total#': `<span class="ui-popupconstructor-content-item-num">${maximal}</span>`,
	      '#date#': `<span>${theDate}</span>`
	    })) : main_core.Tag.render(_t3$1 || (_t3$1 = _$1`
								<div class="ui-popupconstructor-content-item-limit">
									<span>${0} </span>
										${0}
								</div>
							`), this.getFutureUnitsLabel(), main_core.Loc.getMessage('BAAS_WIDGET_PURCHASE_WILL_BE_AVAILABLE_STATUS', {
	      '#left#': `<span class="ui-popupconstructor-content-item-num">${current}</span>`,
	      '#total#': `<span class="ui-popupconstructor-content-item-num">${maximal}</span>`,
	      '#date#': `<span class="ui-popupconstructor-content-item-date">${theDate}</span>`
	    })), main_core.Loc.getMessage('BAAS_WIDGET_PURCHASE_ANNUAL_LIFETIME', {
	      '#date#': `<span class="ui-popupconstructor-content-item-date">${expirationDate}</span>`
	    }));
	  }
	  getFutureUnitsLabel() {
	    return main_core.Loc.getMessage('BAAS_WIDGET_PURCHASES_FUTURE_UNITS');
	  }
	}
	function _groupPackages2(purchasedPackages) {
	  const rawActivePacks = {};
	  const rawInactivePacks = {};
	  let expirationDateObj = new Date();
	  purchasedPackages.forEach(rawPack => {
	    const actual = rawPack.actual === 'Y' || rawPack.actual === true;
	    const pack = {
	      ...rawPack
	    };
	    if (actual) {
	      var _rawActivePacks$expDa;
	      const expDateActive = main_date.DateTimeFormat.parse(rawPack.expirationDate, false, this.FORMAT_DATE).getTime();
	      rawActivePacks[expDateActive] = (_rawActivePacks$expDa = rawActivePacks[expDateActive]) != null ? _rawActivePacks$expDa : [];
	      rawActivePacks[expDateActive].push(pack);
	    } else {
	      var _rawInactivePacks$sta;
	      const startDate = main_date.DateTimeFormat.parse(rawPack.startDate, false, this.FORMAT_DATE).getTime();
	      rawInactivePacks[startDate] = (_rawInactivePacks$sta = rawInactivePacks[startDate]) != null ? _rawInactivePacks$sta : [];
	      rawInactivePacks[startDate].push(pack);
	    }
	    const expDate = main_date.DateTimeFormat.parse(rawPack.expirationDate, false, this.FORMAT_DATE);
	    if (expDate > expirationDateObj) {
	      expirationDateObj = expDate;
	    }
	  });
	  return {
	    rawActivePacks,
	    rawInactivePacks,
	    expirationDateObj
	  };
	}
	function _calculateActiveResult2(rawActivePacks) {
	  const activePackKeys = Object.keys(rawActivePacks);
	  const active = {
	    current: 0,
	    maximal: 0,
	    expirationDateObject: new Date(),
	    expirationDate: ''
	  };
	  activePackKeys.forEach(key => {
	    const packs = rawActivePacks[key];
	    packs.forEach(pack => {
	      Object.keys(pack.services).forEach(serviceCode => {
	        const service = pack.services[serviceCode];
	        active.current += parseInt(service.current, 10);
	        active.maximal += parseInt(service.maximal, 10);
	      });
	      const expDateActive = main_date.DateTimeFormat.parse(pack.expirationDate, false, this.FORMAT_DATE);
	      if (active.expirationDateObject < expDateActive) {
	        active.expirationDateObject = expDateActive;
	      }
	    });
	  });
	  active.expirationDate = main_date.DateTimeFormat.format(main_date.DateTimeFormat.getFormat('DAY_MONTH_FORMAT'), active.expirationDateObject);
	  return active;
	}
	function _calculateNextResult2(rawInactivePacks) {
	  const inactivePackKeys = Object.keys(rawInactivePacks).sort((a, b) => {
	    return a < b ? -1 : 1;
	  });
	  const next = {
	    current: 0,
	    maximal: 0,
	    startDateObject: new Date(),
	    startDate: ''
	  };
	  const firstDate = inactivePackKeys[0];
	  const futurePacks = rawInactivePacks[firstDate];
	  futurePacks.forEach(pack => {
	    Object.keys(pack.services).forEach(serviceCode => {
	      const service = pack.services[serviceCode];
	      next.current += parseInt(service.current, 10);
	      next.maximal += parseInt(service.maximal, 10);
	    });
	    next.startDate = pack.startDate;
	  });
	  return next;
	}
	function _prepareActiveAndFuturePurchases2(purchasedPacks) {
	  const {
	    rawActivePacks,
	    rawInactivePacks,
	    expirationDateObj
	  } = babelHelpers.classPrivateFieldLooseBase(this, _groupPackages)[_groupPackages](purchasedPacks);
	  const result = {
	    expirationDate: main_date.DateTimeFormat.format(main_date.DateTimeFormat.getFormat('LONG_DATE_FORMAT'), expirationDateObj)
	  };
	  const active = babelHelpers.classPrivateFieldLooseBase(this, _calculateActiveResult)[_calculateActiveResult](rawActivePacks);
	  if (active) {
	    result.active = active;
	  } else {
	    const next = babelHelpers.classPrivateFieldLooseBase(this, _calculateNextResult)[_calculateNextResult](rawInactivePacks);
	    if (next) {
	      result.next = next;
	    }
	  }
	  return result;
	}
	function _getStatusLabels2(purchasesResult) {
	  const labels = [];
	  const isActual = Boolean(purchasesResult.active);
	  if (!isActual) {
	    labels.push(babelHelpers.classPrivateFieldLooseBase(this, _createLabel)[_createLabel]('BAAS_WIDGET_PURCHASES_ARE_PAID', '--paid'));
	    return labels;
	  }
	  const hasZeroBalance = isActual && purchasesResult.active.current === 0;
	  if (isActual && !hasZeroBalance && purchasesResult.active.current / purchasesResult.active.maximal * 100 <= 20) {
	    labels.push(babelHelpers.classPrivateFieldLooseBase(this, _createLabel)[_createLabel]('BAAS_WIDGET_PURCHASE_LIMIT_IS_ALMOST_EXCEEDED', '--almost'));
	  }
	  if (hasZeroBalance) {
	    labels.push(babelHelpers.classPrivateFieldLooseBase(this, _createLabel)[_createLabel]('BAAS_WIDGET_PURCHASE_LIMIT_IS_EXCEEDED_THIS_MONTH', '--exceeded'));
	  }
	  labels.unshift(babelHelpers.classPrivateFieldLooseBase(this, _createLabel)[_createLabel]('BAAS_WIDGET_PURCHASE_IS_ACTIVE', '--active'));
	  return labels;
	}
	function _createLabel2(message, styleClass) {
	  return new ui_label.Label({
	    text: main_core.Loc.getMessage(message),
	    size: ui_label.LabelSize.SM,
	    fill: true,
	    customClass: styleClass
	  });
	}

	let _$2 = t => t,
	  _t$2;
	var _package = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("package");
	var _openNewPurchase = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openNewPurchase");
	class Purchases extends main_core_events.EventEmitter {
	  constructor(packageItem) {
	    super();
	    Object.defineProperty(this, _openNewPurchase, {
	      value: _openNewPurchase2
	    });
	    this.FORMAT_DATE = 'YYYY-MM-DD';
	    Object.defineProperty(this, _package, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _package)[_package] = packageItem;
	    this.setEventNamespace('BX.Baas');
	  }
	  getContainer() {
	    const data = babelHelpers.classPrivateFieldLooseBase(this, _package)[_package].getData();
	    return main_core.Tag.render(_t$2 || (_t$2 = _$2`
			<div data-bx-package="${0}">
				<div class="ui-popupconstructor-content-main-title">
					${0}
					<div class="ui-popupconstructor-content-item__title">${0}</div>
				</div>
				<div class="ui-popupcomponentmaker__content-wrap">
					<div class="ui-popupcomponentmaker__content">
						${0}
						<div class="ui-popupcomponentmaker__content--section --add" onclick="${0}">
							<div class="ui-popupcomponentmaker__content--section-item">
								<div class="ui-popupconstructor-content-item-wrapper">
									<div class="ui-popupconstructor-content-item-add">${0}</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		`), main_core.Text.encode(data.code), babelHelpers.classPrivateFieldLooseBase(this, _package)[_package].getIcon(), main_core.Text.encode(data.title), this.renderPurchases(), babelHelpers.classPrivateFieldLooseBase(this, _openNewPurchase)[_openNewPurchase].bind(this), main_core.Loc.getMessage('BAAS_WIDGET_PURCHASE_ADD'));
	  }
	  renderPurchases() {
	    const data = this.getData();
	    const groupedPackages = this.groupPackages(data.purchaseInfo.purchases);
	    return [...groupedPackages].map(group => this.createPurchasedPackageTileContainer(...group));
	  }
	  createPurchasedPackageTileContainer(groupedPackages, groupedByPurchase = false) {
	    const fabric = groupedByPurchase === true ? this.getAnnualTileFabric(groupedPackages) : this.getDefaultTileFabric(groupedPackages);
	    const container = fabric.getContainer();
	    container.dataset.bxRole = 'purchase-tile';
	    container.dataset.bxPurchasecodes = fabric.getPurchaseCodes();
	    return container;
	  }
	  setDefaultTileFabric(classFabric) {
	    this.getDefaultTileFabric = classFabric;
	  }
	  getDefaultTileFabric(groupedPackages) {
	    return new PurchaseTileDefault(groupedPackages);
	  }
	  setAnnualTileFabric(classFabric) {
	    this.getAnnualTileFabric = classFabric;
	  }
	  getAnnualTileFabric(groupedPackages) {
	    return new PurchaseTileAnnual(groupedPackages);
	  }
	  getData() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _package)[_package].getData();
	  }
	  groupPackages(purchases) {
	    const singlePackages = [];
	    const groupedPackages = {};
	    [...purchases].forEach(purchase => {
	      [...purchase].forEach(purchasedPackage => {
	        let id = main_date.DateTimeFormat.parse(purchasedPackage.expirationDate, false, this.FORMAT_DATE).getTime();
	        if (purchase.length <= 1) {
	          if (!singlePackages[id]) {
	            singlePackages[id] = [];
	          }
	          singlePackages[id].push(purchasedPackage);
	        } else {
	          id = purchasedPackage.purchaseCode;
	          if (!groupedPackages[id]) {
	            groupedPackages[id] = {};
	          }
	          const time = main_date.DateTimeFormat.parse(purchasedPackage.startDate, false, this.FORMAT_DATE).getTime();
	          groupedPackages[id][time] = purchasedPackage;
	        }
	      });
	    });
	    const packageComparator = (a, b) => {
	      const getSortingRank = pkg => {
	        const hasService = pkg.services && pkg.services[0];
	        switch (true) {
	          case hasService && pkg.services[0].current > 0 && pkg.actual === 'Y':
	            return 0;
	          case pkg.actual === 'N':
	            return 1;
	          case hasService && pkg.services[0].current === 0:
	            return 2;
	          default:
	            return 3;
	        }
	      };
	      const rankA = getSortingRank(a);
	      const rankB = getSortingRank(b);
	      if (rankA !== rankB) {
	        return rankA - rankB;
	      }
	      const dateA = main_date.DateTimeFormat.parse(a.expirationDate, false, this.FORMAT_DATE).getTime();
	      const dateB = main_date.DateTimeFormat.parse(b.expirationDate, false, this.FORMAT_DATE).getTime();
	      return dateA - dateB;
	    };
	    singlePackages.sort(packageComparator);
	    const finalPackages = singlePackages;
	    const groupedPackageTopElements = [];
	    Object.keys(groupedPackages).forEach(key => {
	      const sortedKeys = Object.keys(groupedPackages[key]).sort((a, b) => {
	        return a < b ? -1 : 1;
	      });
	      const earliestDate = sortedKeys[0];
	      const topElement = {
	        ...groupedPackages[key][earliestDate]
	      };
	      topElement.groupKey = key;
	      topElement.earliestDate = earliestDate;
	      groupedPackageTopElements.push(topElement);
	    });
	    groupedPackageTopElements.sort(packageComparator).forEach(topElement => {
	      let earliestDate = topElement.earliestDate;
	      while (finalPackages[earliestDate]) {
	        earliestDate += 1;
	      }
	      finalPackages[earliestDate] = groupedPackages[topElement.groupKey];
	    });
	    const sortedKeys = Object.keys(finalPackages);
	    return [...sortedKeys].map(key => {
	      const annual = !main_core.Type.isArray(finalPackages[key]);
	      let packages = finalPackages[key];
	      if (annual) {
	        packages = Object.values(finalPackages[key]);
	      }
	      return [packages, annual && packages.length > 1];
	    });
	  }
	}
	function _openNewPurchase2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _package)[_package].openNewPurchase();
	}

	class PurchaseTileDiskOoEdit extends PurchaseTileDefault {
	  getLeftUnitsString(serviceGrouped, pack) {
	    return `
			${main_core.Loc.getMessage('BAAS_WIDGET_PURCHASE_DISK_LEFT_STATUS', {
      '#total#': `<span class="ui-popupconstructor-content-item-num">${serviceGrouped.maximal}</span>`,
      '#date#': `<span>${pack.expirationDate}</span>`
    })}`;
	  }
	}

	class PurchaseTileAiCopilotToken extends PurchaseTileDefault {
	  getLeftUnitsString(serviceGrouped, pack) {
	    return `
			${main_core.Loc.getMessage('BAAS_WIDGET_AI_PURCHASE_LEFT_STATUS', {
      '#left#': `<span class="ui-popupconstructor-content-item-num">${serviceGrouped.current}</span>`,
      '#total#': `<span class="ui-popupconstructor-content-item-num">${serviceGrouped.maximal}</span>`,
      '#date#': `<span>${pack.expirationDate}</span>`
    })}`;
	  }
	}

	class PurchaseTileAiCopilotAnnualToken extends PurchaseTileAnnual {
	  getLeftUnitsString(serviceGrouped, pack) {
	    return `
			${main_core.Loc.getMessage('BAAS_WIDGET_AI_PURCHASE_LEFT_STATUS', {
      '#left#': `<span class="ui-popupconstructor-content-item-num">${serviceGrouped.current}</span>`,
      '#total#': `<span class="ui-popupconstructor-content-item-num">${serviceGrouped.maximal}</span>`,
      '#date#': `<span>${pack.expirationDate}</span>`
    })}`;
	  }
	}

	let _$3 = t => t,
	  _t$3,
	  _t2$2;
	class PurchaseTileDocumentgeneratorFastTransform extends PurchaseTileDefault {
	  getContainer() {
	    const pack = this.prepareGroupedPurchases(this.getGroupedPurchases());
	    pack.startDate = main_date.DateTimeFormat.format(main_date.DateTimeFormat.getFormat('LONG_DATE_FORMAT'), pack.startDate);
	    pack.expirationDate = main_date.DateTimeFormat.format(main_date.DateTimeFormat.getFormat('LONG_DATE_FORMAT'), pack.expirationDate);
	    const count = this.getGroupedPurchases().length;
	    const multiple = this.getGroupedPurchases().length > 1;
	    if (multiple) {
	      const modifiedClass = count > 2 ? '--more' : '--two';
	      return main_core.Tag.render(_t$3 || (_t$3 = _$3`
				<div class="ui-popupcomponentmaker__content--section ${0}">
					<div class="ui-popupcomponentmaker__content--section-item">
						<div class="ui-popupconstructor-content-item-wrapper">
							<div class="ui-popupconstructor-content-item-wrapper_information">
								<div class="ui-popupconstructor-content-item-wrapper-title">
									<div class="ui-popupconstructor-content-item__title">
										${0}
									</div>
									<div class="ui-popupconstructor-content-item-subject">
										${0}
										<button class="ui-popupconstructor-content-item-menu" style="display: none;" type="button"></button>
									</div>
								</div>
								<div class="ui-popupconstructor-content-item-limit">
									<span>${0} </span>
									<span class="ui-popupconstructor-content-item-date">${0}</span>
								</div>
							</div>
						</div>
					</div>
				</div>
			`), modifiedClass, [main_core.Loc.getMessage('BAAS_WIDGET_PURCHASES_TITLE'), ': ', count].join(''), this.renderStatusLabel(pack), main_core.Loc.getMessage('BAAS_WIDGET_DFT_PURCHASE_LEFT'), pack.expirationDate);
	    }
	    return main_core.Tag.render(_t2$2 || (_t2$2 = _$3`
			<div class="ui-popupcomponentmaker__content--section">
				<div class="ui-popupcomponentmaker__content--section-item">
					<div class="ui-popupconstructor-content-item-wrapper">
						<div class="ui-popupconstructor-content-item-wrapper_information">
							<div class="ui-popupconstructor-content-item-wrapper-title">
								<div class="ui-popupconstructor-content-item__title">${0}</div>
								<div class="ui-popupconstructor-content-item-subject">
									<div class="ui-label ui-label-success ui-label-sm --active ui-label-fill">
										<div class="ui-label-status"></div>
										<span class="ui-label-inner">${0}</span>
									</div>
								</div>
							</div>
							<div class="ui-popupconstructor-content-item-limit">
								<span>${0} </span>
								<span class="ui-popupconstructor-content-item-date">${0}</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		`), main_core.Loc.getMessage('BAAS_WIDGET_PURCHASE_TITLE'), main_core.Loc.getMessage('BAAS_WIDGET_PURCHASE_IS_ACTIVE'), main_core.Loc.getMessage('BAAS_WIDGET_DFT_PURCHASE_LEFT'), pack.expirationDate);
	  }
	}

	var _detectServiceCode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("detectServiceCode");
	class PurchasesFactory {
	  constructor() {
	    Object.defineProperty(this, _detectServiceCode, {
	      value: _detectServiceCode2
	    });
	  }
	  create(packageItem) {
	    const serviceCode = babelHelpers.classPrivateFieldLooseBase(this, _detectServiceCode)[_detectServiceCode](packageItem.getData().purchaseInfo.purchases);
	    const purchase = new Purchases(packageItem);
	    if (serviceCode === 'ai_copilot_token') {
	      purchase.setDefaultTileFabric((...params) => new PurchaseTileAiCopilotToken(...params));
	      purchase.setAnnualTileFabric((...params) => new PurchaseTileAiCopilotAnnualToken(...params));
	    } else if (serviceCode === 'documentgenerator_fast_transform') {
	      purchase.setDefaultTileFabric((...params) => new PurchaseTileDocumentgeneratorFastTransform(...params));
	      purchase.groupPackages = purchases => {
	        const groupedPackages = [];
	        [...purchases].forEach(purchase => {
	          [...purchase].forEach(purchasedPackage => {
	            groupedPackages.push(purchasedPackage);
	          });
	        });
	        return [[groupedPackages]];
	      };
	    } else if (serviceCode === 'disk_oo_edit') {
	      purchase.setDefaultTileFabric((...params) => new PurchaseTileDiskOoEdit(...params));
	    }
	    return purchase;
	  }
	}
	function _detectServiceCode2(purchases) {
	  let serviceCode = null;
	  let multiple = false;
	  [...purchases].forEach(purchase => {
	    [...purchase].forEach(purchasedPackage => {
	      if (purchasedPackage.services.length > 1) {
	        multiple = true;
	      } else {
	        const thisServiceCode = purchasedPackage.services[0].code;
	        if (serviceCode === null) {
	          serviceCode = thisServiceCode;
	        } else if (thisServiceCode !== serviceCode) {
	          multiple = true;
	        }
	      }
	    });
	  });
	  return multiple ? null : serviceCode;
	}

	let _$4 = t => t,
	  _t$4,
	  _t2$3,
	  _t3$2,
	  _t4$1,
	  _t5$1,
	  _t6$1;
	var _cache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _data = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("data");
	var _renderIcon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderIcon");
	var _renderMoreLink = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderMoreLink");
	var _renderButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderButton");
	var _isPaidLicense = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isPaidLicense");
	var _renderButtonDescription = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderButtonDescription");
	var _getPurchaseBlock = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPurchaseBlock");
	var _adjustPackage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("adjustPackage");
	class PackageItem extends main_core_events.EventEmitter {
	  constructor(_data2) {
	    super();
	    Object.defineProperty(this, _adjustPackage, {
	      value: _adjustPackage2
	    });
	    Object.defineProperty(this, _getPurchaseBlock, {
	      value: _getPurchaseBlock2
	    });
	    Object.defineProperty(this, _renderButtonDescription, {
	      value: _renderButtonDescription2
	    });
	    Object.defineProperty(this, _isPaidLicense, {
	      value: _isPaidLicense2
	    });
	    Object.defineProperty(this, _renderButton, {
	      value: _renderButton2
	    });
	    Object.defineProperty(this, _renderMoreLink, {
	      value: _renderMoreLink2
	    });
	    Object.defineProperty(this, _renderIcon, {
	      value: _renderIcon2
	    });
	    Object.defineProperty(this, _cache, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    Object.defineProperty(this, _data, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _data)[_data] = _data2;
	    this.setEventNamespace('BX.Baas');
	    if (BX.PULL && main_core.Extension.getSettings('baas.store').pull) {
	      BX.PULL.extendWatch(main_core.Extension.getSettings('baas.store').pull.channelName);
	      main_core_events.EventEmitter.subscribe('onPullEvent-baas', event => {
	        const [command, params] = event.getData();
	        if (command === 'updateService' && params.packages) {
	          [...params.packages].forEach(packagePullData => {
	            if (packagePullData.code === babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].code) {
	              babelHelpers.classPrivateFieldLooseBase(this, _adjustPackage)[_adjustPackage](packagePullData);
	            }
	          });
	        }
	      });
	    }
	  }
	  getContainer() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('container', () => {
	      const data = babelHelpers.classPrivateFieldLooseBase(this, _data)[_data];
	      return main_core.Tag.render(_t$4 || (_t$4 = _$4`
				<div class="ui-popupconstructor-content-item-wrapper" data-bx-role="package-tile" data-bx-package="${0}">
					<div class="ui-popupconstructor-content-item-wrapper_information">
						<div class="ui-popupconstructor-content-item-wrapper-title">
							${0}
							<div class="ui-popupconstructor-content-item__title">${0}</div>
						</div>
						<div>
							<div class="ui-popupconstructor-content-item__description">
								${0}
								${0}
							</div>
						</div>
						${0}
					</div>
					<div class="ui-popupconstructor-content-item-wrapper_button">
						${0}
						${0}
					</div>
				</div>
			`), main_core.Text.encode(data.code), this.getIcon(), main_core.Text.encode(data.title), main_core.Text.encode(data.description), babelHelpers.classPrivateFieldLooseBase(this, _renderMoreLink)[_renderMoreLink](babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].featurePromotionCode), babelHelpers.classPrivateFieldLooseBase(this, _getPurchaseBlock)[_getPurchaseBlock](), babelHelpers.classPrivateFieldLooseBase(this, _renderButton)[_renderButton](babelHelpers.classPrivateFieldLooseBase(this, _data)[_data]), babelHelpers.classPrivateFieldLooseBase(this, _renderButtonDescription)[_renderButtonDescription](babelHelpers.classPrivateFieldLooseBase(this, _data)[_data]));
	    });
	  }
	  getIcon() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _renderIcon)[_renderIcon](babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].icon);
	  }
	  openNewPurchase() {
	    const purchaseUrl = babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].purchaseUrl;
	    const isBitrix24 = main_core.Extension.getSettings('baas.store').get('isBitrix24License');
	    if (isBitrix24 && !babelHelpers.classPrivateFieldLooseBase(this, _isPaidLicense)[_isPaidLicense]()) {
	      const findPackageCode = /product=(\w+)/gi;
	      const params = {
	        package: babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].code
	      };
	      if (findPackageCode.test(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].purchaseUrl)) {
	        params.package = babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].purchaseUrl.match(findPackageCode)[0].replace('product=', '');
	      }
	      bitrix24_license.License.openPurchasePage(params);
	    } else if (isBitrix24 && main_core.Extension.getSettings('baas.store').get('canBaasOnlyBePurchasedByAdmin') && !main_core.Extension.getSettings('baas.store').get('isCurrentUserAdmin')) {
	      BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('BAAS_WIDGET_ONLY_ADMIN_CAN_PURCHASE_BAAS'),
	        category: 'baas',
	        autoHideDelay: 5000
	      });
	    } else if (purchaseUrl.indexOf('http') === 0) {
	      window.open(purchaseUrl);
	    } else {
	      BX.SidePanel.Instance.open(purchaseUrl, {
	        width: 1250,
	        cacheable: false
	      });
	    }
	    this.emit('onClickToBuyPackage', {
	      packageCode: babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].code
	    });
	  }
	  showPurchases() {
	    main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, this.getFullEventName('onPurchaseShown'), new main_core_events.BaseEvent({
	      data: {
	        package: this,
	        packageCode: babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].code
	      }
	    }));
	    const bindElement = this.getContainer().closest('div.popup-window');
	    const padding = 10;
	    const popupWidth = bindElement.offsetWidth - padding * 2;
	    const popupHeight = bindElement.offsetHeight - padding * 2;
	    const fabric = new PurchasesFactory();
	    const popup = main_popup.PopupManager.create({
	      id: 'purchase',
	      autoHide: true,
	      cacheable: false,
	      closeIcon: true,
	      lightShadow: true,
	      draggable: false,
	      closeByEsc: true,
	      padding: 0,
	      className: '--baas-widget --purchase',
	      content: fabric.create(this).getContainer(),
	      bindElement,
	      offsetTop: 0 - popupHeight - padding,
	      offsetLeft: padding,
	      bindOptions: {
	        position: 'bottom'
	      },
	      width: popupWidth,
	      height: popupHeight,
	      events: {
	        onClose: () => {
	          main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, this.getFullEventName('onPurchaseHidden'), new main_core_events.BaseEvent({
	            data: {
	              package: this
	            }
	          }));
	        }
	      }
	    });
	    popup.show();
	  }
	  getData() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _data)[_data];
	  }
	}
	function _renderIcon2(iconParams) {
	  var _iconParams$style;
	  const params = {};
	  const style = (_iconParams$style = iconParams.style) != null ? _iconParams$style : {};
	  let iconNode = main_core.Tag.render(_t2$3 || (_t2$3 = _$4`
			<div class="ui-popupconstructor-content-item__icon ui-icon-set"></div>
		`));
	  if (main_core.Type.isStringFilled(iconParams.className)) {
	    if (ui_iconSet_api_core.Set[iconParams.className]) {
	      params.icon = ui_iconSet_api_core.Set[iconParams.className];
	      if (iconParams.color) {
	        params.color = iconParams.color;
	      }
	      iconNode = new ui_iconSet_api_core.Icon(params).render();
	    } else {
	      main_core.Dom.addClass(iconNode, iconParams.className);
	    }
	  }
	  main_core.Dom.style(iconNode, style);
	  return iconNode;
	}
	function _renderMoreLink2(featurePromotionCode) {
	  if (!main_core.Type.isStringFilled(featurePromotionCode)) {
	    return null;
	  }
	  const node = main_core.Tag.render(_t3$2 || (_t3$2 = _$4`
			<div class="ui-popupconstructor-content-item__more-link">${0}</div>
		`), main_core.Loc.getMessage('BAAS_WIDGET_MORE_LINK_TITLE'));
	  const onclick = e => {
	    e.stopPropagation();
	    const popup = ui_infoHelper.FeaturePromotersRegistry.getPromoter({
	      code: featurePromotionCode
	    });
	    popup.show();
	  };
	  main_core.Event.bind(node, 'click', onclick);
	  return node;
	}
	function _renderButton2(data) {
	  if (!main_core.Type.isPlainObject(data.price)) {
	    return null;
	  }
	  const button = data.isActive ? new ui_buttons.Button({
	    round: true,
	    text: main_core.Loc.getMessage('BAAS_WIDGET_BUY_BUTTON_TITLE'),
	    size: ui_buttons.Button.Size.EXTRA_SMALL,
	    color: ui_buttons.Button.Color.SUCCESS,
	    noCaps: true,
	    onclick: this.openNewPurchase.bind(this)
	  }) : new ui_buttons.Button({
	    round: true,
	    text: main_core.Loc.getMessage('BAAS_WIDGET_BUY_BUTTON_TITLE'),
	    size: ui_buttons.Button.Size.EXTRA_SMALL,
	    color: ui_buttons.Button.Color.SUCCESS,
	    state: ui_buttons.Button.State.DISABLED,
	    noCaps: true,
	    onclick: () => {
	      return false;
	    }
	  });
	  return button.render();
	}
	function _isPaidLicense2() {
	  return main_core.Extension.getSettings('baas.store').isBaasActive;
	}
	function _renderButtonDescription2(data) {
	  return data.isActive ? main_core.Tag.render(_t4$1 || (_t4$1 = _$4`
			<div class="ui-popupconstructor-content-item__button-description">
				<div>${0}</div>
				${0}
			</div>
		`), data.price.value, data.price.description) : null;
	}
	function _getPurchaseBlock2() {
	  function getStatusLabels(response) {
	    const labels = [];
	    const purchaseInfo = response.purchaseInfo;
	    if (!purchaseInfo || purchaseInfo.purchaseCount <= 0) {
	      return labels;
	    }
	    const isAnnual = response.code === 'COPILOT_Q1000_P12';
	    const hasZeroBalance = purchaseInfo.purchaseBalance === 0;
	    const isExceedAnnual = isAnnual && hasZeroBalance && purchaseInfo.purchases.length === 1 && purchaseInfo.purchases[0].length === 1;
	    if (purchaseInfo.purchaseBalance <= 20 && !hasZeroBalance) {
	      labels.push(new ui_label.Label({
	        text: main_core.Loc.getMessage('BAAS_WIDGET_PURCHASE_LIMIT_IS_ALMOST_EXCEEDED'),
	        size: ui_label.LabelSize.SM,
	        fill: true,
	        customClass: '--almost'
	      }));
	    }
	    if (isExceedAnnual || hasZeroBalance && !isAnnual) {
	      labels.push(new ui_label.Label({
	        text: main_core.Loc.getMessage('BAAS_WIDGET_PURCHASE_LIMIT_IS_EXCEEDED'),
	        size: ui_label.LabelSize.SM,
	        fill: true,
	        customClass: '--exceeded'
	      }));
	    }
	    if (hasZeroBalance && isAnnual) {
	      labels.push(new ui_label.Label({
	        text: main_core.Loc.getMessage('BAAS_WIDGET_PURCHASE_LIMIT_IS_EXCEEDED_THIS_MONTH'),
	        size: ui_label.LabelSize.SM,
	        fill: true,
	        customClass: '--exceeded'
	      }));
	    }
	    const isActiveAnnual = isAnnual && (labels.length === 0 || !isExceedAnnual);
	    const isActiveMonthly = !isAnnual && labels.length === 0;
	    if (isActiveAnnual || isActiveMonthly) {
	      labels.unshift(new ui_label.Label({
	        text: main_core.Loc.getMessage('BAAS_WIDGET_PURCHASE_IS_ACTIVE'),
	        size: ui_label.LabelSize.SM,
	        fill: true,
	        customClass: '--active'
	      }));
	    }
	    return labels;
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('purchase-block', () => {
	    const data = babelHelpers.classPrivateFieldLooseBase(this, _data)[_data];
	    let htmlToRender = main_core.Tag.render(_t5$1 || (_t5$1 = _$4`<div></div>`));
	    const purchaseInfo = data.purchaseInfo;
	    if (main_core.Type.isPlainObject(purchaseInfo) && purchaseInfo.purchaseCount > 0) {
	      const labels = getStatusLabels(data);
	      htmlToRender = main_core.Tag.render(_t6$1 || (_t6$1 = _$4`
					<div class="ui-popupconstructor-content-item__purchase-description" onclick="${0}">
						<span class="ui-link ui-link-dashed">${0}: ${0}</span>
						${0}
					</div>
				`), this.showPurchases.bind(this), main_core.Loc.getMessage('BAAS_WIDGET_PURCHASE_TITLE'), purchaseInfo.purchaseCount, labels.map(label => label.render()));
	    }
	    return htmlToRender;
	  });
	}
	function _adjustPackage2(newPackageData) {
	  babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].purchaseInfo = newPackageData.purchaseInfo;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].has('purchase-block')) {
	    const oldNode = babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].get('purchase-block');
	    babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].delete('purchase-block');
	    const newNode = babelHelpers.classPrivateFieldLooseBase(this, _getPurchaseBlock)[_getPurchaseBlock]();
	    main_core.Dom.replace(oldNode, newNode);
	  }
	}

	var _serviceCode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("serviceCode");
	var _lastContext = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("lastContext");
	var _active = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("active");
	var _send = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("send");
	class Analytics {
	  constructor(serviceCode) {
	    Object.defineProperty(this, _serviceCode, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _lastContext, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _active, {
	      writable: true,
	      value: false
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _serviceCode)[_serviceCode] = serviceCode;
	    babelHelpers.classPrivateFieldLooseBase(this, _lastContext)[_lastContext] = Analytics.CONTEXT_IS_NOT_SET;
	    this.onClickToBuyPackage = this.onClickToBuyPackage.bind(this);
	    this.onPurchaseShown = this.onPurchaseShown.bind(this);
	  }
	  activate() {
	    babelHelpers.classPrivateFieldLooseBase(this, _active)[_active] = true;
	    main_core_events.EventEmitter.subscribe('BX.Baas:onClickToBuyPackage', this.onClickToBuyPackage);
	    main_core_events.EventEmitter.subscribe('BX.Baas:onPurchaseShown', this.onPurchaseShown);
	    return this;
	  }
	  deactivate() {
	    main_core_events.EventEmitter.unsubscribe('BX.Baas:onClickToBuyPackage', this.onClickToBuyPackage);
	    main_core_events.EventEmitter.unsubscribe('BX.Baas:onPurchaseShown', this.onPurchaseShown);
	    babelHelpers.classPrivateFieldLooseBase(this, _active)[_active] = false;
	    return this;
	  }
	  onShowFrom(context) {
	    babelHelpers.classPrivateFieldLooseBase(this, _lastContext)[_lastContext] = context;
	    babelHelpers.classPrivateFieldLooseBase(this.constructor, _send)[_send](this.constructor.EVENT_SHOW_POPUP, babelHelpers.classPrivateFieldLooseBase(this, _serviceCode)[_serviceCode], context);
	  }
	  onPlayerEvents(eventName, additionalVideoParameter) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _active)[_active]) {
	      babelHelpers.classPrivateFieldLooseBase(this.constructor, _send)[_send](eventName, babelHelpers.classPrivateFieldLooseBase(this, _serviceCode)[_serviceCode], babelHelpers.classPrivateFieldLooseBase(this, _lastContext)[_lastContext], additionalVideoParameter ? {
	        p2: additionalVideoParameter
	      } : {});
	    }
	  }
	  onClickToBuyPackage(event) {
	    babelHelpers.classPrivateFieldLooseBase(this.constructor, _send)[_send](this.constructor.EVENT_CLICK_TO_BUY, babelHelpers.classPrivateFieldLooseBase(this, _serviceCode)[_serviceCode], babelHelpers.classPrivateFieldLooseBase(this, _lastContext)[_lastContext], {
	      p2: event.getData().packageCode.replaceAll('_', '-')
	    });
	  }
	  onPurchaseShown(event) {
	    babelHelpers.classPrivateFieldLooseBase(this.constructor, _send)[_send](this.constructor.EVENT_CLICK_TO_VIEW_PURCHASE, babelHelpers.classPrivateFieldLooseBase(this, _serviceCode)[_serviceCode], babelHelpers.classPrivateFieldLooseBase(this, _lastContext)[_lastContext], {
	      p2: event.getData().packageCode.replaceAll('_', '-')
	    });
	  }
	  static createByServiceCode(serviceCodeId) {
	    let serviceCode = null;
	    switch (serviceCodeId) {
	      case 'ai_copilot_token':
	        serviceCode = this.SERVICE_AI_COPILOT_TOKEN;
	        break;
	      case 'disk_oo_edit':
	        serviceCode = this.SERVICE_DISK_OO_EDIT;
	        break;
	      case 'documentgenerator_fast_transform':
	        serviceCode = this.SERVICE_DOCUMENTGENERATOR_FAST_TRANSFORM;
	        break;
	      default:
	        serviceCode = this.SERVICE_ALL;
	        break;
	    }
	    return new this(serviceCode);
	  }
	  static guessContextByServiceCode(serviceCodeId) {
	    let serviceContext = null;
	    switch (serviceCodeId) {
	      case 'ai_copilot_token':
	        serviceContext = this.CONTEXT_STREAM;
	        break;
	      case 'disk_oo_edit':
	        serviceContext = this.CONTEXT_TASKS;
	        break;
	      case 'documentgenerator_fast_transform':
	        serviceContext = this.CONTEXT_CRM;
	        break;
	      default:
	        serviceContext = this.CONTEXT_LICENSE_WIDGET;
	        break;
	    }
	    return serviceContext;
	  }
	}
	function _send2(event, serviceCode, context, additional = {}) {
	  ui_analytics.sendData(Object.assign(additional, {
	    tool: 'boost',
	    category: 'buy',
	    event,
	    type: serviceCode,
	    c_section: context,
	    p1: main_core.Extension.getSettings('baas.store').get('isCurrentUserAdmin') ? 'isAdmin_Y' : 'isAdmin_N'
	  }));
	}
	Object.defineProperty(Analytics, _send, {
	  value: _send2
	});
	Analytics.EVENT_SHOW_POPUP = 'show';
	Analytics.EVENT_CLICK_TO_BUY = 'button_buy_click';
	Analytics.EVENT_CLICK_TO_VIEW_PURCHASE = 'button_click_active_boost';
	Analytics.SERVICE_ALL = 'main';
	Analytics.SERVICE_AI_COPILOT_TOKEN = 'copilot';
	Analytics.SERVICE_DOCUMENTGENERATOR_FAST_TRANSFORM = 'speed';
	Analytics.SERVICE_DISK_OO_EDIT = 'docs';
	Analytics.CONTEXT_IS_NOT_SET = 'notSet';
	Analytics.CONTEXT_LICENSE_WIDGET = 'headerPopup';
	Analytics.CONTEXT_IM_CHAT = 'chat';
	Analytics.CONTEXT_STREAM = 'stream';
	Analytics.CONTEXT_CRM = 'CRM';
	Analytics.CONTEXT_TASKS = 'tasks';

	class PackageItemFactory {
	  create(data) {
	    return new PackageItem(data);
	  }
	}

	var _cache$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _items = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("items");
	class PackageTemplateManager extends ui_popupWithHeader.BaseTemplate {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _cache$1, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    Object.defineProperty(this, _items, {
	      writable: true,
	      value: []
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _items)[_items] = main_core.Type.isPlainObject(options) ? options.items : [];
	  }
	  setOptions(data) {
	    if (data.items) {
	      babelHelpers.classPrivateFieldLooseBase(this, _items)[_items] = main_core.Type.isPlainObject(data.items) ? Object.values(data.items) : main_core.Type.isArray(data.items) ? data.items : [];
	    }
	    super.setOptions(data);
	  }
	  getContent() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].remember('popup-content', () => {
	      const factory = new PackageItemFactory();
	      return babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].map((serviceData, index) => {
	        var _serviceData$styles;
	        const item = factory.create(serviceData);
	        return {
	          html: item.getContainer(),
	          background: (_serviceData$styles = serviceData.styles) == null ? void 0 : _serviceData$styles.background,
	          margin: index === 0 ? '12px 0 0 0' : null
	        };
	      });
	    });
	  }
	}

	let _$5 = t => t,
	  _t$5,
	  _t2$4;
	var _setServiceData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setServiceData");
	var _getAnalytic = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAnalytic");
	var _getLastBoundElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLastBoundElement");
	var _getWidgetData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getWidgetData");
	var _getPopupWithHeader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopupWithHeader");
	var _convertIntoWidgetDataType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("convertIntoWidgetDataType");
	class PresenterPopup extends PresenterDefault {
	  constructor(serviceCode, _serviceData, dataProviderPromiseCreator = null) {
	    super(serviceCode, _serviceData, dataProviderPromiseCreator);
	    Object.defineProperty(this, _convertIntoWidgetDataType, {
	      value: _convertIntoWidgetDataType2
	    });
	    Object.defineProperty(this, _getPopupWithHeader, {
	      value: _getPopupWithHeader2
	    });
	    Object.defineProperty(this, _getWidgetData, {
	      value: _getWidgetData2
	    });
	    Object.defineProperty(this, _getLastBoundElement, {
	      value: _getLastBoundElement2
	    });
	    Object.defineProperty(this, _getAnalytic, {
	      value: _getAnalytic2
	    });
	    Object.defineProperty(this, _setServiceData, {
	      value: _setServiceData2
	    });
	    this.cache = new main_core.Cache.MemoryCache();
	    babelHelpers.classPrivateFieldLooseBase(this, _setServiceData)[_setServiceData](_serviceData);
	    this.initialize();
	  }
	  initialize() {}
	  adjustServiceData(serviceData) {
	    babelHelpers.classPrivateFieldLooseBase(this, _setServiceData)[_setServiceData](serviceData);
	    this.getSubtitleContainer().innerHTML = main_core.Text.encode(serviceData.isActive === true ? serviceData.activeSubtitle : serviceData.inactiveSubtitle);
	  }
	  getIconContainer(data) {
	    return this.cache.remember('baas-popup-icon', () => {
	      var _ref, _Set$iconClass;
	      const iconParams = {
	        ...this.serviceData.icon
	      };
	      const iconClass = iconParams.className;
	      const params = {
	        icon: (_ref = (_Set$iconClass = ui_iconSet_api_core.Set[iconClass]) != null ? _Set$iconClass : ui_iconSet_api_core.Set.QR_CODE_1) != null ? _ref : null
	      };
	      if (params) {
	        if (iconParams.color) {
	          params.color = iconParams.color;
	        }
	        return new ui_iconSet_api_core.Icon(params).render();
	      }
	      return main_core.Dom.addClass(main_core.Tag.render(_t$5 || (_t$5 = _$5`<div class="ui-popupconstructor-content-item__icon ui-icon-set"></div>`)), iconClass);
	    });
	  }
	  getWidgetHeader(data) {
	    return {
	      title: this.serviceData.title,
	      subtitle: this.getSubtitleContainer()
	    };
	  }
	  getSubtitleContainer() {
	    return this.cache.remember('baas-popup-subtitle', () => {
	      return main_core.Tag.render(_t2$4 || (_t2$4 = _$5`<span>${0}</span>`), main_core.Text.encode(this.serviceData.isActive ? this.serviceData.activeSubtitle : this.serviceData.inactiveSubtitle));
	    });
	  }
	  getBody(data) {
	    const adsInfo = data.adsInfo;
	    return adsInfo ? {
	      title: adsInfo.title,
	      subtitle: adsInfo.subtitle,
	      subtitleDescription: adsInfo.subtitleDescription,
	      roundContent: {
	        posterUrl: adsInfo.iconUrl,
	        videos: [{
	          url: adsInfo.videoUrl,
	          type: adsInfo.videoFileType
	        }]
	      },
	      moreLabel: main_core.Loc.getMessage('BAAS_WIDGET_MORE_LINK_TITLE'),
	      code: adsInfo.featurePromotionCode || this.serviceData.featurePromotionCode
	    } : {
	      title: this.serviceData.description,
	      roundContent: this.getIconContainer(),
	      moreLabel: main_core.Loc.getMessage('BAAS_WIDGET_MORE_LINK_TITLE'),
	      code: this.serviceData.featurePromotionCode
	    };
	  }
	  getEmergencyButton(data) {
	    if (main_core.Type.isArray(data == null ? void 0 : data.packages)) {
	      return null;
	    }
	    return new ui_buttons.Button({
	      text: main_core.Loc.getMessage('BAAS_WIDGET_RELOAD_PACKAGES'),
	      color: ui_buttons.ButtonColor.LIGHT_BORDER,
	      size: ui_buttons.ButtonSize.SMALL,
	      onclick: () => {
	        this.reload();
	      },
	      round: true,
	      noCaps: true
	    });
	  }
	  adjustPopupOnShow(popup) {
	    main_core.Dom.addClass(popup.getPopup().getContentContainer(), '--baas-widget --baas-specified');
	  }
	  getPopup() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getPopupWithHeader)[_getPopupWithHeader]().getPopup();
	  }
	  show(node) {
	    const popup = babelHelpers.classPrivateFieldLooseBase(this, _getPopupWithHeader)[_getPopupWithHeader]().getPopup();
	    if (node && node !== popup.bindElement) {
	      popup.setBindElement(node);
	    }
	    popup.show();
	  }
	  toggle(node) {
	    const popup = this.getPopup();
	    if (popup.isShown()) {
	      this.hide();
	    } else {
	      this.show(node);
	    }
	  }
	  hide() {
	    const popup = this.getPopup();
	    popup.close();
	  }
	  reload() {
	    const popup = this.getPopup();
	    popup.close();
	    this.cache.delete('widgetData');
	    this.cache.delete('baas-popup');
	    this.cache.delete('baas-popup-icon');
	    this.cache.delete('baas-popup-subtitle');
	    this.show(popup.bindElement);
	  }
	}
	function _setServiceData2(serviceData) {
	  this.serviceData = {
	    ...serviceData
	  };
	}
	function _getAnalytic2() {
	  return this.cache.remember('analyticObject', () => {
	    return Analytics.createByServiceCode(this.serviceCode);
	  });
	}
	function _getLastBoundElement2() {
	  return this.cache.has('boundLastElement') ? this.cache.get('boundLastElement') : null;
	}
	function _getWidgetData2() {
	  return new Promise((resolve, reject) => {
	    if (this.cache.has('widgetData')) {
	      resolve({
	        data: this.cache.get('widgetData')
	      });
	    } else if (main_core.Type.isFunction(this.dataProviderPromiseCreator)) {
	      this.dataProviderPromiseCreator().then(response => {
	        response.data = babelHelpers.classPrivateFieldLooseBase(this, _convertIntoWidgetDataType)[_convertIntoWidgetDataType](response.data);
	        resolve(response);
	      }).catch(reject);
	    } else {
	      // eslint-disable-next-line unicorn/prefer-type-error
	      throw new Error('Baas popup presenter: data provider is not defined');
	    }
	  });
	}
	function _getPopupWithHeader2() {
	  return this.cache.remember('baas-popup', () => {
	    const popup = new ui_popupWithHeader.PopupWithHeader({
	      target: babelHelpers.classPrivateFieldLooseBase(this, _getLastBoundElement)[_getLastBoundElement](),
	      id: `baas-components-maker-${this.constructor.justCounter++}`,
	      width: 344,
	      content: [],
	      asyncData: babelHelpers.classPrivateFieldLooseBase(this, _getWidgetData)[_getWidgetData](),
	      template: new PackageTemplateManager(),
	      analyticsCallback: (event, additionalParameter) => {
	        babelHelpers.classPrivateFieldLooseBase(this, _getAnalytic)[_getAnalytic]().onPlayerEvents(event, additionalParameter);
	      },
	      popupOptions: {
	        autoHide: true,
	        closeByEsc: true,
	        events: {
	          onShow: event => {
	            var _targetPopup$bindElem;
	            const targetPopup = event.getTarget();
	            const targetPos = main_core.Dom.getPosition(targetPopup.bindElement);
	            const popupWidth = targetPopup.getPopupContainer().offsetWidth;
	            const offsetLeft = targetPos.width / 2 - popupWidth / 2;
	            const angleShift = main_popup.Popup.getOption('angleLeftOffset') - main_popup.Popup.getOption('angleMinTop');
	            targetPopup.setAngle({
	              offset: popupWidth / 2 - angleShift
	            });
	            targetPopup.setOffset({
	              offsetLeft: offsetLeft + main_popup.Popup.getOption('angleLeftOffset')
	            });
	            babelHelpers.classPrivateFieldLooseBase(this, _getAnalytic)[_getAnalytic]().activate().onShowFrom((_targetPopup$bindElem = targetPopup.bindElement.dataset.bxAnalyticContextLabel) != null ? _targetPopup$bindElem : Analytics.CONTEXT_IS_NOT_SET);
	          },
	          onClose: () => {
	            babelHelpers.classPrivateFieldLooseBase(this, _getAnalytic)[_getAnalytic]().deactivate();
	          }
	        }
	      }
	    });
	    this.adjustPopupOnShow(popup);
	    return popup;
	  });
	}
	function _convertIntoWidgetDataType2(data) {
	  return {
	    header: {
	      icon: this.getIconContainer(data),
	      top: this.getWidgetHeader(data),
	      info: this.getBody(data),
	      button: this.getEmergencyButton(data)
	    },
	    items: data.packages
	  };
	}
	PresenterPopup.justCounter = 0;

	var _events = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("events");
	class WidgetIcon extends ui_iconSet_api_core.Icon {
	  constructor(params = {}) {
	    super(params);
	    Object.defineProperty(this, _events, {
	      writable: true,
	      value: {}
	    });
	    if (params.events) {
	      babelHelpers.classPrivateFieldLooseBase(this, _events)[_events] = params.events;
	    }
	  }
	  render() {
	    this.iconElement = super.render();
	    main_core.Dom.style(this.iconElement, 'opacity', '60%');
	    Object.keys(babelHelpers.classPrivateFieldLooseBase(this, _events)[_events]).forEach(eventName => {
	      main_core.Event.bind(this.iconElement, eventName, babelHelpers.classPrivateFieldLooseBase(this, _events)[_events][eventName]);
	    });
	    return this.iconElement;
	  }
	}

	var _onPurchaseShown = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onPurchaseShown");
	var _onPackageHidden = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onPackageHidden");
	class PresenterPopupMain extends PresenterPopup {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _onPackageHidden, {
	      value: _onPackageHidden2
	    });
	    Object.defineProperty(this, _onPurchaseShown, {
	      value: _onPurchaseShown2
	    });
	  }
	  initialize() {
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Baas:onPurchaseShown', event => babelHelpers.classPrivateFieldLooseBase(this, _onPurchaseShown)[_onPurchaseShown](event));
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Baas:onPurchaseHidden', event => babelHelpers.classPrivateFieldLooseBase(this, _onPackageHidden)[_onPackageHidden](event));
	  }
	  getIconContainer(data) {
	    return this.cache.remember('baas-popup-icon', () => {
	      return new WidgetIcon({
	        icon: ui_iconSet_api_core.Actions.CHEVRON_LEFT,
	        size: 22,
	        color: '#fff',
	        events: {
	          click: () => {
	            main_core_events.EventEmitter.emit(this, 'BX.Baas:onClickBack');
	            this.hide();
	          }
	        }
	      }).render();
	    });
	  }
	  getWidgetHeader(data) {
	    return {
	      title: main_core.Loc.getMessage('BAAS_WIDGET_TITLE'),
	      subtitle: this.getSubtitleContainer()
	    };
	  }
	  getSubtitleContainer() {
	    return '';
	  }
	  getBody(data) {
	    const adsInfo = data.adsInfo;
	    const showMore = main_core.Type.isStringFilled(adsInfo == null ? void 0 : adsInfo.featurePromotionCode);
	    return adsInfo && adsInfo.title ? {
	      title: adsInfo.title,
	      subtitle: adsInfo.subtitle,
	      subtitleDescription: adsInfo.subtitleDescription,
	      roundContent: {
	        posterUrl: adsInfo.iconUrl,
	        videos: [{
	          url: adsInfo.videoUrl,
	          type: adsInfo.videoFileType
	        }]
	      },
	      moreLabel: showMore ? main_core.Loc.getMessage('BAAS_WIDGET_MORE_LINK_TITLE') : '',
	      code: showMore ? adsInfo.featurePromotionCode : null
	    } : {
	      title: main_core.Loc.getMessage('BAAS_WIDGET_DESCRIPTION'),
	      roundContent: new ui_iconSet_api_core.Icon({
	        icon: ui_iconSet_api_core.Set.QR_CODE_1
	      }).render(),
	      moreLabel: '',
	      code: null
	    };
	  }
	  adjustPopupOnShow(popup) {
	    main_core.Dom.addClass(popup.getPopup().contentContainer, '--baas-widget --baas-common');
	  }
	}
	function _onPurchaseShown2(event) {
	  var _this$getPopup;
	  const packageItem = event.getData().package;
	  if (this.cache.has('baas-popup') && (_this$getPopup = this.getPopup()) != null && _this$getPopup.getContentContainer().contains(packageItem.getContainer())) {
	    this.getPopup().setAutoHide(false);
	    this.getPopup().setClosingByEsc(false);
	  }
	}
	function _onPackageHidden2(event) {
	  const packageItem = event.getData().package;
	  if (this.cache.has('baas-popup') && this.getPopup().getContentContainer().contains(packageItem.getContainer())) {
	    this.getPopup().setAutoHide(true);
	    this.getPopup().setClosingByEsc(true);
	  }
	}

	class PresenterSlider extends PresenterDefault {
	  constructor(serviceCode, serviceData, dataProviderPromiseCreator = null) {
	    super(serviceCode, serviceData, dataProviderPromiseCreator);
	  }
	  hide() {
	    ui_infoHelper.FeaturePromotersRegistry.getPromoter({
	      code: this.serviceData.featurePromotionCode
	    }).hide();
	  }
	  show(node) {
	    ui_infoHelper.FeaturePromotersRegistry.getPromoter({
	      code: this.serviceData.featurePromotionCode
	    }).show();
	  }
	}

	class PresenterFactory {
	  static createForWidget(serviceCode, serviceData, dataProviderPromiseCreator = null) {
	    if (serviceCode === 'main') {
	      return new PresenterPopupMain(serviceCode, serviceData, dataProviderPromiseCreator);
	    }
	    if ((serviceData == null ? void 0 : serviceData.advertisingStrategy) === 'market') {
	      return new PresenterSlider(serviceCode, serviceData, dataProviderPromiseCreator);
	    }
	    return new PresenterPopup(serviceCode, serviceData, dataProviderPromiseCreator);
	  }
	}

	var _instance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("instance");
	class Widget extends main_core_events.EventEmitter {
	  constructor(serviceCode, serviceData) {
	    super();
	    this.cache = new main_core.Cache.MemoryCache();
	    this.setEventNamespace('BX.Baas');
	    this.serviceCode = serviceCode;
	    this.serviceData = serviceData;
	    this.presenter = PresenterFactory.createForWidget(this.serviceCode, this.serviceData, this.getAjaxPromise.bind(this));
	    main_core_events.EventEmitter.subscribe(this.presenter, 'BX.Baas:onClickBack', () => this.emit('onClickBack'));
	    this.cache.set('boundElements', new WeakMap());
	  }

	  // deprecated
	  getPopup() {
	    return this.presenter.getPopup();
	  }
	  getAjaxPromise() {
	    return main_core.ajax.runAction('baas.Service.getAll');
	  }
	  bind(node, bxAnalyticContextLabel) {
	    this.cache.set('boundLastElement', node);
	    if (!this.cache.get('boundElements').has(node)) {
	      this.cache.get('boundElements').set(node);
	      main_core.Event.bind(node, 'click', () => {
	        this.show(node);
	      });
	    }
	    this.bindAnalyticContext(node, bxAnalyticContextLabel || Analytics.CONTEXT_IS_NOT_SET);
	    return this;
	  }
	  bindAnalyticContext(node, bxAnalyticContextLabel) {
	    // eslint-disable-next-line no-param-reassign
	    node.dataset.bxAnalyticContextLabel = bxAnalyticContextLabel;
	    return this;
	  }
	  show(node) {
	    const targetElement = node != null ? node : this.cache.get('boundLastElement');
	    this.presenter.show(targetElement);
	    return this;
	  }
	  toggle(node) {
	    this.presenter.toggle(node);
	    return this;
	  }
	  hide() {
	    this.presenter.hide();
	    return this;
	  }
	  reload() {
	    if (main_core.Type.isFunction(this.presenter.reload)) {
	      this.presenter.reload();
	      this.presenter.show();
	    }
	  }
	  static getInstance() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance] === null) {
	      babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance] = new this('main', null);
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _instance)[_instance];
	  }
	}
	Object.defineProperty(Widget, _instance, {
	  writable: true,
	  value: null
	});

	var _instances = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("instances");
	class ServiceWidget extends Widget {
	  constructor(serviceCode, serviceData) {
	    super(serviceCode, serviceData);
	    const settings = main_core.Extension.getSettings('baas.store');
	    if (BX.PULL && settings.pull) {
	      BX.PULL.extendWatch(settings.pull.channelName);
	      main_core_events.EventEmitter.subscribe('onPullEvent-baas', event => {
	        const [command, params] = event.getData();
	        if (command === 'updateService' && params.service.code === this.serviceCode) {
	          this.setServiceData(params.service);
	        }
	      });
	    }
	  }
	  setServiceData(serviceData) {
	    if (serviceData) {
	      this.serviceData = {
	        ...serviceData
	      };
	      this.presenter.adjustServiceData(this.serviceData);
	    }
	    return this;
	  }
	  getAjaxPromise() {
	    return main_core.ajax.runAction('baas.Service.get', {
	      data: {
	        code: this.serviceCode
	      }
	    });
	  }
	  bindAnalyticContext(node, bxAnalyticContextLabel) {
	    let contextLabel = bxAnalyticContextLabel;
	    if (Analytics.CONTEXT_IS_NOT_SET === bxAnalyticContextLabel) {
	      contextLabel = Analytics.guessContextByServiceCode(this.serviceCode);
	    }
	    super.bindAnalyticContext(node, contextLabel);
	    return this;
	  }
	  static getInstanceByCode(code) {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _instances)[_instances][code]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _instances)[_instances][code] = new this(code, main_core.Extension.getSettings('baas.store').services[code]);
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _instances)[_instances][code];
	  }
	}
	Object.defineProperty(ServiceWidget, _instances, {
	  writable: true,
	  value: []
	});

	exports.Widget = Widget;
	exports.ServiceWidget = ServiceWidget;
	exports.Analytics = Analytics;

}((this.BX.Baas.Store = this.BX.Baas.Store || {}),BX,BX.UI,BX.Main,BX.UI,BX.Bitrix24,BX,BX.UI.Analytics,BX.UI,BX.Main,BX.UI.IconSet,BX.UI,BX.UI,BX,BX.Event));
//# sourceMappingURL=list.bundle.js.map
