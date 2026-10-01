@props(['selected' => null])

@foreach(config('kasus.labels', []) as $value => $label)
    <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $label }}</option>
@endforeach
