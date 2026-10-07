<?php

use App\Http\Controllers\HomeController;
use App\Models\Camp;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    $camp_counter = Camp::where('finish', '=', true)->where('counter', '>', 0)->get()->count();
    $healhform_counter = Camp::where('finish', '=', true)->where('counter', '>', 0)->sum('counter');

    return view('welcome', compact('camp_counter', 'healhform_counter'));
});

Route::get('/home', [HomeController::class, 'home']);

Route::match(['post', 'get'], '/healthform', ['as' => 'healthform.edit', 'uses' => 'HealthFormController@edit']);
Route::get('/healthform/createNew', ['as' => 'healthform.createNew', 'uses' => 'HealthFormController@createNew']);
Route::patch('/healthform/update/{healthform}', ['as' => 'healthform.update', 'uses' => 'HealthFormController@update']);
Route::get('/healthform/show/{healthform}', ['as' => 'healthform.show', 'uses' => 'HealthFormController@show']);
Route::get('/healthform/download/{healthform}', ['as' => 'healthform.downloadPDF', 'uses' => 'HealthFormController@downloadPDF']);
Route::get('healthform/searchajaxcity', ['as' => 'searchajaxcity', 'uses' => 'HealthFormController@searchResponseCity']);
Route::get('healthform/searchajaxgroups', ['as' => 'searchajaxgroups', 'uses' => 'HealthFormController@searchResponseGroups']);
Route::get('healthform/downloadAllergy/{healthform}', ['as' => 'downloadAllergy', 'uses' => 'HealthFormController@downloadAllergy']);

Route::get('/loginLeiter', function () {
    $user = User::where('username', 'leiter1@demo')->firstOrFail();
    Auth::login($user);

    return redirect('dashboard');
});

Route::get('/loginLagerleiter', function () {
    $user = User::where('username', 'lagerleiter@demo')->firstOrFail();
    Auth::login($user);

    return redirect('dashboard');
});

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return redirect('/');
})->middleware(['auth', 'signed'])->name('verification.verify');

Auth::routes([
    'register' => true, // Registration Routes...
    'reset' => true, // Password Reset Routes...
    'verify' => true, // Email Verification Routes...
]);

Route::group(['middleware' => 'verified'], function () {

    Route::get('/dashboard', [HomeController::class, 'index']);

    Route::get('/user/{user}', ['as' => 'dashboard.user', 'uses' => 'UsersController@edit']);
    Route::patch('/user/{user}', ['as' => 'dashboard.update', 'uses' => 'UsersController@update']);

    Route::resource('/camps', 'CampController')->only(['create', 'store', 'update']);

    Route::group(['middleware' => 'helper'], function () {
        Route::resource('dashboard/healthinformation', 'HealthInformationController')->only(['index', 'show']);
        Route::get('healthinformation/createDataTables', ['as' => 'healthinformation.CreateDataTables', 'uses' => 'HealthInformationController@createDataTables']);
        Route::get('healthinformation/searchajaxcode', ['as' => 'searchajaxcode', 'uses' => 'HealthInformationController@searchResponseCode']);
        Route::get('healthinformation/search', ['as' => 'healthinformation.search', 'uses' => 'HealthInformationController@search']);
        Route::resource('dashboard/interventions', 'InterventionController')->only(['index', 'store', 'edit']);
        Route::get('interventions/createDataTables', ['as' => 'interventions.CreateDataTables', 'uses' => 'InterventionController@createDataTables']);
        Route::get('interventions/close/{intervention}', ['as' => 'interventions.close', 'uses' => 'InterventionController@close']);
        Route::get('healthinformation/{healthInformation}/intervention/{intervention}/createNew', ['as' => 'interventions.createNew', 'uses' => 'InterventionController@createNew']);
        Route::get('interventions/addNew', ['as' => 'interventions.addNew', 'uses' => 'InterventionController@addNew']);
        Route::get('interventions/closeAjax', ['as' => 'interventions.closeAjax', 'uses' => 'InterventionController@closeAjax']);
        Route::resource('dashboard/healthforms', 'HealthFormController')->only(['index']);
        Route::get('healthforms/createDataTables', ['as' => 'healthforms.CreateDataTables', 'uses' => 'HealthFormController@createDataTables']);
    });

    Route::group(['middleware' => 'manager'], function () {
        Route::resource('dashboard/healthforms', 'HealthFormController')->only(['create', 'store']);
        Route::get('dashboard/users/searchajaxuser', ['as' => 'searchajaxuser', 'uses' => 'AdminUsersController@searchResponseUser']);
        Route::post('dashboard/users/add', ['as' => 'users.add', 'uses' => 'AdminUsersController@add']);
        Route::resource('dashboard/users', 'AdminUsersController', ['as' => 'dashboard']);
        Route::get('users/createDataTables', ['as' => 'users.CreateDataTables', 'uses' => 'AdminUsersController@createDataTables']);
        Route::resource('dashboard/questions', 'QuestionController', ['as' => 'dashboard']);
        Route::get('questions/createDataTables', ['as' => 'questions.CreateDataTables', 'uses' => 'QuestionController@createDataTables']);
        Route::post('dashboard/healthforms/import', ['as' => 'healthforms.import', 'uses' => 'HealthFormController@import']);
        Route::get('dashboard/healthforms/showOrEdit/{healthform}', ['as' => 'healthforms.showOrEdit', 'uses' => 'HealthFormController@showOrEdit']);
        Route::get('dashboard/healthforms/uploadFile', ['as' => 'dashboard.healthforms.uploadFile', 'uses' => 'HealthFormController@UploadFile']);
        Route::get('healthinformation/print/{healthInformation}', ['as' => 'healthinformation.print', 'uses' => 'HealthInformationController@print']);
        Route::post('dashboard/healthinformation/uploadProtocol/{healthinformation}', ['as' => 'uploadProtocol', 'uses' => 'HealthInformationController@uploadProtocol']);
        Route::get('healthinformation/downloadProtocol/{healthinformation}', ['as' => 'downloadProtocol', 'uses' => 'HealthInformationController@downloadProtocol']);
        Route::post('healthforms/{healthform}/open', ['as' => 'healthforms.open', 'uses' => 'HealthFormController@open']);
        Route::get('dashboard/healthform/downloadFile', ['as' => 'healthforms.downloadFile', 'uses' => 'HealthFormController@downloadFile']);
        Route::post('dashboard/healthform/uploadFile', 'HealthFormController@uploadFile');
        Route::resource('dashboard/camps', 'AdminCampController', ['as' => 'dashboard']);

    });

    Route::group(['middleware' => 'admin'], function () {
        Route::get('audits', ['as' => 'dashboard.audits', 'uses' => 'AuditController@index']);
        Route::resource('dashboard/helps', 'HelpController');
        // Route::resource('dashboard/interventionclasses', 'InterventionClassController');
        Route::post('healthforms/{healthform}/newCode', ['as' => 'healthforms.newCode', 'uses' => 'HealthFormController@newCode']);

        Route::get('admin/run-migrations', function () {
            return Artisan::call('migrate', ['--force' => true]);
        });

        Route::get('admin/run-deployment', function () {
            Artisan::call('optimize:clear');

            return true;
        });
    });
});
