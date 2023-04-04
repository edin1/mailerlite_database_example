<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use MailerLite\Exceptions\MailerLiteHttpException;
use MailerLite\Exceptions\MailerLiteValidationException;
use MailerLite\MailerLite;
use Illuminate\Support\Facades;

class UserController extends Controller
{
    public function welcome(Request $request)
    {
        if ($request->session()->has('api_key')) {
            $searchEmail = $request->get('search_email', null);
            Log::debug('Ahoy query ' . var_export($request->query(), true));
            if ($searchEmail !== null and trim($searchEmail) !== '') {
                if (filter_var($searchEmail, FILTER_VALIDATE_EMAIL)) {
                    Log::debug('Ahoy ' . var_export($request->query(), true));
                    $request->session()->put('search_email', $searchEmail);
                } else {
                    $request->session()->flash('error', 'The email you searched for is not valid.');
                }
            }
        }

        return view('welcome');
    }

    public function index(Request $request)
    {
        $apiKey = $request->session()->get('api_key');

        if ($apiKey === null) {
            return [
                'is_api_key_present' => false
            ];
        } else {
            return [
                'is_api_key_present' => true
            ];
        }
    }

    public function logout(Request $request)
    {
        $request->session()->forget('api_key');
        return redirect()->action('App\Http\Controllers\UserController@welcome');
    }

    public function apiKey(Request $request)
    {
        $this->setApiKey($request, $request->post('api_key', null));
        return redirect()->action('App\Http\Controllers\UserController@welcome');
    }

    public function apiDatatables(Request $request)
    {
        if ($request->session()->has('datatables_draw')) {
            $request->session()->put('datatables_draw', $request->session()->get('datatables_draw') + 1);
        } else {
            $request->session()->put('datatables_draw', 1);
        }

        $draw = $request->session()->get('datatables_draw');

        if ($request->session()->has('search_email')) {
            $searchEmail = $request->session()->get('search_email');
            $request->session()->forget('search_email');
            try {
                $response = $this->getMailerLite($request)->subscribers->find($searchEmail);
            } catch (MailerLiteHttpException $e) {
                return [
                    "draw" => $draw,
                    "recordsTotal" => 0,
                    "recordsFiltered" => 0,
                    "data" => [],
                ];
            } // else
            Log::debug('Ahoy ' . var_export($response, true));
            $total = 1;
            $rowsInPage = [$response['body']['data']];
        } else {
            Log::debug('Ahoy query ' . var_export($request->query(), true));
            if ($request->get('length', null) !== null) {
                $length = intval($request->get('length'));
            } else {
                $length = 10;
            }
            if ($request->get('start', null) !== null) {
                $start = intval($request->get('start'));
            } else {
                $start = 0;
            }

            $page = $start / $length;
            $response = $this->getMailerLite($request)->subscribers->get(['limit' => $length]);
            Log::debug('Ahoy response ' . var_export($response, true));
            $i = 0;
            while ($i < $page) {
                $i += 1;
                $response = $this->getMailerLite($request)->subscribers->get(['limit' => $length, 'cursor' => $response['body']['meta']['next_cursor']]);
            }
//        Log::debug('Ahoy ' . var_export(array_keys($request->query()), true));
//        Log::debug('Ahoy ' . var_export($request->get('search', null), true));
            $total = $this->getMailerLite($request)->subscribers->get(['limit' => 0])['body']['total'];
//        Log::debug('Ahoy ' . var_export($total, true));
            //        Log::debug('Ahoy ' . var_export(array_keys($rowsInPage), true));
            $rowsInPage = $response['body']['data'];
            Log::debug('Ahoy ' . var_export($rowsInPage, true));
            Log::debug('Ahoy ' . var_export($this->getMailerLite($request)->subscribers->get(['limit' => 2, 'cursor' => 2]), true));
        }

        $data = [];
        foreach ($rowsInPage as $row) {
//            Log::debug(var_export($row, true));
//            Log::debug(var_export($row['subscribed_at'], true));
            $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $row['subscribed_at']);
            $data[] = [
                $row['email'],
                $row['fields']['name'],
                $row['fields']['country'],
                $dt->format('d/m/Y'),
                $dt->format('H:i:s'),
            ];
        }

//        $this->mailerLite->subscribers->get();
//        $this->mailerLite->subscribers->find();
        return [
            "draw" => $draw,
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $data,
        ];
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        if (\request()->get('email')) {
            $response = $this->getMailerLite(\request())->subscribers->find(\request()->get('email'));
            $params = [
                'email' => \request()->get('email'),
                'name' => $response['body']['data']['fields']['name'],
                'country' => $response['body']['data']['fields']['country'],
            ];
        } else {
            $params = [
                'email' => '',
                'name' => '',
                'country' => '',

            ];
        }
        return view('create', $params);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
//        $validated = $request->validate([
//            // 'subscriber_email' => 'required|email:rfc,dns',
//            'subscriber_email' => 'required|email',
//            'subscriber_name' => 'required',
//            'subscriber_country' => 'required',
//        ]);

