import { PhoneCallsController } from './controller';
import { PhoneCallView } from './view/view';
import { FoldedCallView } from './view/folded-view';
import './css/view.css';

export {
	PhoneCallsController,
	PhoneCallView,
}

// legacy compat
BX.FoldedCallView = FoldedCallView;