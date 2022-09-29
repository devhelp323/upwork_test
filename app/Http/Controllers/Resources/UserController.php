<?php

namespace App\Http\Controllers\Resources;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Notifications\PrivateMessageNotification;
use App\User;
use App\Util\Utils;
use Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return User::orderBy('id', 'desc')->paginate(5);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validator($request);

        $input = $request->except(['photo']);
        $input['password'] = bcrypt($input['password']);

        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $randName = $photo->hashName();
            $photo->storeAs('avatars', $randName, 'public');
            $input['photo'] = 'avatars/' . $randName;
        }

        $user = User::create($input);

        $user->sendSMS('Your account was just created in Laravel test website');

        return response()->json($user, 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $User
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $input = $request->except(['photo']);

        $this->validator($request, $user->id);

        if (!empty($input['password'])) {
            $input['password'] = bcrypt($input['password']);
        }

        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $randName = $photo->hashName();
            $photo->storeAs('avatars', $randName, 'public');
            $input['photo'] = 'avatars/' . $randName;
        }

        $user->update($input);

        return $user;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\User  $User
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();

        $user->sendSMS('Your account was removed by admin');

        $user->notify(new PrivateMessageNotification('Your account was removed by admin'));

        return response()->json(null, 204);
    }

    private function validator(Request $request, $id = null)
    {
        $emailValidation = 'required|max:191|email|unique:users';

        if (!empty($id)) {
            $emailValidation .= ',email,'.$id;
        }

        $request->validate([
            'name' => 'required|max:191',
            'email' => $emailValidation,
            'type_id' => 'required|integer|between:1,2',
            'password' => 'sometimes|min:6|confirmed',
        ], [
            'type_id.*' => __('users.invalid_user_type'),
        ]);
    }
}
