<?php

namespace App\Http\Controllers;

use App\Models\RangeStudy;
use App\Poker\RangeQuiz;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(): View
    {
        return view('quiz.index', [
            'studies' => RangeStudy::ordered()
                ->withCount(['scenarios as quiz_scenarios_count' => fn ($query) => $query->where('quiz_enabled', true)])
                ->get(),
            'questionCount' => RangeQuiz::QUESTIONS,
        ]);
    }

    public function show(RangeStudy $rangeStudy): View
    {
        return view('quiz.show', [
            'study' => $rangeStudy,
            'questions' => RangeQuiz::questions($rangeStudy),
        ]);
    }
}
