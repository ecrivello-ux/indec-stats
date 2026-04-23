<?php

use App\Livewire\CrossExplorer;
use App\Livewire\Dashboard;
use App\Livewire\DataTool;
use Illuminate\Support\Facades\Route;

Route::get('/', Dashboard::class);
Route::get('/explorador', CrossExplorer::class);
Route::get('/datos', DataTool::class);
