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
			ListChanged: 'StoreEvents.ProductList.ListChanged',
			StartUpdateSummary: 'StoreEvents.ProductList.StartUpdateSummary',
			FinishUpdateSummary: 'StoreEvents.ProductList.FinishUpdateSummary',
			TotalChanged: 'StoreEvents.ProductList.TotalChanged',
		},

		Wizard: {
			Progress: 'onCatalogProductWizardProgress',
			Finish: 'onCatalogProductWizardFinish',
		}

	}

})();