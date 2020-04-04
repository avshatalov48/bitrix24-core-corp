/**
 * Bitrix OpenLines widget
 * Widget constants
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

function GetObjectValues(source)
{
	const destination = [];
	for (let value in source)
	{
		if (source.hasOwnProperty(value))
		{
			destination.push(source[value]);
		}
	}
	return destination;
}

/* region 01. Constants */

export const VoteType = Object.freeze({
	none: 'none',
	like: 'like',
	dislike: 'dislike',
});

export const LanguageType = Object.freeze({
	russian: 'ru',
	ukraine: 'ua',
	world: 'en',
});

export const FormType = Object.freeze({
	none: 'none',
	like: 'like',
	smile: 'smile',
	consent: 'consent',
	welcome: 'welcome',
	offline: 'offline',
	history: 'history',
});

export const LocationType = Object.freeze({
	topLeft: 1,
	topMiddle: 2,
	topBottom: 3,
	bottomLeft: 6,
	bottomMiddle: 5,
	bottomRight: 4,
});

export const LocationStyle = Object.freeze({
	1: 'top-left',
	2: 'top-center',
	3: 'top-right',
	6: 'bottom-left',
	5: 'bottom-center',
	4: 'bottom-right',
});

export const SubscriptionType = Object.freeze({
	configLoaded: 'configLoaded',
	widgetOpen: 'widgetOpen',
	widgetClose: 'widgetClose',
	sessionStart: 'sessionStart',
	sessionOperatorChange: 'sessionOperatorChange',
	sessionFinish: 'sessionFinish',
	operatorMessage: 'operatorMessage',
	userForm: 'userForm',
	userMessage: 'userMessage',
	userFile: 'userFile',
	userVote: 'userVote',
	every: 'every',
});
export const SubscriptionTypeCheck = GetObjectValues(SubscriptionType);

export const RestMethod = Object.freeze({
	widgetUserRegister: 'imopenlines.widget.user.register',
	widgetConfigGet: 'imopenlines.widget.config.get',
	widgetDialogGet: 'imopenlines.widget.dialog.get',
	widgetUserGet: 'imopenlines.widget.user.get',
	widgetUserConsentApply: 'imopenlines.widget.user.consent.apply',
	widgetVoteSend: 'imopenlines.widget.vote.send',
	widgetFormSend: 'imopenlines.widget.form.send',

	pullServerTime: 'server.time',
	pullConfigGet: 'pull.config.get',
});
export const RestMethodCheck = GetObjectValues(RestMethod);

export const RestAuth = Object.freeze({
	guest: 'guest',
});

export const SessionStatus = Object.freeze({
	new: 0,
	skip: 5,
	answer: 10,
	client: 20,
	clientAfterOperator: 25,
	operator: 40,
	waitClient: 50,
	close: 60,
	spam: 65,
	duplicate: 69,
	silentlyClose: 75,
});