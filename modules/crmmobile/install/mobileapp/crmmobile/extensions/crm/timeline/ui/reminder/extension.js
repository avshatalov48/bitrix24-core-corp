/**
 * @module crm/timeline/ui/reminder
 */
jn.define('crm/timeline/ui/reminder', (require, exports, module) => {
	const { Loc } = require('loc');
	const { getEntityMessage } = require('crm/loc');

	const nothing = () => {};

	function CreateReminder({ entityTypeId, style, onClick })
	{
		return View(
			{
				style: {
					borderRadius: 12,
					backgroundColor: '#fefcee',
					padding: 12,
					marginBottom: style.marginBottom || 0,
					flexDirection: 'row',
				},
				onClick: onClick || nothing,
			},
			View(
				{
					style: {
						flexDirection: 'row',
						flexGrow: 1,
					},
				},
				PlusIcon(),
				View(
					{
						style: {
							flex: 1,
							paddingRight: 8,
						},
					},
					Title(Loc.getMessage('CRM_TIMELINE_REMINDER_TITLE2')),
					Description(getEntityMessage('CRM_TIMELINE_REMINDER_DESCRIPTION3', entityTypeId)),
				),
			),
		);
	}

	function PlusIcon()
	{
		return View(
			{
				style: {
					padding: 8,
					marginRight: 8,
					flexDirection: 'column',
					justifyContent: 'center',
				},
			},
			Image({
				svg: {
					content: '<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0 11C0 4.92487 4.92487 0 11 0V0C17.0751 0 22 4.92487 22 11V11C22 17.0751 17.0751 22 11 22V22C4.92487 22 0 17.0751 0 11V11Z" fill="#9DCF00"/><path d="M11.8967 4.58331H10.0833V10.0833H4.58334V11.8686H10.0833V17.4072H11.8967V11.8686H17.4549V10.0833H11.8967V4.58331Z" fill="white"/></svg>',
				},
				style: {
					width: 22,
					height: 22,
				},
			}),
		);
	}

	function Title(text)
	{
		return View(
			{
				style: {
					marginBottom: 2,
				},
			},
			Text({
				text,
				style: {
					fontSize: 15,
					color: '#333333',
				},
			}),
		);
	}

	function Description(text)
	{
		return View(
			{},
			Text({
				text,
				style: {
					fontSize: 13,
					color: '#959CA4',
				},
			}),
		);
	}

	module.exports = { CreateReminder };
});
