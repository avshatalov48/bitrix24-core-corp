import { PhoneCallsController } from './controller';
import { PhoneCallView } from './view/view';
import { FoldedCallView } from './view/folded-view';
import {BackgroundWorker} from './view/background-worker';

import './css/view.css';

import 'applayout';
import 'crm_form_loader';
import 'phone_number';

export {
	PhoneCallsController,
	PhoneCallView,
	BackgroundWorker,
}

// legacy compat
BX.FoldedCallView = FoldedCallView;