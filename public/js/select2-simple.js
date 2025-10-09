// function initializeSelect2OnlyMarked() {
//     const select2Fields = document.querySelectorAll('select[data-select2-field="true"]');
    
//     select2Fields.forEach((select) => {
//         if (select._select2Initialized) return;

//         select.classList.remove('choices__input', 'select2-hidden-accessible');
//         select.removeAttribute('hidden');
//         select.removeAttribute('tabindex');
//         select.removeAttribute('aria-hidden');
//         select.style.display = 'block';

//         $(select).select2({
//             width: '100%',
//             placeholder: 'Выберите значение',
//             allowClear: true,
//             dropdownParent: $(select).closest('.modal').length ? $(select).closest('.modal') : document.body
//         });
        
//         select._select2Initialized = true;
//     });
// }

// $.fn.select2.defaults = $.fn.select2.defaults || {};
// $.fn.select2.defaults.initSelection = undefined;

// document.addEventListener('DOMContentLoaded', function() {
//     setTimeout(initializeSelect2OnlyMarked, 100);
// });

// if (typeof Livewire !== 'undefined') {
//     Livewire.hook('element.updated', (el) => {
//         setTimeout(() => {
//             initializeSelect2OnlyMarked();
//         }, 150);
//     });
// }