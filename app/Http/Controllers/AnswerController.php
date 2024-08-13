<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use Illuminate\Http\Request;

class AnswerController extends Controller
{
    public function index()
    {
        $answers = Answer::all();
        return response()->json($answers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'question_id' => 'required|exists:questions,id',
            'text' => 'required|string',
            'correct' => 'required|boolean',
        ]);

        $answer = Answer::create($validated);

        return response()->json(['message' => 'Answer created successfully.', 'answer' => $answer]);
    }

    public function show($id)
    {
        $answer = Answer::findOrFail($id);
        return response()->json($answer);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'text' => 'sometimes|required|string',
            'correct' => 'sometimes|required|boolean',
        ]);

        $answer = Answer::findOrFail($id);
        $answer->update($validated);

        return response()->json(['message' => 'Answer updated successfully.', 'answer' => $answer]);
    }

    public function destroy($id)
    {
        $answer = Answer::findOrFail($id);
        $answer->delete();

        return response()->json(['message' => 'Answer deleted successfully.']);
    }
}
