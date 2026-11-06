@extends('emails.layout')

@section('content')
    <h2>Dear {{ $recipient['name'] }},</h2>

    @if($recipient['type'] === 'requester')
        <p>Thank you for submitting your request: {{$request->capacity_development_title}} through the Ocean Connector. Your submission has been received and will be reviewed by the IOC
            Secretariat.</p>
    @endif

    @if($recipient['type'] === 'requester')
        <p>If any clarifications or additional information are required, we will contact you within two weeks. Thank you
            for your engagement.</p>
        <a href="{{ route('request.show',['id'=>$request->id])}}" class="cta-button">
            Track Your Request
        </a>
    @endif

    <p>Sincerely,<br>
        IOC Secretariat</p>
@endsection
