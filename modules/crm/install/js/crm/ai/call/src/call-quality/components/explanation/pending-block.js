import { Lottie } from 'ui.lottie';
import { Loader } from '../common/loader';

export const PendingBlock = {
	components: {
		Loader,
	},

	template: `
		<div class="call-quality__explanation">
			<div class="call-quality__explanation__container">
				<div class="call-quality__explanation-title">
					{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_PENDING_TITLE') }}
				</div>
				<div class="call-quality__explanation-text">
					<div class="call-quality__explanation-loader__container">
						<Loader />
						<div class="call-quality__explanation-loader__lottie-text">
							{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_PENDING_TEXT') }}
						</div>
					</div>
				</div>
			</div>
		</div>
	`,
};
