@extends('layouts.app')

@section('title', __('Upload Module'))

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('modules/sidebar_menu')
@endsection

@section('content')

    @include('partials/flash_messages')

    <div class="section-heading">
        {{ __('Upload Module') }}
    </div>

    <div class="row-container margin-top">
        <p class="text-help margin-bottom">
            {{ __('Install a custom or third-party module by uploading its .zip file. The archive must contain a single module folder at its root with a valid module.json file inside.') }}
        </p>
        <p class="text-help margin-bottom">
            <strong>{{ __('Warning') }}:</strong>
            {{ __('A module can run arbitrary PHP code on this server. Only upload modules from sources you trust.') }}
        </p>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="margin-none">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('moduleuploader.upload') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <input type="file" name="module_zip" accept=".zip" required>
            </div>
            <button type="submit" class="btn btn-primary">{{ __('Upload') }}</button>
            <a href="{{ route('modules') }}" class="btn btn-link">{{ __('Cancel') }}</a>
        </form>
    </div>
@endsection
