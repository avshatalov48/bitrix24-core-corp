import { mount } from '@vue/test-utils';

import 'tasks.v2.test';

import { ProgressBar } from '../src/progress-bar';

xdescribe('UiProgressBar', () => {
	it('should render with default props', () => {
		const wrapper = mount(ProgressBar, {
			props: {
				totalValue: 100,
				completedValue: 50
			}
		});

		expect(wrapper.props()).toEqual({
			totalValue: 100,
			completedValue: 50,
			width: 100,
			height: 10,
			color: '#000000',
			bgColor: '#ffffff',
			borderRadius: 5
		});
	});

	it('should calculate progress percentage correctly', () => {
		const wrapper = mount(ProgressBar, {
			props: {
				totalValue: 200,
				completedValue: 50
			}
		});

		expect(wrapper.vm.progressPercentage).toBe(25);
	});

	it('should update progress when props change', async () => {
		const wrapper = mount(ProgressBar, {
			props: {
				totalValue: 100,
				completedValue: 10
			}
		});

		await wrapper.setProps({ completedValue: 50 });
		expect(wrapper.vm.progressPercentage).toBe(50);
	});
});
