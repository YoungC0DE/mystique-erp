import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import BasePagination from '@/components/ui/BasePagination.vue';

describe('BasePagination', () => {
  it('renders nothing when there is a single page and no total', () => {
    const wrapper = mount(BasePagination, {
      props: { currentPage: 1, lastPage: 1 },
    });
    expect(wrapper.find('[data-testid="pagination"]').exists()).toBe(false);
  });

  it('emits change with the next page', async () => {
    const wrapper = mount(BasePagination, {
      props: { currentPage: 1, lastPage: 3 },
    });
    const next = wrapper.findAll('button')[1];
    await next.trigger('click');

    expect(wrapper.emitted('change')).toEqual([[2]]);
  });

  it('does not emit when already on the last page', async () => {
    const wrapper = mount(BasePagination, {
      props: { currentPage: 3, lastPage: 3 },
    });
    const next = wrapper.findAll('button')[1];
    await next.trigger('click');

    expect(wrapper.emitted('change')).toBeUndefined();
  });

  it('disables the previous button on the first page', () => {
    const wrapper = mount(BasePagination, {
      props: { currentPage: 1, lastPage: 3 },
    });
    const prev = wrapper.findAll('button')[0];
    expect(prev.attributes('disabled')).toBeDefined();
  });

  it('shows the total when provided', () => {
    const wrapper = mount(BasePagination, {
      props: { currentPage: 1, lastPage: 1, total: 42 },
    });
    expect(wrapper.text()).toContain('42 registro(s)');
  });
});
