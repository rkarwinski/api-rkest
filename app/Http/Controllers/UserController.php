<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request; 
use App\Models\User;
use Symfony\Component\HttpFoundation\Response; 
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

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

        try {
            $users = $this->model->all();

            if(count($users) > 0){
                $users['total'] = count($users);
                return response()->json($users, Response::HTTP_OK);
            }
    
            return response()->json(['total' => 0], Response::HTTP_OK);    
        } catch (QueryException $exception) {
            return response()->json(['error' => 'Erro interno do Servidor'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
       
    }

    public function get( $id )
    {
        try {

            $user = $this->model->find($id);

            if( isset($user) ){
                return response()->json($user, Response::HTTP_OK);
            }
    
            return response()->json(['message' => 'ID não encontrado'], Response::HTTP_OK);
        } catch (QueryException $exception) {
            return response()->json(['error' => 'Erro interno do Servidor'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
    }

    public function create(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            [
                'email' => 'required | max:100',
                'nome' => 'required | max:100',
                'senha' => 'required | max:20 | min:8',
                'data_criado' => 'required | date_format: "Y-m-d H:i:s"'
            ]
        );

        try {

            $data = $request->all();
            $data['senha'] = Hash::make($data['senha']); 

            $user = $this->model->create($data);

            return response()->json($user, Response::HTTP_CREATED);
        } catch (QueryException $exception) {
            return response()->json(['error' => 'Erro interno do Servidor'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update( $id, Request $request )
    {

        $validator = Validator::make(
            $request->all(),
            [
                'nome' => 'required | max:100',
                'senha' => 'required | max:20 | min:8',
                'data_criado' => 'required | date_format: "Y-m-d H:i:s"'
            ]
        );

        try {

            $data = $request->all();
            $data['senha'] = Hash::make($data['senha']); 

            if(isset($data['email'])){
                unset($data['email']);
            }

            $user = $this->model->find($id) 
                                ->update($data);

            $msg = ['message' => 'success'];
            if($user == 0){
                $msg['message'] = 'not saved';
            }

            return response()->json($msg, Response::HTTP_OK);  
        } catch (QueryException $exception) {
            return response()->json(['error' => true, 'messega' => 'Erro interno do Servidor'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
        
    }

    public function delete( $id )
    {

        try {
            $user = $this->model->find($id) 
                                ->delete();
       
            return response()->json( ['message' => 'Success'], Response::HTTP_OK);  
        } catch (QueryException $exception) {
            return response()->json(['error' => 'Erro interno do Servidor'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
    }
    
}
