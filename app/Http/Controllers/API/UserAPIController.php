<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UpUser;
use Illuminate\Http\Request;

class UserAPIController extends Controller
{

    public function verifyTerms($id)
    {
        $upuser = Upuser::find($id);
        if ($upuser) {
            $acceptedTerms = $upuser->accepted_terms_conditions;
            if ($acceptedTerms == null) {
                $acceptedTerms = false;
            }
            return response()->json($acceptedTerms);
        } else {         // El registro no fue encontrado, por lo tanto, retorna false        
            return response()->json(['message' => 'No se encontro el user'], 404);
        }
    }

    public function updateAcceptedTerms($id, Request $request)
    {
        $user_found = UpUser::findOrFail($id);
        $accepted_terms = $request->input('accepted_terms_conditions');

        // Update 'accepted_terms_conditions'
        $user_found->accepted_terms_conditions = $accepted_terms;
        $user_found->save();

        return response()->json(['message' => 'Estado de Términos y condiciones actualizados con éxito'], 200);
    }

 
}
