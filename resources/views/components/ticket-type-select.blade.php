@props(['name' => 'ticket_type', 'value' => 'regular'])

<select name="{{ $name }}" id="{{ $name }}" {{ $attributes->merge(['class' => 'form-select']) }}>
    <option value="regular" {{ $value === 'regular' ? 'selected' : '' }}>Regular</option>
    <option value="vip" {{ $value === 'vip' ? 'selected' : '' }}>VIP</option>
    <option value="single" {{ $value === 'single' ? 'selected' : '' }}>Single</option>
    <option value="double" {{ $value === 'double' ? 'selected' : '' }}>Double</option>
</select> 