export interface BaseFieldProps {
	// General Props
	uid?: string; // Unique identifier for the field.
	testId?: string; // Test identifier for testing purposes.
	onFocusIn?: () => void; // Callback when the field gains focus.
	onFocusOut?: () => void; // Callback when the field loses focus.
	onChange?: (...values: any[]) => Promise<void> | void; // Callback when the field value changes.
	onBeforeChange?: (actionParams: any) => Promise<void> | void; // Callback before changing the field value.
	value?: any; // The current value of the field.

	// Display Props
	title?: string; // The title of the field.
	titlePosition?: 'top' | 'left'; // The position of the title (top or left).
	showLeftIcon?: boolean; // Whether to show the left icon before the title.
	showRequired?: boolean; // Whether to show the '*' symbol to indicate the field is required.
	showTitle?: boolean; // Whether to show the title.
	canFocusTitle?: boolean; // Whether the title can be focused.
	disabled?: boolean; // Whether the field is disabled.
	readOnly?: boolean; // Whether the field is read-only.
	editable?: boolean; // Whether the field is editable.
	hidden?: boolean; // Whether the field is hidden.
	required?: boolean; // Whether the field is required.
	multiple?: boolean; // Whether the field supports multiple values.
	hasHiddenEmptyView?: boolean; // Whether to have a view without a title if the field is empty.
	showBorder?: boolean; // Whether to show a border around the field.
	hasSolidBorderContainer?: boolean; // Whether the border around the field is solid.
	restrictionPolicy?: number;

	// Config Props
	config?: {
		parentWidget?: any; // Parent widget object.
		copyingOnLongClick?: boolean; // Whether copying is enabled on long-click.
		styles?: any; // Additional styles for the field.
		deepMergeStyles?: any; // Object with styles that will replace the default ones
		titleIcon?: {
			before?: {
				uri: string; // URI of the icon to show before the title.
				width: number; // Width of the icon.
				height: number; // Height of the icon.
			};
			after?: {
				uri: string; // URI of the icon to show after the title.
				width: number; // Width of the icon.
				height: number; // Height of the icon.
			};
		};
	};

	// Callbacks
	onContentClick?: () => void; // Callback when the content of the field is clicked.
	renderAdditionalContent?: () => any; // Callback to display additional content for the field on the right side.
	tooltip?: (any) => Promise<{ message: string; color: string }>; // Callback to show tooltip message for the field.

	// Other Props
	showEditIcon?: boolean; // Whether to show an edit icon for the field.
	editIcon?: any; // Custom edit icon content to render for the field.
	restrictedEdit?: boolean; // Whether editing is restricted for the field. // todo move to userField
	emptyValue?: string; // The value to display when the field is empty and read-only.
	requiredErrorMessage?: string; // Custom error message for the required field validation.
	focus?: boolean; // Whether the field should be focused.

	parent?: any; // Parent widget object.
	customValidation?: (value: any) => Promise<string | null>; // Custom validation function for the field.
	id: number;
}

export interface BaseFieldState {
	focus: boolean; // Whether the field is currently focused.
	errorMessage: string | null; // The error message for the field, if any.
	tooltipMessage: string | null; // The tooltip message for the field, if any.
	tooltipColor: string; // The color of the tooltip message for the field.
	showAll: boolean; // Whether to show all content for the field (used for fields with hidden content).
}
