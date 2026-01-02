/**
 * @module layout/ui/app-rating/src/strategies/strategy-factory
 */
jn.define('layout/ui/app-rating/src/strategies/strategy-factory', (require, exports, module) => {
	const { StoreRedirectStrategy } = require('layout/ui/app-rating/src/strategies/store-redirect-strategy');
	const { SupportChatStrategy } = require('layout/ui/app-rating/src/strategies/support-chat-strategy');
	const { FeedbackFormStrategy } = require('layout/ui/app-rating/src/strategies/feedback-form-strategy');

	class StrategyFactory
	{
		constructor(strategies)
		{
			this.strategies = strategies;
		}

		getApplicableStrategy(props)
		{
			return this.strategies.find((strategy) => strategy.isApplicable(props));
		}
	}

	const defaultStrategies = [
		new StoreRedirectStrategy(),
		new SupportChatStrategy(),
		new FeedbackFormStrategy(),
	];

	const defaultStrategyFactory = new StrategyFactory(defaultStrategies);

	module.exports = {
		StrategyFactory,
		defaultStrategyFactory,
	};
});
