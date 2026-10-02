@props(['name', 'label', 'value' => '', 'type' => 'text', 'required' => false, 'hint' => null])
<label class="field"><span>{{ $label }} @if($required)<span class="required">*</span>@endif</span>
@if($type === 'textarea')<textarea name="{{ $name }}" rows="5" @required($required) {{ $attributes }}>{{ old($name, $value) }}</textarea>
@else<input type="{{ $type }}" name="{{ $name }}" value="{{ $type === 'password' || $type === 'file' ? '' : old($name, $value) }}" @required($required) {{ $attributes }}>@endif
@if($hint)<small>{{ $hint }}</small>@endif
@error($name)<small class="field-error">{{ $message }}</small>@enderror</label>