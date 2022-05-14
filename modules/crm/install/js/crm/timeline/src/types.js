/** @memberof BX.Crm.Timeline.Types */
export const Item = {
	undefined: 0,
	activity: 1,
	creation: 2,
	modification: 3,
	link: 4,
	unlink: 5,
	mark: 6,
	comment: 7,
	wait: 8,
	bizproc: 9,
	conversion: 10,
	sender: 11,
	document: 12,
	restoration: 13,
	order: 14,
	orderCheck: 15,
	scoring: 16,
	externalNotification: 17,
	finalSummary: 18,
	delivery: 19,
	finalSummaryDocuments: 20,
	storeDocument: 21,
};

/** @memberof BX.Crm.Timeline.Types */
export const Mark = {
	undefined: 0,
	waiting: 1,
	success: 2,
	renew: 3,
	ignored: 4,
	failed: 5
};

/** @memberof BX.Crm.Timeline.Types */
export const Delivery = {
	undefined: 0,
	taxiEstimationRequest: 1,
	taxiCallRequest: 2,
	taxiCancelledByManager: 3,
	taxiCancelledByDriver: 4,
	taxiPerformerNotFound: 5,
	taxiSmsProviderIssue: 6,
	taxiReturnedFinish: 7,
	deliveryMessage: 101,
	deliveryCalculation: 102,
};

/** @memberof BX.Crm.Timeline.Types */
export const Order = {
	encourageBuyProducts: 100,
}

/** @memberof BX.Crm.Timeline.Types */
export const EditorMode = {
	view: 1,
	edit: 2
}