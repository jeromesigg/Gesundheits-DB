<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Http\Requests\AddCampUserRequest;
use App\Http\Requests\StoreCampUserRequest;
use App\Http\Requests\UpdateCampUserRequest;
use App\Models\Camp;
use App\Models\CampUser;
use App\Models\Help;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
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
        $roles = Role::whereIn('id', Role::assignableIds())->pluck('name', 'id')->all();
        $title = 'Personen';
        $help = Help::where('title', $title)->first();

        return view('dashboard.users.index', compact('roles', 'title', 'help'));
    }

    public function createDataTables()
    {
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
        $members = CampUser::where('camp_id', $camp->id)->pluck('user_id');

        return User::whereRaw('LOWER(email) = ?', [mb_strtolower(trim((string) $request->get('term')))])
            ->where('role_id', '<>', config('status.role_Administrator'))
            ->whereNotIn('id', $members)
            ->get(['id', 'username', 'email']);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create() {}

    public function add(AddCampUserRequest $request)
    {
        $aktUser = Auth::user();

        if (! $aktUser->demo) {
            CampUser::firstOrCreate(['camp_id' => $aktUser->camp_id, 'user_id' => $request->validated('user_id')],
                ['role_id' => $request->validated('role_id_add')]);
        }

        return redirect('/dashboard/users');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(StoreCampUserRequest $request)
    {
        $aktUser = Auth::user();
        if (! $aktUser->demo) {
            $user = User::create([
                'username' => $request->validated('username'),
                'email' => $request->validated('email'),
                'password' => bcrypt($request->validated('password')),
                'slug' => Str::slug($request->validated('username')),
                'role_id' => $request->validated('role_id'),
            ]);
            Helper::updateCamp($user, $aktUser->camp);
        }

        return redirect('/dashboard/users');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id) {}

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit(User $user)
    {
        $this->authorize('manage', $user);
        $roles = Role::whereIn('id', Role::assignableIds())->pluck('name', 'id')->all();
        $camp_user = $user->camp_user()->first();
        $user->role_id = $camp_user->role_id;
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
    public function update(UpdateCampUserRequest $request, User $user)
    {
        $camp = Auth::user()->camp;
        $role_id = (int) $request->validated('role_id');
        $active = $request->filled('active');

        $user->update([
            'username' => $request->validated('username'),
            'email' => $request->validated('email'),
            'slug' => Str::slug($request->validated('username')),
        ]);
        if (filled($request->validated('password'))) {
            $user->update(['password' => bcrypt($request->validated('password'))]);
        }
        $user->camp_user()->first()->update(['role_id' => $role_id, 'active' => $active]);
        if ($user->camp_id === $camp->id) {
            $user->update(['role_id' => $role_id]);
        }
        if (! $active && $user->camp_id === $camp->id) {
            $camp_global = Camp::where('global_camp', true)->first();
            Helper::updateCamp($user, $camp_global);
        }

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
        $this->authorize('manage', $user);
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
