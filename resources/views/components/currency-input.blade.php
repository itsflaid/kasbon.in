@props(['model', 'value' => null, 'placeholder' => '20.000'])

<div
    x-data="{
        format(value) {
            const digits = String(value ?? '').replace(/\D/g, '');

            return digits ? new Intl.NumberFormat('id-ID').format(Number(digits)) : '';
        },
        sync() {
            const digits = $refs.display.value.replace(/\D/g, '');

            $refs.display.value = this.format(digits);
            $refs.raw.value = digits;
            $refs.raw.dispatchEvent(new Event('input', { bubbles: true }));
        },
        init() {
            $refs.display.value = this.format($refs.raw.value);
        }
    }"
    x-init="init()"
>
    <input
        type="hidden"
        x-ref="raw"
        wire:model="{{ $model }}"
        value="{{ $value ?? '' }}"
    >
    <input
        type="text"
        x-ref="display"
        inputmode="numeric"
        autocomplete="off"
        placeholder="{{ $placeholder }}"
        x-on:input="sync()"
        {{ $attributes->merge(['class' => 'w-full rounded-full border border-rule bg-surface px-4 py-2.5 text-sm text-ink placeholder-muted focus:border-primary focus:outline-none tabular-nums']) }}
    >
</div>
