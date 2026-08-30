<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class WebGISController extends Controller
{
    public function index(): View
    {
        return view('webgis.index');
    }
}
