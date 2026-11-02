<?php

namespace App\Http\Controllers;

use App\Poker\FlopzerPresets;
use Illuminate\View\View;

class FlopzerController extends Controller
{
    /** Range-vs-board analyser. The maths run in the browser; the server only hands over the open-raise charts to load. */
    public function index(): View
    {
        return view('flopzer.index', ['presets' => FlopzerPresets::all()]);
    }
}
