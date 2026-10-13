@props(['cancel', 'submit' => 'Simpan'])
<div class="mt-6 flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end">
    <a href="{{ $cancel }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-2 text-sm font-semibold text-gray-700 hover:bg-action-secondary-hover focus-visible:ring-2 focus-visible:ring-green-500">Batal</a>
    <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-action-primary px-5 py-2 text-sm font-semibold text-white hover:bg-action-primary-hover focus-visible:ring-2 focus-visible:ring-green-500">{{ $submit }}</button>
</div>
