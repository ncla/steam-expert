@extends('layouts.app')

@section('htmlheader')
    @include('layouts.partials.htmlheader')
    <script src="{{ secure_asset('/plugins/jQuery/jQuery-2.1.4.min.js') }}"></script>
@show

@section('contentheader_title')
    {{ $table_title }}
@endsection

@section('main-content')
    <script>
        $('.treeview-menu.{{ $type }}').show();
    </script>
    <div class="box">
        <div class="box-header"></div>
        <div class="box-body">
            <div class="col-sm-12">
                <table id="data-table" class="table table-bordered table-hover dataTable" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        @foreach($cols as $col)
                            <th>{{ trim($col) }}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($rows as $row)
                        <tr>
                            @foreach($row as $stat)
                                <td>
                                    {{ trim($stat) }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        $(window).load(function () {
            $('#data-table').DataTable({
                "iDisplayLength": ('{{ $table_title }}').indexOf('url_encode') !== -1 ? 10 : -1,
                "scrollX": ('{{ $table_title }}').indexOf('url_encode') !== -1
            });
        });
    </script>
@endsection
