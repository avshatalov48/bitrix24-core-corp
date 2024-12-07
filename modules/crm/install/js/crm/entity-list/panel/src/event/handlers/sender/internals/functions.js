import { ajax as Ajax, Text } from 'main.core';
import { MessageBox } from 'ui.dialogs.messagebox';

export function saveEntitiesToSegment(
	segmentId: ?number,
	entityTypeId: number,
	entityIds: number[],
	gridId: ?string,
): Promise<{segment: Object}>
{
	return new Promise((resolve, reject) => {
		Ajax.runAction('crm.integration.sender.segment.upload', {
			data: {
				segmentId,
				entityTypeName: BX.CrmEntityType.resolveName(entityTypeId),
				entities: entityIds,
				gridId,
			},
		}).then(({ data }) => {
			if ('errors' in data)
			{
				MessageBox.alert(Text.encode(data.errors.join('\n')));

				reject();

				return;
			}

			resolve({ segment: data });
		}).catch(reject);
	});
}
