<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Support\Facades\Auth;
use Invisnik\LaravelSteamAuth\SteamAuth;

class AuthController extends Controller
{

	use AuthenticatesAndRegistersUsers, ThrottlesLogins;

	/**
	 * @var SteamAuth
	 */
	private $steam;

	public function __construct(SteamAuth $steam)
	{
		$this->steam = $steam;
	}

	public function login()
	{
		if (!$this->steam->validate()) {
			return $this->steam->redirect(); //redirect to Steam login page
		}

		$info = $this->steam->getUserInfo();
		if (is_null($info)) {
			return response()->view('errors.500');
		}

		$user = User::where('steamid', $info->getSteamID64())->first();
		if (!is_null($user)) {
			$user->update(['username' => $info->getNick(), 'avatar' => $info->getProfilePictureFull()]); //update username and avatar
			Auth::login($user, true);

			return redirect($user->isAdmin() ? '/admin' : '/');
		} else {
			$user = User::create([
									 'username' => $info->getNick(),
									 'avatar'   => $info->getProfilePictureFull(),
									 'steamid'  => $info->getSteamID64()
								 ]);
			Auth::login($user, true);

			return redirect($user->isAdmin() ? '/admin' : '/');
		}

	}

	public function showLoginForm()
	{
		return $this->login();
	}

	public function showRegistrationForm()
	{
		return $this->login();
	}

	public function register()
	{
		return $this->login();
	}
}