import { FileType } from '../../../model/files/src/types';
import { DialogId } from '../../../types/common';

type sendAnalyticsParams = {
	fileType: FileType,
	dialogId: DialogId,
	status: string
}

export { sendAnalyticsParams };