        //setup Validator and passing request data and rules
        $validator = Facades\Validator::make($request->all(), [
            // 'subscriber_email' => 'required|email:rfc,dns',
            'subscriber_email' => 'required',
            'subscriber_name' => 'required',
            'subscriber_country' => 'required',
        ]);

        try {
            $response = $this->getMailerLite($request)->subscribers->create([
                'email'=> $request->get('subscriber_email'),
                'fields' => [
                    'name'=> $request->get('subscriber_name'),
                    'country'=> $request->get('subscriber_country'),
                ],
            ]);
            $mailerliteHadErrors = false;
            // Log::debug('Mailerlite Response ' . var_export($response, true));
        } catch (MailerLiteValidationException $e) {
            //hook to add additional rules by calling the ->after method
            $validator->after(function ($validator) use ($e) {
                $validator->errors()->add('mailerlite', 'There was an error returned by mailerlite: ' . $e->getMessage());
            });

            $mailerliteHadErrors = true;
        }

        if (!$mailerliteHadErrors) {
            if ($response['status_code'] === 201) {
                $request->session()->flash('success', 'Successfully added a new subscriber');
            } elseif ($response['status_code'] === 200) {
//            //hook to add additional rules by calling the ->after method
//            $validator->after(function ($validator) {
//                $validator->errors()->add('mailerlite', 'A user with the specified email is already subscribed.');
//            });

                $request->session()->flash('success', 'Successfully updated a current subscriber.');
            } else {
                //hook to add additional rules by calling the ->after method
                $validator->after(function ($validator) {
                    $validator->errors()->add('mailerlite', 'There was an error returned by mailerlite');
                });
            }
        }

        $validated = $validator->validate();

        Log::debug('Validated ' . var_export($validated, true));
        return redirect()->action('App\Http\Controllers\UserController@welcome');
    }

    /**
     * Delete the specified resource.
     *
     * @return Response
     */
    public function delete()
    {
        $response = $this->getMailerLite(\request())->subscribers->find(\request()->get('email'));
        $response = $this->getMailerLite(\request())->subscribers->delete($response['body']['data']['id']);
        // Log::debug('Delete ' . var_export($response, true));

        return new Response(\request()->get('email'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    private function setApiKey(Request $request, ?string $apiKey)
    {
        if ($apiKey !== null) {
            $apiKey = trim($apiKey);
        }

        if ($apiKey === '') {
            $apiKey = null;
        }

        if ($apiKey === null) {
            $request->session()->flash('error', 'The provided API key was null.');
            return;
        }

        $mailerLite = new MailerLite(['api_key' => $apiKey]);

        try {
            $response = $mailerLite->timezones->get();
        } catch (MailerLiteHttpException $e) {
            $request->session()->flash('error', 'The provided API key not valid, please provide a valid key.');
            return;
        }

        $request->session()->put('api_key', $apiKey);
    }

    /**
     * @param Request $request
     * @return MailerLite
     */
    public function getMailerLite(Request $request)
    {
        return new MailerLite(['api_key' => $request->session()->get('api_key')]);
    }
}
