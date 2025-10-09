<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: [Date, null],
        default: null
    }
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const wrapperRef = ref(null);

const tempSelectedDate = ref(props.modelValue ? new Date(props.modelValue) : null);

const viewDate = ref(props.modelValue ? new Date(props.modelValue) : new Date());

const today = new Date();

const weekDays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];

watch(() => props.modelValue, (newVal) => {
    if (newVal) {
        tempSelectedDate.value = new Date(newVal);
        viewDate.value = new Date(newVal);
    } else {
        tempSelectedDate.value = null;
    }
});

watch(isOpen, (val) => {
    if (val && props.modelValue) {
        tempSelectedDate.value = new Date(props.modelValue);
        viewDate.value = new Date(props.modelValue);
    }
});

const daysGrid = computed(() => {
    const year = viewDate.value.getFullYear();
    const month = viewDate.value.getMonth();

    const firstDayOfMonth = new Date(year, month, 1);
    let startDay = firstDayOfMonth.getDay();
    if (startDay === 0) startDay = 7;

    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth = new Date(year, month, 0).getDate();

    const days = [];

    for (let i = startDay - 1; i > 0; i--) {
        days.push({
            day: daysInPrevMonth - i + 1,
            type: 'prev',
            fullDate: new Date(year, month - 1, daysInPrevMonth - i + 1)
        });
    }

    for (let i = 1; i <= daysInMonth; i++) {
        const current = new Date(year, month, i);
        days.push({
            day: i,
            type: 'current',
            fullDate: current,
            isSelected: isSameDay(current, tempSelectedDate.value),
            isToday: isSameDay(current, today)
        });
    }

    const remainingCells = 42 - days.length;
    for (let i = 1; i <= remainingCells; i++) {
        days.push({
            day: i,
            type: 'next',
            fullDate: new Date(year, month + 1, i)
        });
    }

    return days;
});

function isSameDay(d1, d2) {
    if (!d1 || !d2) return false;
    return d1.getDate() === d2.getDate() &&
        d1.getMonth() === d2.getMonth() &&
        d1.getFullYear() === d2.getFullYear();
}

const toggle = () => isOpen.value = !isOpen.value;

const prevMonth = () => {
    viewDate.value = new Date(viewDate.value.getFullYear(), viewDate.value.getMonth() - 1, 1);
};

const nextMonth = () => {
    viewDate.value = new Date(viewDate.value.getFullYear(), viewDate.value.getMonth() + 1, 1);
};

const selectDay = (dayObj) => {
    tempSelectedDate.value = dayObj.fullDate;
    if (dayObj.type !== 'current') {
        viewDate.value = new Date(dayObj.fullDate.getFullYear(), dayObj.fullDate.getMonth(), 1);
    }
};

const monthTitle = computed(() => {
    const formatter = new Intl.DateTimeFormat('ru-RU', { month: 'long', year: 'numeric' });
    const parts = formatter.formatToParts(viewDate.value);
    const month = parts.find(p => p.type === 'month').value;
    const year = parts.find(p => p.type === 'year').value;
    return `${month.charAt(0).toUpperCase() + month.slice(1)} ${year}`;
});

