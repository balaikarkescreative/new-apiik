<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/test', function()
{
	$beautymail = app()->make(Snowfire\Beautymail\Beautymail::class);
	$beautymail->send('emails.welcome', [], function($message)
	{
		$message
			->from('syhrlanwr@gmail.com.com')
			->to('syahrulanwararia89@gmail.com', 'Ujang')
			->subject('Welcome!');
	});

});
