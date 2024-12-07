/**
 * @module call/calls-card/card-content/enum
 */
jn.define('call/calls-card/card-content/enum', (require, exports, module) => {
	const CallsCardType = {
		incoming: 'INCOMING',
		finished: 'FINISHED',
		started: 'STARTED',
		outgoing: 'OUTGOING',
		waiting: 'WAITING',
		callback: 'CALLBACK',
	};

	const TelephonyUiEvent = {
		onHangup: "onHangupCallClicked",
		onSpeakerphoneChanged: "onSpeakerphoneCallClicked",
		onMuteChanged: "onMuteCallClicked",
		onPauseChanged: "onPauseCallClicked",
		onNumpadClicked: "onNumpadClicked",
		onFormFolded: "onFoldCallClicked",
		onFormExpanded: "onUnfoldIconClicked",
		onCloseClicked: "onCloseCallClicked",
		onSkipClicked: "onSkipCallClicked",
		onAnswerClicked: "onAnswerCallClicked",
		onNumpadClosed: "onNumpadClosed",
		onNumpadOpen: "onNumpadOpen",
		onNumpadButtonClicked: "onNumpadButtonClicked",
		onPhoneNumberReceived: "onPhoneNumberReceived",
		onContactListChoose: "onContactListChoose",
		onContactListMenuChoose: "onContactListMenuChoose",
	};

	module.exports = { CallsCardType,  TelephonyUiEvent };
});