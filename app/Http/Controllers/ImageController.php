<?php

namespace App\Http\Controllers;

use App\Services\Upload;
use Illuminate\Http\Request;

/**
 * Upload image and recognizing of shapes
 */
class ImageController extends Controller
{

    public function form(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application
    {
        return view('form');
    }

    public function upload(Request $request)
    {

        try{
            $rules = [
                'picture' => 'file'
            ];

            $error = '';
            $figure = '';

            $filename = Upload::uploadImage($request);

            if( $filename === null ){
                $error = 'Error: your file has not uploaded' ;
            } else{
                $figure = Upload::recognizeImage($filename);

                if( $figure === null ){
                    $error = 'Error: your file has not recognized' ;
                }
            }

        } catch(\Exception $e){
            $error = "Un error: " . $e->getMessage();
        }

        $data = [
            'error'     => $error,
            'figure'    => $figure,
            'filename'  => $filename
        ];

        return view('recognize',$data);

    }

}
