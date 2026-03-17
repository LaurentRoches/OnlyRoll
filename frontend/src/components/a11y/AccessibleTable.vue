<template>
  <div
    class="accessible-table-container"
    role="region"
    :aria-label="caption || t('common.accessibleTable.regionLabel')"
  >
    <table class="w-full" :aria-describedby="descriptionId">
      <caption v-if="caption" class="sr-only">
        {{
          caption
        }}
      </caption>

      <thead class="bg-secondary-700">
        <tr>
          <th
            v-for="column in columns"
            :key="column.key"
            scope="col"
            class="px-4 py-3 text-left text-xs font-medium text-secondary-300 uppercase tracking-wider"
            :class="[
              column.align === 'center' && 'text-center',
              column.align === 'right' && 'text-right',
              sortable &&
                column.sortable !== false &&
                'cursor-pointer hover:bg-secondary-600 select-none',
            ]"
            :aria-sort="getSortDirection(column.key)"
            @click="sortable && column.sortable !== false && handleSort(column.key)"
            @keydown.enter="sortable && column.sortable !== false && handleSort(column.key)"
            @keydown.space.prevent="sortable && column.sortable !== false && handleSort(column.key)"
            :tabindex="sortable && column.sortable !== false ? 0 : undefined"
          >
            <span class="flex items-center" :class="column.align === 'right' && 'justify-end'">
              {{ column.label }}
              <span
                v-if="sortable && column.sortable !== false"
                class="ml-2 flex-shrink-0"
                aria-hidden="true"
              >
                <svg
                  v-if="sortColumn === column.key && sortDirection === 'ASC'"
                  class="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 15l7-7 7 7"
                  />
                </svg>
                <svg
                  v-else-if="sortColumn === column.key && sortDirection === 'DESC'"
                  class="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                  />
                </svg>
                <svg
                  v-else
                  class="w-4 h-4 opacity-40"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"
                  />
                </svg>
              </span>
              <span v-if="sortColumn === column.key" class="sr-only">
                {{
                  sortDirection === 'ASC'
                    ? t('common.accessibleTable.sortedAsc')
                    : t('common.accessibleTable.sortedDesc')
                }}
              </span>
            </span>
          </th>
        </tr>
      </thead>

      <tbody class="divide-y divide-secondary-700">
        <tr v-if="data.length === 0">
          <td :colspan="columns.length" class="px-4 py-8 text-center text-secondary-400">
            <slot name="empty">
              {{ emptyMessage || t('common.accessibleTable.emptyMessage') }}
            </slot>
          </td>
        </tr>

        <tr
          v-for="(row, rowIndex) in data"
          :key="getRowKey(row, rowIndex)"
          class="hover:bg-secondary-700/50 transition-colors"
          :class="{ 'bg-secondary-800/50': rowIndex % 2 === 0 }"
        >
          <td
            v-for="column in columns"
            :key="column.key"
            class="px-4 py-3 text-sm"
            :class="[
              column.align === 'center' && 'text-center',
              column.align === 'right' && 'text-right',
              column.class || 'text-secondary-200',
            ]"
          >
            <slot :name="`cell-${column.key}`" :row="row" :value="getCellValue(row, column.key)">
              {{ formatCellValue(getCellValue(row, column.key), column) }}
            </slot>
          </td>
        </tr>
      </tbody>
    </table>

    <p :id="descriptionId" class="sr-only">
      {{ t('common.accessibleTable.description', { count: data.length }, data.length) }}
      <template v-if="sortable">
        {{ t('common.accessibleTable.sortHint') }}
      </template>
    </p>

    <div role="status" aria-live="polite" aria-atomic="true" class="sr-only">
      {{ announcement }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, useId } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

export interface TableColumn {
  key: string
  label: string
  sortable?: boolean
  align?: 'left' | 'center' | 'right'
  class?: string
  format?: (value: unknown) => string
}

interface Props {
  columns: TableColumn[]
  data: Record<string, unknown>[]
  caption?: string
  emptyMessage?: string
  sortable?: boolean
  initialSortColumn?: string
  initialSortDirection?: 'ASC' | 'DESC'
  rowKey?: string | ((row: Record<string, unknown>) => string | number)
}

const props = withDefaults(defineProps<Props>(), {
  caption: '',
  emptyMessage: '',
  sortable: false,
  initialSortDirection: 'ASC',
})

const emit = defineEmits<{
  sort: [column: string, direction: 'ASC' | 'DESC']
}>()

const descriptionId = useId()
const announcement = ref('')
const sortColumn = ref(props.initialSortColumn || '')
const sortDirection = ref<'ASC' | 'DESC'>(props.initialSortDirection)

const getRowKey = (row: Record<string, unknown>, index: number): string | number => {
  if (typeof props.rowKey === 'function') {
    return props.rowKey(row)
  }
  if (props.rowKey && row[props.rowKey] !== undefined) {
    return String(row[props.rowKey])
  }
  return index
}

const getCellValue = (row: Record<string, unknown>, key: string): unknown => {
  const keys = key.split('.')
  let value: unknown = row
  for (const k of keys) {
    if (value && typeof value === 'object' && k in value) {
      value = (value as Record<string, unknown>)[k]
    } else {
      return undefined
    }
  }
  return value
}

const formatCellValue = (value: unknown, column: TableColumn): string => {
  if (value === undefined || value === null) {
    return '-'
  }
  if (column.format) {
    return column.format(value)
  }
  return String(value)
}

const getSortDirection = (columnKey: string): 'ascending' | 'descending' | 'none' | undefined => {
  if (!props.sortable) return undefined
  if (sortColumn.value !== columnKey) return 'none'
  return sortDirection.value === 'ASC' ? 'ascending' : 'descending'
}

const handleSort = (columnKey: string) => {
  if (sortColumn.value === columnKey) {
    sortDirection.value = sortDirection.value === 'ASC' ? 'DESC' : 'ASC'
  } else {
    sortColumn.value = columnKey
    sortDirection.value = 'ASC'
  }

  const column = props.columns.find((c) => c.key === columnKey)
  const directionText =
    sortDirection.value === 'ASC'
      ? t('common.accessibleTable.ascending')
      : t('common.accessibleTable.descending')
  announcement.value = t('common.accessibleTable.sortAnnouncement', {
    column: column?.label || columnKey,
    direction: directionText,
  })

  setTimeout(() => {
    announcement.value = ''
  }, 1000)

  emit('sort', columnKey, sortDirection.value)
}
</script>

<style scoped lang="postcss">
.accessible-table-container {
  @apply bg-secondary-800 rounded-lg border border-secondary-700 overflow-hidden;
}

/* Focus visible sur les en-têtes triables */
th[tabindex='0']:focus-visible {
  @apply outline-2 outline-primary-500 outline-offset-[-2px];
}
</style>
