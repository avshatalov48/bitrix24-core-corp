export const ColumnTitle = {
	name: 'ColumnTitle',

	props: {
		mode: {
			type: String,
			required: true,
		},
	},

	template: `
		<template v-if="mode === 'direct'">
			<div class="hr-hcmlink-sync__column-title-container">
				<div>
					{{ $Bitrix.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_COLUMN_TITLE_BITRIX') }}
				</div>
				<div>
					{{ $Bitrix.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_COLUMN_TITLE_ZUP') }}
				</div>
			</div>
		</template>
		<template v-else>
			<div class="hr-hcmlink-sync__column-title-container">
				<div>
					{{ $Bitrix.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_COLUMN_TITLE_ZUP') }}
				</div>
				<div>
					{{ $Bitrix.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_COLUMN_TITLE_BITRIX') }}
				</div>
			</div>
		</template>
	`,
};