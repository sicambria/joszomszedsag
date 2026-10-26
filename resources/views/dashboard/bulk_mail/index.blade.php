@extends('dashboard.layout')
@section('site_title', __('Körlevelek'))
@section('page_title')
    <div class="page-title-icon"><i class="pe-7s-mail icon-gradient bg-white"></i></div>
    <div>
        {{ __('Körlevelek') }}
        <div class="page-title-subheading">{{ __('Elküldött körlevelek listája') }}</div>
    </div>
@endsection

@section('content')
    <h3>{{ __('Körlevelek') }}</h3>
    @if (session('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif
    <p><a class="btn btn-primary" href="{{ route('bulk_mail_create') }}">{{ __('Új körlevél') }}</a></p>
    <table class="display" style="width: 100%;">
        <thead>
        <tr>
            <th>#</th>
            <th>{{ __('Tárgy') }}</th>
            <th>{{ __('Csoportok') }}</th>
            <th>{{ __('Küldve') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($mails as $mail)
            <tr>
                <td>{{ $mail->id }}</td>
                <td>{{ $mail->subject }}</td>
                <td>{{ $mail->groups }}</td>
                <td>{{ $mail->created_at }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $mails->links() }}
@endsection
