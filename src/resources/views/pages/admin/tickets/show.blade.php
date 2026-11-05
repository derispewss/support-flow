@extends('layouts.admin')

@section('title', $ticket->ticket_number.' — '.config('app.name'))

@section('content')
    <livewire:support.ticket-detail :ticket="$ticket" />
@endsection
