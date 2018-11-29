<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ManagerControllerAPI extends Controller
{
    public function index()
    {
        return Item::all();
    }
}
