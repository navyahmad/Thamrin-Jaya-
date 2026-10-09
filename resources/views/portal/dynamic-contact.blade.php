@php
$details = array_filter(array_intersect_key($contact, array_flip(['address', 'hours', 'phone', 'email'])));
$whatsapp = preg_match('/^[1-9][0-9]{7,14}$/', $contact['whatsapp'] ?? '') ? $contact['whatsapp'] : null;
@endphp
<div @class(['inquiry-grid', 'is-single' => ! $details && ! $whatsapp])>
@if($details || $whatsapp)<div><dl class="contact-details">@foreach(['address' => 'Alamat', 'hours' => 'Jam operasional', 'phone' => 'Telepon', 'email' => 'Email'] as $key => $label)@if(!empty($contact[$key]))<div><dt>{{ $label }}</dt><dd>{{ $contact[$key] }}</dd></div>@endif @endforeach</dl>
@if($whatsapp)<a class="button" href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener noreferrer">WhatsApp Sales ↗</a>@endif</div>@endif
@if($preview)<p>Form inquiry dinonaktifkan pada preview.</p>@else
<form class="inquiry-form" method="POST" action="{{ $company ? route('company.inquiry', $company->slug) : route('group.inquiry') }}">@csrf
@if(!$company)<label class="field"><span>Tujuan perusahaan</span><select name="company_id" required><option value="">Pilih perusahaan</option>@foreach($content->companies() as $unit)<option value="{{ $unit->id }}" @selected(old('company_id') == $unit->id)>{{ $unit->name }}</option>@endforeach</select>@error('company_id')<small class="field-error">{{ $message }}</small>@enderror</label>@endif
<x-field name="name" label="Nama" required maxlength="120"/><x-field name="email" label="Email" type="email" required maxlength="255"/><x-field name="phone" label="Telepon" maxlength="40"/><x-field name="subject" label="Subjek" required maxlength="200"/><x-field name="message" label="Pesan" type="textarea" required minlength="10" maxlength="5000"/>
<div class="honeypot" aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off"></div><label class="checkbox-row"><input type="checkbox" name="consent" value="1" @checked(old('consent')) required> Saya menyetujui penggunaan data ini untuk menindaklanjuti inquiry.</label>@error('consent')<small class="field-error">{{ $message }}</small>@enderror<button class="button">Kirim pesan</button></form>@endif</div>
