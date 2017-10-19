@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="col-sm-4">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title pull-left">Список xlsx файлов</h3>
                    <button class="btn btn-default pull-right" data-toggle="modal" data-target="#uploadFile">
                        <i class="fa fa-btn fa-plus"></i>Загрузить файл
                    </button>
                    <div class="clearfix"></div>
                </div>

                <div class="panel-body">
                    <table class="table table-hover xlsx-table">
                        @if($files)
                            <thead>
                                <th>Название</th>
                                <th colspan='2'>Действия</th>
                            </thead>
                            <tbody>
                                @foreach ($files as $file)
                                    <tr>
                                        <td>{{ $file }}</td>
                                        <td>
                                            <button value="{{ $file }}" class="btn btn-success btn-view">
                                                    <i class="fa fa-btn fa-eye"></i>Просмотр
                                            </button>
                                        </td>
                                        <td>
                                            <form action="{{ route('file_delete',['filename' => $file]) }}" method="POST">
                                                {{ csrf_field() }}
                                                {{ method_field('DELETE') }}

                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fa fa-btn fa-trash"></i>Удалить
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        @else
                            <tr>
                                <td colspan="3">Файлов нет</td>
                            </tr>
                        @endif
                            <tfoot>
                                <tr>
                                    <td colspan="3">
                                        <div class="dropzone" id="dropzoneFileUpload"></div>
                                    </td>
                                </tr>
                            </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title pull-left">Просмотр</h3>
                    <button class="btn btn-default pull-right" id="previewHide">
                        Отчистить
                    </button>
                    <div class="clearfix"></div>
                </div>

                <div class="panel-body">
                    <div id="ajax-data"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="uploadFile" tabindex="-1" role="dialog" aria-labelledby="addArticleLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="addArticleLabel">Загрузка файла</h4>
                </div>

                <div class="modal-body">
                    <form method="post" action="{{ route('file_upload') }}" enctype="multipart/form-data">
                        <input name="_token" type="hidden" value="{{ csrf_token() }}">
                        <div class="form-group">
                            <input type="file" id="file" name="file[]" multiple>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-btn fa-download"></i>Загрузить
                            </button>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
                </div>

            </div>
        </div>
    </div>
@endsection
