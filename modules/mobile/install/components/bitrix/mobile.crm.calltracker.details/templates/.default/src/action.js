export default class Action
{
	static addToIgnored(id)
	{
		BXMobileApp.Events.postToComponent('onCrmCallTrackerAddToIgnoredRequest', {ID: id});
		BXMobileApp.UI.Page.close();
	}

	static postpone(id)
	{
		BXMobileApp.Events.postToComponent('onCrmCallTrackerPostponeRequest', {ID: id});
		BXMobileApp.UI.Page.close();
	}
}