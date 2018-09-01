<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\document;
use App\companie;
use Mail;
Use Session;
use Redirect;
use File;
use ZipArchive;


class DocumentController extends Controller
{
    public function index(){
    	 return view('system.documents.load');
    }



    public $user_mail;
    public function store(Request $request){
    	
    	
    	$now = new \DateTime();
 		$date_Actual =$now->format('Y-m-d');
    	$user_id=Auth::user()->id;
    	$this->user_mail=Auth::user()->email;

    	$file=$request ->file('document');
    	$name=$request ->input('name');
     	$path=public_path() . '/images/documents';
     	$fileName=uniqid().$file->getClientOriginalName();
     	$moved=$file->move($path,$fileName);

	      if($moved){
	           $fileFac=new document();
	           $fileFac->name=$name;
	           $fileFac->date=$date_Actual;
	           $fileFac->document=$fileName;
	           //$productImage->featured=;
	           $fileFac->user_id=$user_id;
	           $fileFac->save();
	           $fileName='images/documents/'.$fileName;
	         try {
                Mail::send('system.documents.send',['filename' => $fileName, 'name'=> $name],function($msj){
                    $msj->subject('Se cargo un nuevo archivo');
                    $msj->to($this->user_mail);
                    $msj->cc('javeliecm@gmail.com');
                });
                 
             } catch (Exception $e) {
                 return response()->json(['success'=>'Datos Cargados Correctamente, pero el correo no se envio']);
             }
	           
	      }

          return response()->json(['success'=>'Datos Cargados Correctamente, desea cargar mas?']);

	   // return redirect('system');
    }




     public function destroy($id){
        $notification="No fue posible eliminar el  documento, no existe o esta siendo utilizado :(";
        $documentf=document::find($id);
   
        $fullPath=public_path() . '/images/documents/'.$documentf->document;
        $deleted=File::delete($fullPath); 
        

        if($deleted){
               $documentf->delete();
                $notification="Documento eliminado :)";
          }
         return back()->with(compact("notification"));
     }


     public function zip(Request $request){

       $initial=\Carbon\Carbon::parse($request->fechainicial)->format('Y-m-d');
        $finald=\Carbon\Carbon::parse($request->fechafinal)->format('Y-m-d');

        if($request->has('download')) {
            $user_id=Auth::user()->id;
       
            
            $documents= document::where('date',">=",$initial)  
                                ->where('date',"<=",$finald)  
                                ->get();
            

            // Define Dir Folder
            $public_dir=public_path();
            // Zip File Name
            $zipFileName = 'All.zip';
            $fullPath=public_path() . '/downloads/facturas/'.$zipFileName ;
            $deleted=File::delete($fullPath); 

            // Create ZipArchive Obj
            $zip = new ZipArchive;

            if ($zip->open($public_dir . '/downloads/facturas/' . $zipFileName, ZipArchive::CREATE) === TRUE) {    
                // Add Multiple file   

                $cont=0;
                foreach($documents as $document) {
                    $zip->addFile($public_dir . '/images/documents/'.$document->document, $document->document);
                    $cont++;
                }        

                $zip->close();

            }

            // Set Header
            $headers = array(
                'Content-Type' => 'application/octet-stream',
            );
            $filetopath=$public_dir.'/downloads/facturas/'.$zipFileName;
            // Create Download Response
            if(file_exists($filetopath)){
                return response()->download($filetopath,$zipFileName,$headers);
            }
        }
        return back();
     }




    public function show($companie){

      $companieId=companie::where("name_short",$companie)->first();
      
      $user_id=Auth::user()->id;
      $role=Auth::user()->role_id;

      if(is_null($companieId) || empty($companieId)){
          abort(404);
        }

        $name_companie=$companieId->name;

       if($role==1){
            $documents= document::where("companie_id",$companieId->id)->paginate(10);
        }else{
            $documents= document::where('user_id',$user_id)->paginate(10);
        }

        

        return view('system.companies.documents')->with(compact('documents','role','name_companie'));
    }

}
