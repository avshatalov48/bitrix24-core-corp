import type { BlankSelectorConfig } from 'sign.v2.blank-selector';
import type { DocumentSendConfig } from 'sign.v2.b2b.document-send';
import type { UserPartyConfig } from 'sign.v2.b2e.user-party';
import type { DocumentInitiatedType } from 'sign.type';
import type { B2EFeatureConfig } from 'sign.v2.b2e.sign-settings';

export type SignOptionsConfig = {
	b2eFeatureConfig: B2EFeatureConfig;
	blankSelectorConfig: BlankSelectorConfig;
	documentSendConfig: DocumentSendConfig;
	userPartyConfig: UserPartyConfig;
}

export type SignOptions = {
	uid: ?string;
	type: ?string;
	config: SignOptionsConfig,
	documentMode: 'document' | 'template',
	templateUid?: string,
	initiatedByType?: DocumentInitiatedType,
};
