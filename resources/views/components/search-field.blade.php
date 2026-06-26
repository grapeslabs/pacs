@props([
    'form' => '',
])

<div class="ms-search">
    {!! $form !!}
</div>

<style>
    .ms-search { position: relative; width: 100%; max-width: 380px; }
    .ms-search .search-form,
    .ms-search .ms-search-group { width: 100%; }
    .ms-search .ms-search-group { position: relative; display: flex; align-items: center; }

    .ms-search .ms-search-input {
        width: 100%;
        background-color: #ffffff;
        border: 1px solid #D7DBEF;
        border-radius: 10px;
        padding: 0.625rem 2.25rem 0.625rem 2.5rem;
        font-size: 0.875rem;
        line-height: 1.25rem;
        color: #1a1a2e;
        outline: none;
        box-shadow: none;
        transition: border-color 0.15s ease;
    }
    .ms-search .ms-search-input::placeholder { color: #9CA3AF; }
    .ms-search .ms-search-input:focus {
        border-color: #7381F4;
        box-shadow: none;
    }

    .ms-search .ms-search-submit {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        display: inline-flex;
        align-items: center;
        padding: 0 0.5rem 0 0.75rem;
        background: transparent;
        border: none;
        cursor: pointer;
        color: #9CA3AF;
        z-index: 2;
    }
    .ms-search .ms-search-submit svg { width: 18px; height: 18px; }

    .ms-search .ms-search-clear {
        position: absolute;
        right: 0.5rem;
        top: 50%;
        transform: translateY(-50%);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: none;
        cursor: pointer;
        color: #9CA3AF;
        z-index: 2;
    }
    .ms-search .ms-search-clear svg { width: 16px; height: 16px; }
</style>
