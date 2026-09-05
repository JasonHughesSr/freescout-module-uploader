{{-- Overrides FreeScout's core resources/views/modules/sidebar_menu.blade.php --}}
{{-- (loaded first via View::prependLocation() in ModuleUploaderServiceProvider) --}}
{{-- to add the "Upload Module" link. Keep in sync with core if it changes. --}}
<div class="sidebar-title">
    {{ __('Modules') }}
</div>
<ul class="sidebar-menu">
	@if (count($installed_modules))
    	<li><a href="{{ route('modules') }}#installed"><i class="glyphicon glyphicon-saved"></i> {{ __('Installed Modules') }}</a></li>
    @endif

    <li><a href="{{ route('modules') }}#directory"><i class="glyphicon glyphicon-briefcase"></i> {{ __('Modules Directory') }}</a></li>
    <li><a href="{{ route('moduleuploader') }}"><i class="glyphicon glyphicon-upload"></i> {{ __('Upload Module') }}</a></li>
</ul>
