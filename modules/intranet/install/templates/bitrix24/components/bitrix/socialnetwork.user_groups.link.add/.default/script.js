(function() {

"use strict";

BX.namespace("BX.Bitrix24");

BX.Bitrix24.SonetGroupFilter = function()
{
	this.actualSearchString = '';
	this.minSearchStringLength = 2;
};

BX.Bitrix24.SonetGroupFilter.prototype.init = function (params) {

	var filterId = (
		typeof params != 'undefined'
		&& typeof params.filterId != 'undefined'
			? params.filterId
			: 'SONET_GROUP_LIST'
	);

	if (
		typeof params != 'undefined'
		&& typeof params.minSearchStringLength != 'undefined'
		&& parseInt(params.minSearchStringLength) > 0
	)
	{
		this.minSearchStringLength = parseInt(params.minSearchStringLength);
	}

	BX.addCustomEvent("BX.SonetGroupList:refresh", BX.delegate(function() {
		BX.Main.filterManager.getById(filterId).getPreset().resetPreset(true);
		BX.Main.filterManager.getById(filterId).getSearch().clearForm();
	}, this));

	BX.addCustomEvent("BX.Main.Filter:beforeApply", BX.delegate(function(eventFilterId, values, ob, filterPromise) {
		if (
			eventFilterId != filterId
			|| (
				this.actualSearchString.length > 0
				&& this.actualSearchString.length < this.minSearchStringLength
			)
		)
		{
			return;
		}

		BX.onCustomEvent(window, 'BX.SonetGroupList.Filter:beforeApply', [values, filterPromise]);
	}, this));

	BX.addCustomEvent("BX.Main.Filter:apply", BX.delegate(function(eventFilterId, values, ob, filterPromise, filterParams) {
		if (
			eventFilterId != filterId
			|| (
				this.actualSearchString.length > 0
				&& this.actualSearchString.length < this.minSearchStringLength
			)
		)
		{
			return;
		}

		BX.onCustomEvent(window, 'BX.SonetGroupList.Filter:apply', [values, filterPromise, filterParams]);
	}, this));

	BX.addCustomEvent('BX.Filter.Search:input', BX.delegate(function(eventFilterId, searchString) {
		if (eventFilterId == filterId)
		{

			this.actualSearchString = (typeof searchString != 'undefined' ? BX.util.trim(searchString) : '');

			if (
				this.actualSearchString.length > 0
				&& this.actualSearchString.length >= this.minSearchStringLength
			)
			{
				BX.onCustomEvent(window, 'BX.SonetGroupList.Filter:searchInput', [ searchString ]);
			}
		}
	}, this));

	BX.addCustomEvent('BX.Main.Filter:blur', BX.delegate(function(filterObject) {
		if (
			filterObject.getParam('FILTER_ID') == filterId
			&& filterObject.getSearch().getSquares().length <= 0
			&& filterObject.getSearch().getSearchString().length <= 0
		)
		{
			var pagetitleContainer = BX.findParent(BX(filterId + '_filter_container'), { className: 'pagetitle-wrap'});
			if (pagetitleContainer)
			{
				BX.removeClass(pagetitleContainer, "pagetitle-wrap-filter-opened");
			}
		}
	}, this));
};

}());