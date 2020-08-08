/**
 * Bitrix Im mobile
 * Application Launcher
 *
 * @package bitrix
 * @subpackage mobile
 * @copyright 2001-2020 Bitrix
 */

export const ApplicationLauncher = function (name, params = {})
{
	name = name.toString();
	name = name.substr(0, 1).toUpperCase()+name.substr(1);

	if (name === 'Launch' || name === 'Core' || name.endsWith('Application'))
	{
		console.error('BX.Messenger.Application.Launch: specified name is forbidden.');
		return new Promise((resolve, reject) => reject());
	}

	let launch = function() {
		BX.Messenger.Application[name] = new BX.Messenger.Application[name+'Application'](params);
		return BX.Messenger.Application[name].ready();
	};

	if (typeof BX.Messenger.Application[name+'Application'] === 'undefined')
	{
		console.error('BX.Messenger.Application.Launch: application is not found.');
		return new Promise((resolve, reject) => reject());
	}

	return launch();
};