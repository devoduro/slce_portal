<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;

class StudentAppLayout extends Component
{
    public $student;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->student = Auth::user()->student;
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render()
    {
        return view('components.student-app-layout');
    }
}
