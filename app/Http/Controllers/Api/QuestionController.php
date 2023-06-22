<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\User;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function create(Request $request) {
        $user = User::where('token', $request->token)->first();
        $question = $request->question;
        
        $saveData = Question::create([
            'user_id' => $user->id,
            'question' => $question
        ]);

        return response()->json([
            'status' => 200,
            'message' => "Berhasil menambahkan pertanyaan untuk virtual assistant"
        ]);
    }
    public function update($id, Request $request) {
        $question = $request->question;
        $data = Question::where('id', $id);
        
        $toUpdate = [
            'answer' => $request->answer,
            'question' => $question
        ];

        $updateData = $data->update($toUpdate);

        return response()->json([
            'status' => 200,
            'message' => "Berhasil mengubah pertanyaan"
        ]);
    }
    public function delete($id) {
        $data = Question::where('id', $id);
        $question = $data->first();

        $data->delete();

        return response()->json([
            'status' => 200,
            'message' => "Berhasil menghapus pertanyaan"
        ]);
    }
}
