<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'display_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * Note: the legacy users table (2014 migration) only has a `name` column;
     * extended profile columns come from later Voyager/BREAD seeds. Keep this
     * compatible with both by filling `name` and any available columns.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        $user = new User();
        $user->name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        if (!$user->name) {
            $user->name = $data['display_name'] ?? $data['email'];
        }
        $user->email = $data['email'];
        $user->password = Hash::make($data['password']);
        // Fill extended columns only if the schema supports them.
        foreach (['first_name', 'last_name', 'display_name', 'role_id'] as $col) {
            if ($col === 'role_id') {
                continue;
            }
            if (isset($data[$col])) {
                try {
                    $user->{$col} = $data[$col];
                } catch (\Throwable $e) {
                    // Column missing on legacy schema — ignore.
                }
            }
        }
        try {
            $user->role_id = \App\Support\RoleIds::VOLUNTEER;
        } catch (\Throwable $e) {
            // Ignore when column is missing.
        }
        $user->save();

        return $user;
    }
}
