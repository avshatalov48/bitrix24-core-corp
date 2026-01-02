/**
 * @module more-menu/block/company/whats-new
 */
jn.define('more-menu/block/company/whats-new', (require, exports, module) => {
	const { inAppUrl } = require('in-app-url');
	const { Loc } = require('loc');
	const { PureComponent } = require('layout/pure-component');
	const { connect } = require('statemanager/redux/connect');
	const { selectNewCount } = require('statemanager/redux/slices/whats-new');
	const { Indent } = require('tokens');
	const { MoreMenuAnalytics } = require('more-menu/analytics');
	const { ProfileButton } = require('more-menu/ui/profile-button');
	const { PropTypes } = require('utils/validation');

	/**
	 * @class WhatsNewButton
	 */
	class WhatsNewButton extends PureComponent
	{
		render()
		{
			return ProfileButton({
				testId: this.props.testId,
				text: Loc.getMessage('MORE_MENU_COMPANY_WHATS_NEW'),
				onClick: this.openWhatsNew,
				style: {
					marginRight: Indent.M.toNumber(),
				},
				badge: this.props.counter,
			});
		}

		openWhatsNew = () => {
			inAppUrl.open('/whats-new/', {
				title: Loc.getMessage('MORE_MENU_COMPANY_WHATS_NEW'),
			});

			MoreMenuAnalytics.sendDrawerOpenEvent();
		};
	}

	WhatsNewButton.propTypes = {
		testId: PropTypes.string,
		counter: PropTypes.number,
	};

	const mapStateToProps = (state) => {
		const counter = selectNewCount(state);

		return {
			counter,
		};
	};

	module.exports = {
		WhatsNewButton: connect(mapStateToProps)(WhatsNewButton),
	};
});
