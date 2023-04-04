<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Mailerlite DataTables example</title>

        <!-- Remember to include jQuery :) -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.0.0/jquery.min.js"></script>

        <!-- jQuery Modal -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.css" />

        <!-- DataTables -->
        <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />

    </head>
    <body>
        <div>
            <div id="my-content">
                @if (session()->has('error'))
                    <div class="alert alert-error">
                        {{ session('error') }}
                    </div>
                @endif
                <div id="api-key-form" style="visibility: hidden;">
                    {{ Form::open(array('url' => '/user/api_key')) }}
                    {{ Form::label('api_key', 'Please provide a mailerlite API key') }}
                    {{ Form::text('api_key') }}
                    {{ Form::submit('Submit') }}
                    {{ Form::close() }}
                </div>
                <div id="my-create" style="visibility: hidden; max-width: 50%;">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <br/>
                    @if (request()->has('email'))
                        Edit existing subscriber
                    @else
                        Add a new subscriber
                    @endif
                    <br/>
                    {{ Form::open(array('url' => '/user/store', 'method' => 'post')) }}
                    {{ Form::label('subscriber_email', 'Email') }}
                    @if (request()->has('email'))
                        {{ Form::text('subscriber_email', $email, ['readOnly'=>'true'])}}<br/>
                    @else
                        {{ Form::text('subscriber_email', $email)}}<br/>
                    @endif
                    {{ Form::label('subscriber_name', 'Name') }}
                    {{ Form::text('subscriber_name', $name)}}<br/>
                    {{ Form::label('subscriber_country', 'Country') }}
                    {{ Form::text('subscriber_country', $country)}}<br/>
                    {{ Form::submit('Submit') }}
                    {{ Form::close() }}
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
                            $("#my-create").css('visibility','visible');
                        }
                    }});
                } );
            </script>
        </div>
    </body>
</html>
