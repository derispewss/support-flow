<?php

namespace App\Modules\Support\Providers;

use App\Modules\Support\Livewire\Admin\TicketDetail;
use App\Modules\Support\Livewire\Admin\TicketQueue;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class SupportServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('support.ticket-queue', TicketQueue::class);
        Livewire::component('support.ticket-detail', TicketDetail::class);
    }
}
