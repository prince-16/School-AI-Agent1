<?php

namespace App\Livewire\Schools;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('No school assigned')]
class NoSchool extends Component
{
    public function render()
    {
        return view('livewire.schools.no-school');
    }
}
