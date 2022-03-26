(() => {

	this.CatalogStoreEvents = {

		Document: {
			Change: 'StoreEvents.Document.Change',
			TabChange: 'DetailCard::onTabChange',
		},

		ProductDetails: {
			Change: 'StoreEvents.ProductDetails.Change',
		},

		ProductList: {
			TotalChanged: 'StoreEvents.ProductList.TotalChanged',
		},

		Wizard: {
			Progress: 'onCatalogProductWizardProgress',
			Finish: 'onCatalogProductWizardFinish',
		}

	}

})();