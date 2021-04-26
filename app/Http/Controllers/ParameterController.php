<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request; 
use App\Models\Parameter;
use Symfony\Component\HttpFoundation\Response; 
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class ParameterController extends Controller
{

    private $model; 

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Parameter $mParameter)
    {
        $this->model = $mParameter;
    }

    public function getAll()
    {

        try {
            $parameters = $this->model->all();

            if(count($parameters) > 0){
                $parameters['total'] = count($parameters);
                return response()->json($parameters, Response::HTTP_OK);
            }
    
            return response()->json(['total' => 0], Response::HTTP_OK);    
        } catch (QueryException $exception) {
            return response()->json(['error' => 'Erro interno do Servidor'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
       
    }

    public function get( $id )
    {
        try {

            $parameter = $this->model->find($id);

            if( isset($parameter) ){
                return response()->json($parameter, Response::HTTP_OK);
            }
    
            return response()->json(['message' => 'ID não encontrado'], Response::HTTP_OK);
        } catch (QueryException $exception) {
            return response()->json(['error' => 'Erro interno do Servidor'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
    }

    public function create(Request $request)
    {
        
        //if($request->getMethod() === 'OPTIONS')  {
        //    app()->options($request->path(), function () {
        //        return response('OK',200)
        //            ->header('Access-Control-Allow-Origin', '*')
        //            ->header('Access-Control-Allow-Methods','OPTIONS, GET, POST, PUT, DELETE')
        //            ->header('Access-Control-Allow-Headers', 'Content-Type, Origin');                    
        //    });
        //}

        $validator = Validator::make(
            $request->all(),
            [
                'nome' => 'required | max:220',
                'valor' => 'required | max:220'
            ]
        );

        try {

            $data = $request->all();

            $parameter = $this->model->create($data);

            return response()->json($parameter, Response::HTTP_CREATED);
        } catch (QueryException $exception) {
            return response()->json(['error' => 'Erro interno do Servidor'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update( $id, Request $request )
    {

        $validator = Validator::make(
            $request->all(),
            [
                'nome' => 'required | max:220',
                'valor' => 'required | max:220'
            ]
        );

        try {

            $data = $request->all();
            $parameter = $this->model->find($id) 
                                ->update($data);

            $msg = ['message' => 'success'];
            if($parameter == 0){
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
            $parameter = $this->model->find($id) 
                                ->delete();
       
            return response()->json( ['message' => 'Success'], Response::HTTP_OK);  
        } catch (QueryException $exception) {
            return response()->json(['error' => 'Erro interno do Servidor'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
    }
    
}
