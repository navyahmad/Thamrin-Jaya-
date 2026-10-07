@foreach($nodes as $node)
@if($node['children'])<details class="mega-menu"><summary>{{ $node['label'] }}</summary><div class="panel">@if($node['url'])<a href="{{ $node['url'] }}" @if($node['new_tab']) target="_blank" rel="noopener noreferrer" @endif>{{ $node['label'] }}</a>@endif @include('portal.dynamic-menu', ['nodes' => $node['children']])</div></details>
@else<a href="{{ $node['url'] }}" @if($node['new_tab']) target="_blank" rel="noopener noreferrer" @endif>{{ $node['label'] }}</a>@endif
@endforeach
