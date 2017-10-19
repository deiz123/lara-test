<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Validator;

class FileController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a list of all files in xlsx storage.
     *
     * @return Response
     */
    public function getFiles()
    {
        $f = Storage::disk('xlsx');
        $files = $f->allFiles();
        
        return view('files.index',['files' => $files]);
    }

    /**
     * Upload file on server in xlsx storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function upload(Request $request)
    {
        $rules = [];
        $messages = [];
        foreach(range(0, count($request->file()) - 1) as $i) {
            $rules['file.' . $i] = 'required|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet|max:20000';
            $messages['file.' . $i.'.mimetypes'] = 'Only xlsx files are allowed';
        }

        $this->validate($request, $rules, $messages);

        foreach ($request->file() as $file) {
            foreach ($file as $f) {
                $f->move(storage_path('xlsx'), $f->getClientOriginalName());
            }
        }

        return redirect('/');
    }


    /**
     * Deilte file from server in xlsx storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function delete(Request $request)
    {
        $f = Storage::disk('xlsx');
        $f->delete($request->filename);

        return redirect('/');
    }
}
