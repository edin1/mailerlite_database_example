<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Mailerlite DataTables example</title>

        <!-- Remember to include jQuery :) -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.0.0/jquery.min.js"></script>

        <!-- DataTables -->
        <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />

    </head>
    <body>
        <div>
            <div id="my-content">
                <a id="my-logout" style="visibility: hidden;" href="/user/logout">Logout</a>
                <br/>
                @if (session()->has('error'))
                    <div class="alert alert-error">
                        {{ session('error') }}
                    </div>
                @endif
                @if (session()->has('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                <div id="api-key-form" style="visibility: hidden;">
                    {{ Form::open(array('url' => '/user/api_key')) }}
                    {{ Form::label('api_key', 'Please provide a mailerlite API key') }}
                    {{ Form::text('api_key') }}
                    {{ Form::submit('Submit') }}
                    {{ Form::close() }}
                </div>
                <div id="my-data-grid" style="visibility: hidden; max-width: 50%;">
                    <h3>Subscribers data grid</h3>
                    <br/>
                    {{ Form::open(array('url' => '/', 'method' => 'get')) }}
                    {{ Form::label('search_email', 'Search by email') }}
                    {{ Form::text('search_email', Request::get('search_email') ? Request::get('search_email') : null)}}
                    {{ Form::submit('Search') }}
                    {{ Form::close() }}
                    <br/>
                    <a href="/user/create">Add a new subscriber</a>
                    <br/>
                    <br/>
                    <table id="my-table" class="display">
                        <thead>
                        <tr>
                            <th>Email</th>
                            <th>Name</th>
                            <th>Country</th>
                            <th>Subscribe date</th>
                            <th>Subscribe time</th>
                            <th>Delete</th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
            <script>
                $(document).ready( function () {
                    $.ajax({url: "{{ url('/user') }}", success: function(result) {
                        if (!result['is_api_key_present']) {
                            console.log("API key was not provided!")
                            $("#api-key-form").css('visibility','visible');
                        } else {
                            console.log(result)
                            $("#my-logout").css('visibility','visible');
                            $("#my-data-grid").css('visibility','visible');
                            let table = $('#my-table').DataTable({
                                processing: true,
                                serverSide: true,
                                paging: true,
                                ajax: 'user/datatables',
                                columnDefs: [
                                    {
                                        targets: -1,
                                        data: null,
                                        defaultContent: '<button>Delete!</button>',
                                    },
                                    {
                                        targets: 0,
                                        data: null,
                                        defaultContent: '<button>Delete!</button>',
                                        render: function (data, type, row, meta)
                                        {
                                            console.log(type)
                                            if (type === 'display')
                                            {
                                                data = '<a href="/user/create?email=' + encodeURIComponent(data[0]) + '">' + data[0] + '</a>';
                                            }
                                            return data;
                                        }
                                    },
                                ],
                            });
                            $('#my-table tbody').on('click', 'button', function () {
                                var data = table.row($(this).parents('tr')).data();
                                $.get( 'user/delete?email=' + data[0], function(data) {
                                    $('.result').html(data);
                                    $('#my-table').DataTable().ajax.reload();
                                });
                            });
                            $("#my-table_filter").css('visibility','hidden');
                        }
                    }});
                } );
            </script>
        </div>
    </body>
</html>
