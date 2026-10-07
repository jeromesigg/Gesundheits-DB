<?php

namespace App\Http\Controllers;

use App\Models\HealthInformation;
use App\Models\Help;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
        $healthinformation = [];
        $title = 'Individuelle Fragen';
        $help = Help::where('title', $title)->first();

        return view('dashboard.questions.index', compact('healthinformation', 'title', 'help'));
    }

    public function createDataTables()
    {
        //
        $akt_User = Auth::user();
        $camp = $akt_User->camp;
        $questions = $camp->questions();

        return DataTables::of($questions)
            ->editColumn('active', function (Question $questions) {
                return $questions['active'] ? 'Aktiv' : 'Archiviert';
            })
            ->addColumn('Actions', function (Question $questions) {
                return '<a href='.\URL::route('dashboard.questions.edit', $questions).' type="button" class="btn btn-success btn-sm">Bearbeiten</a>
                <button data-remote='.\URL::route('dashboard.questions.destroy', $questions).' class="btn btn-danger btn-sm">Löschen</button>';
            })
            ->rawColumns(['Actions'])
            ->make(true);
    }

    public function create(Request $request)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        //
        $input = $request->all();
        $akt_User = Auth::user();
        $camp = $akt_User->camp;
        $input['active'] = true;
        $input['camp_id'] = $camp['id'];

        Question::create($input);

        return redirect()->back();

    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Question $question)
    {
        //

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Question $question)
    {
        //
        $title = 'Individuelle Frage aktualisieren';
        $help = Help::where('title', $title)->first();
        $help['main_title'] = 'Individuelle Fragen';
        $help['main_route'] = '/dashboard/questions';

        return view('dashboard.questions.edit', compact('question', 'title', 'help'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Question $question)
    {
        //
        if (! Auth::user()->demo) {
            $input = $request->all();
            $input['active'] = isset($input['active']);
            $question->update($input);
        }

        return redirect('/dashboard/questions');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  HealthInformation  $healthInformation
     * @return Response
     */
    public function destroy(Question $question)
    {
        //
        $question->delete();
    }
}
