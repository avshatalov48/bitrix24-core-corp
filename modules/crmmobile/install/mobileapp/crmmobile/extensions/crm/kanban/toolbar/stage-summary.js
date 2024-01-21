/**
 * @module crm/kanban/toolbar/stage-summary
 */
jn.define('crm/kanban/toolbar/stage-summary', (require, exports, module) => {
	const { connect } = require('statemanager/redux/connect');
	const {
		selectById,
		selectStatus,
	} = require('crm/statemanager/redux/slices/stage-counters');
	const {
		getCrmKanbanUniqId,
	} = require('crm/statemanager/redux/slices/kanban-settings');

	const { Loc } = require('loc');
	const { StageSummary } = require('layout/ui/kanban/toolbar');
	const { PureComponent } = require('layout/pure-component');
	const MAX_FORMATTED_SUM = 1_000_000_000;

	class StageSummaryWrapper extends PureComponent
	{
		render()
		{
			const { status } = this.props;
			const money = this.getTotalMoney();
			const currencyText = money && money.formattedCurrency ? `, ${money.formattedCurrency}` : '';
			const title = Loc.getMessage('M_UI_KANBAN_TOOLBAR_DEAL_SUM') + currencyText;
			const text = this.getFormattedMoneyText(money);

			return StageSummary({
				title,
				text,
				useFiller: status !== 'success',
			});
		}

		/**
		 * @return {Money|null}
		 */
		getTotalMoney()
		{
			const { counter } = this.props;
			if (!counter)
			{
				return null;
			}

			const amount = counter.total;
			const currency = counter.currency;

			return new Money({ amount, currency });
		}

		/**
		 * @param {?Money} money
		 * @returns {null|String}
		 */
		getFormattedMoneyText(money)
		{
			if (!money)
			{
				return null;
			}

			const formatBigMoney = (amount) => {
				const compact = Math.floor(amount * 10 / MAX_FORMATTED_SUM) / 10;

				return `${compact} ${Loc.getMessage('M_CRM_MAX_FORMATTED_SUM')}`;
			};

			return money.amount >= MAX_FORMATTED_SUM ? formatBigMoney(money.amount) : money.formattedAmount;
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const counterId = ownProps.activeStageId || getCrmKanbanUniqId(ownProps.entityTypeId, ownProps.categoryId);

		return {
			counter: selectById(state, counterId),
			status: selectStatus(state),
		};
	};

	module.exports = {
		StageSummary: connect(mapStateToProps)(StageSummaryWrapper),
	};
});
