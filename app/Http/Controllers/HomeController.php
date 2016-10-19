<?php namespace App\Http\Controllers;

// because adminlte is drunk
class HomeController extends Controller
{
	public function index()
	{
		return view('welcome');
	}
}
