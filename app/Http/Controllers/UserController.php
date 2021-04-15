<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request; 
use App\Models\User;
use Symfony\Component\HttpFoundation\Response; 

class UserController extends Controller
{

    private $model; 

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(User $mUser)
    {
        $this->model = $mUser;
    }

    public function getAll()
    {

        $users = $this->model->all();
        
        if(count($users) > 0){
            $users['total'] = count($users);
            return response()->json($users, Response::HTTP_OK);
        }

        return response()->json(['total' => 0], Response::HTTP_OK);
       
    }

    public function get( $id )
    {
        $user = $this->model->find($id);

        if(count($user) > 0){
            return response()->json($user, Response::HTTP_OK);
        }

        return response()->json(['message' => 'ID não encontrado'], Response::HTTP_OK);
        
    }

    public function create(Request $request)
    {
        $user = $this->model->create($request->all());

        return response()->json($user, Response::HTTP_CREATED);
    }

    public function update( $id, Request $request )
    {
        $user = $this->model->find($id) 
                            ->update($request->all());
        
        if($user == true){
            $user['message'] = 'success';
        }

        return response()->json($user, Response::HTTP_OK);
    }

    public function delete( $id )
    {
        $user = $this->model->find($id) 
                            ->delete();
                            
        return response()->json( ['message' => 'Success'], Response::HTTP_OK);
    }
    
}
