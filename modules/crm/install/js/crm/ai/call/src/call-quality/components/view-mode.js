export type ViewModeType = ViewMode.usedNotAssessmentScript
	| ViewMode.usedCurrentVersionOfScript
	| ViewMode.usedOtherVersionOfScript
	| ViewMode.emptyScriptList
	| ViewMode.pending
	| ViewMode.error
;

/*
* @readonly
* @enum {string}
*/
export const ViewMode: Object<string, string> = Object.freeze({
	usedNotAssessmentScript: 'usedNotAssessmentScript',
	usedCurrentVersionOfScript: 'usedCurrentVersionOfScript',
	usedOtherVersionOfScript: 'usedOtherVersionOfScript',
	emptyScriptList: 'emptyScriptList',
	pending: 'pending',
	error: 'error',
});
