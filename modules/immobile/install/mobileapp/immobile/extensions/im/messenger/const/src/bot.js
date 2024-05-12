/**
 * @module im/messenger/const/bot
 */
jn.define('im/messenger/const/bot', (require, exports, module) => {
	const RawBotType = Object.freeze({
		bot: 'bot',
		network: 'network',
		support24: 'support24',
		human: 'human',
		openline: 'openline',
		supervisor: 'supervisor',
	});

	const BotType = Object.freeze({
		bot: 'bot',
		network: 'network',
		support24: 'support24',
	});

	const BotCode = Object.freeze({
		marta: 'marta',
		giphy: 'giphy',
		support: 'support',
		copilot: 'copilot',
	});

	const BotCommand = Object.freeze({
		activate: 'activate',
	});

	module.exports = {
		RawBotType,
		BotType,
		BotCode,
		BotCommand,
	};
});