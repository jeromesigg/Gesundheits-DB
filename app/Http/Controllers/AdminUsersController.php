<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\Camp;
use App\Models\CampUser;
use App\Models\Help;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class AdminUsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
        $aktUser = Auth::user();
        if ($aktUser->isAdmin()) {
            $roles = Role::pluck('name', 'id')->all();
        } else {
            $roles = Role::where('id', '>', config('status.role_Administrator'))->pluck('name', 'id')->all();
        }
        $title = 'Personen';
        $help = Help::where('title', $title)->first();

        return view('dashboard.users.index', compact('roles', 'title', 'help'));
    }

    public function createDataTables()
    {
        //
        $camp = Auth::user()->camp;
        $users = $camp->allusers;

        return DataTables::of($users)
            ->editColumn('last_login_at', function (User $user) {
                return $user->last_login_at ? Carbon::parse($user->last_login_at)->diffForHumans() : '';
            })
            ->editColumn('username', function (User $user) {
                return '<a title="Person bearbeiten" href='.\URL::route('dashboard.users.edit', $user).'>'.$user['username'].'</a>';
            })
            ->addColumn('role', function (User $user) {
                $camp = Auth::user()->camp;
                if ($camp) {
                    $camp_user = $user->camp_user()->first();

                    return [
                        'display' => $camp_user->role ? $camp_user->role['name'] : '',
                        'sort' => $camp_user->role ? $camp_user->role['id'] : '',
                    ];
                } else {
                    return [
                        'display' => $user->role ? $user->role['name'] : '',
                        'sort' => $user->role ? $user->role['id'] : '',
                    ];
                }
            })
            ->addColumn('active', function (User $user) {
                $camp = Auth::user()->camp;
                if ($camp) {
                    $camp_user = $user->camp_user()->first();

                    return $camp_user->active ? 'Aktiv' : 'Inaktiv';
                } else {
                    return '';
                }
            })
            ->addColumn('Actions', function (User $user) {
                //                return '<button data-remote='.\URL::route('dashboard.users.destroy', $user->id).' class="btn btn-danger btn-sm">Löschen</button>';
                //                return '<a href="javascript:void(0)"  title="Löschen" data-remote='.\URL::route('dashboard.users.destroy', $user).'>Löschen</a>';

                return '<a class="link--delete" href="javascript:void(0)"  title="Löschen" data-remote='.\URL::route('dashboard.users.destroy', $user).'><i class="fa-solid fa-trash-can fa-xl" style="color: #ff0000;"></i></a>';
            })
            ->rawColumns(['username', 'Actions'])
            ->make(true);
    }

    public function searchResponseUser(Request $request)
    {
        $camp = Auth::user()->camp;
        $allusers = $camp->allusers->pluck('id')->toArray();

        $users = User::where('role_id', '<>', config('status.role_Administrator'))->whereNotIn('id', $allusers)->search($request->get('term'))->get();

        return $users;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    public function add(Request $request)
    {
        $input = $request->all();
        $aktUser = Auth::user();

        if ((! $aktUser->demo) && ($input['user_id'])) {
            $camp = $aktUser->camp;
            $user = User::findOrFail($input['user_id']);
            CampUser::firstOrCreate(['camp_id' => $camp->id, 'user_id' => $user->id],
                ['role_id' => $input['role_id_add']]);
        }

        return redirect('/dashboard/users');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        //
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return redirect()->to(url()->previous())
                ->withErrors($validator)
                ->withInput();
        }

        $aktUser = Auth::user();
        if (! $aktUser->demo) {
            if (trim($request->password) == '') {
                $input = $request->except('password');
            } else {
                $input = $request->all();
                $input['password'] = bcrypt($request->password);
            }
            $input['slug'] = Str::slug($input['username']);

            $user = User::create($input);
            $camp = $aktUser->camp;
            Helper::updateCamp($user, $camp);
        }

        return redirect('/dashboard/users');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit(User $user)
    {
        //
        $aktUser = Auth::user();
        if ($aktUser->isAdmin()) {
            $roles = Role::pluck('name', 'id')->all();
        } else {
            $roles = Role::where('id', '>', config('status.role_Administrator'))->pluck('name', 'id')->all();
        }
        $camp_user = $user->camp_user()->first();
        $title = 'Person aktualisieren';
        $help = Help::where('title', $title)->first();
        $help['main_title'] = 'Personen';
        $help['main_route'] = '/dashboard/users';

        return view('dashboard.users.edit', compact('user', 'roles', 'camp_user', 'title', 'help'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, User $user)
    {
        //
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255',
            'email' => 'required|max:255|unique:users,email,'.$user['id'],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->to(url()->previous())
                ->withErrors($validator)
                ->withInput();
        }
        //        if (!$user->demo) {
        if (trim($request->password) == '') {
            $input = $request->except('password');
        } else {
            $input = $request->all();
            $input['password'] = bcrypt($request->password);
        }
        $input['slug'] = Str::slug($input['username']);

        $user->update($input);
        $input_camp_user['active'] = isset($input['active']);
        $camp_user = $user->camp_user()->first();
        $camp_user->update($input_camp_user);
        if (! $input_camp_user['active']) {
            $camp = Auth::user()->camp;
            if ($user->camp['id'] === $camp['id']) {
                $camp_global = Camp::where('global_camp', true)->first();
                Helper::updateCamp($user, $camp_global);
            }
        }

        //        }
        return redirect('/dashboard/users');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy(User $user)
    {
        //
        $aktUser = Auth::user();
        if (! $aktUser->demo) {
            $camp = $aktUser->camp;
            $camp_user = CampUser::where('camp_id', '=', $camp['id'])->where('user_id', '=', $user['id'])->first();
            $camp_user->delete();
            if ($user->camp['id'] === $camp['id']) {
                $camp_global = Camp::where('global_camp', true)->first();
                Helper::updateCamp($user, $camp_global);
            }
        }

        return redirect('/dashboard/users');
    }
}