const handleClickOutside = (event) => {
    if (wrapperRef.value && !wrapperRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => { document.addEventListener('click', handleClickOutside); });
onUnmounted(() => { document.removeEventListener('click', handleClickOutside); });


const onReset = () => {
    tempSelectedDate.value = null;
};

const onApply = () => {
    emit('update:modelValue', tempSelectedDate.value);
    emit('change', tempSelectedDate.value);
    isOpen.value = false;
};
</script>

<template>
    <div class="datepicker-wrapper" ref="wrapperRef">
        <button class="trigger-btn" @click="toggle" title="Открыть календарь">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M8 1C8.55228 1 9 1.44772 9 2V6C9 6.55228 8.55228 7 8 7C7.44772 7 7 6.55228 7 6V2C7 1.44772 7.44772 1 8 1Z" fill="#7F7F9D"/>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M16 1C16.5523 1 17 1.44772 17 2V6C17 6.55228 16.5523 7 16 7C15.4477 7 15 6.55228 15 6V2C15 1.44772 15.4477 1 16 1Z" fill="#7F7F9D"/>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M5 5C4.44772 5 4 5.44772 4 6V20C4 20.5523 4.44772 21 5 21H19C19.5523 21 20 20.5523 20 20V6C20 5.44772 19.5523 5 19 5H5ZM2 6C2 4.34315 3.34315 3 5 3H19C20.6569 3 22 4.34315 22 6V20C22 21.6569 20.6569 23 19 23H5C3.34315 23 2 21.6569 2 20V6Z" fill="#7F7F9D"/>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M2 10C2 9.44772 2.44772 9 3 9H21C21.5523 9 22 9.44772 22 10C22 10.5523 21.5523 11 21 11H3C2.44772 11 2 10.5523 2 10Z" fill="#7F7F9D"/>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M7 14C7 13.4477 7.44772 13 8 13H8.01C8.56228 13 9.01 13.4477 9.01 14C9.01 14.5523 8.56228 15 8.01 15H8C7.44772 15 7 14.5523 7 14Z" fill="#7F7F9D"/>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M11 14C11 13.4477 11.4477 13 12 13H12.01C12.5623 13 13.01 13.4477 13.01 14C13.01 14.5523 12.5623 15 12.01 15H12C11.4477 15 11 14.5523 11 14Z" fill="#7F7F9D"/>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M15 14C15 13.4477 15.4477 13 16 13H16.01C16.5623 13 17.01 13.4477 17.01 14C17.01 14.5523 16.5623 15 16.01 15H16C15.4477 15 15 14.5523 15 14Z" fill="#7F7F9D"/>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M7 18C7 17.4477 7.44772 17 8 17H8.01C8.56228 17 9.01 17.4477 9.01 18C9.01 18.5523 8.56228 19 8.01 19H8C7.44772 19 7 18.5523 7 18Z" fill="#7F7F9D"/>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M11 18C11 17.4477 11.4477 17 12 17H12.01C12.5623 17 13.01 17.4477 13.01 18C13.01 18.5523 12.5623 19 12.01 19H12C11.4477 19 11 18.5523 11 18Z" fill="#7F7F9D"/>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M15 18C15 17.4477 15.4477 17 16 17H16.01C16.5623 17 17.01 17.4477 17.01 18C17.01 18.5523 16.5623 19 16.01 19H16C15.4477 19 15 18.5523 15 18Z" fill="#7F7F9D"/>
            </svg>
        </button>

        <div v-if="isOpen" class="calendar-popup">
            <div class="calendar-header">
                <button class="nav-btn" @click="prevMonth">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <span class="month-label">{{ monthTitle }}</span>
                <button class="nav-btn" @click="nextMonth">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>

            <div class="weekdays-grid">
                <span v-for="day in weekDays" :key="day">{{ day }}</span>
            </div>

            <div class="days-grid">
                <div
                    v-for="(item, index) in daysGrid"
                    :key="index"
                    class="day-cell"
                    :class="{
            'is-prev-next': item.type !== 'current',
            'is-selected': item.isSelected,
            'is-today': item.isToday && !item.isSelected
          }"
                    @click="selectDay(item)"
                >
                    {{ item.day }}
                </div>
            </div>

            <div class="calendar-footer">
                <button class="btn btn-reset" @click="onReset">Сбросить</button>
                <button class="btn btn-apply" @click="onApply">Применить</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.calendar-popup {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    position: absolute;
    bottom: 45px;
    left: -120px;
    width: 280px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    padding: 16px;
    z-index: 1000;
    user-select: none;
}

.datepicker-wrapper { position: relative; display: inline-block; }

.trigger-btn {
    background: transparent;
    border: none;
    cursor: pointer;
    color: #fff;
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.trigger-btn:hover { opacity: 0.8; }

.calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.month-label { font-weight: 600; color: #1a1a1a; font-size: 15px; }
.nav-btn { background: transparent; border: none; cursor: pointer; padding: 4px; border-radius: 4px; }
.nav-btn:hover { background-color: #f3f4f6; }

.weekdays-grid { display: grid; grid-template-columns: repeat(7, 1fr); margin-bottom: 8px; }
.weekdays-grid span { text-align: center; font-size: 12px; color: #6b7280; font-weight: 500; }

.days-grid { display: grid; grid-template-columns: repeat(7, 1fr); row-gap: 8px; column-gap: 2px; margin-bottom: 16px; }

.day-cell {
    width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; cursor: pointer;
    border-radius: 50%;
    color: #1f2937;
    transition: all 0.2s;
    margin: 0 auto;
}

.day-cell.is-prev-next {
    background-color: #f1f5f9; color: #9ca3af;
    border-radius: 8px; width: 32px;
}

.day-cell:hover:not(.is-selected):not(.is-prev-next) { background-color: #f3f4f6; }

.day-cell.is-selected { background-color: #6366f1; color: white; }

.day-cell.is-today { background-color: white; border: 1px solid #6366f1; color: #1f2937; }

.calendar-footer { display: flex; justify-content: space-between; gap: 10px; margin-top: 10px; }
.btn { flex: 1; padding: 8px 0; border-radius: 8px; font-size: 12px; font-weight: 500; cursor: pointer; border: none; transition: opacity 0.2s; }
.btn:hover { opacity: 0.9; }
.btn-reset { background-color: #eef2ff; color: #4f46e5; }
.btn-apply { background-color: #6366f1; color: white; }
</style>
