import { Type } from 'main.core';
import { Structure } from 'humanresources.company-structure.public';

export class OpenActionService
{
	static openStructureNodeId(nodeId: number): void
	{
		Structure?.open({
			focusNodeId: nodeId,
		});
	}

	static openUserProfile(url: ?string): void
	{
		if (!Type.isStringFilled(url))
		{
			return;
		}

		BX.SidePanel.Instance.open(url);
	}
}
