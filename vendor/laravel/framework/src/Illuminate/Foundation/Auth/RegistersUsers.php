<?php

namespace Illuminate\Foundation\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ProfileRequest;
use App\Model\User\AccountActivate;
use App\User;
// use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;

use Validator;

trait RegistersUsers
{
    use RedirectsUsers;

    /**
     * Show the application registration form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showRegistrationForm()
    {
        // return view('auth.register');
    }

    /**
     * Handle a registration request for the application.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function postRegister( ProfileRequest $request,User $user, AccountActivate $activate)
    {
          try {
            $pass = $request->input('password');
            $country = $request->input('country');
            $currency = 'INR';
           if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
                {
                  $ip=$_SERVER['HTTP_CLIENT_IP'];
                }
                elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
                {
                  $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
                }
                else
                {
                  $ip=$_SERVER['REMOTE_ADDR'];
                }
               
               if($ip!='::1')
               {$location = json_decode(file_get_contents('http://ip-api.com/json/'.$ip),true);}
               else
                {$location = json_decode(file_get_contents('http://ip-api.com/json'),true);}

            $country = \App\Http\Controllers\Front\CartController::findCountryByGeoip($location['countryCode']);
            $states = \App\Http\Controllers\Front\CartController::findStateByRegionId($location['countryCode']);
            $states = \App\Model\Common\State::pluck('state_subdivision_name', 'state_subdivision_code')->toArray();
            $state_code = $location['countryCode'] . "-" . $location['region'];
            $state = \App\Http\Controllers\Front\CartController::getStateByCode($state_code);
            $mobile_code = \App\Http\Controllers\Front\CartController::getMobileCodeByIso($location['countryCode']);
                        // $location = \GeoIP::getLocation($ip);
                        if ($country == 'IN') {
                            $currency = 'INR';
                        } else {
                            $currency = 'USD';
            }
            if (\Session::has('currency')) {
                $currency = \Session::get('currency');
            }
            $manager=$this->accountManager();
          
            $account_manager =$manager; 
     
            $password = \Hash::make($pass);
            $user->password = $password;
            // $user->town="";
            $user->town=$location['city'];
            $user->profile_pic="";
            $user->active=0;
            $user->debit=0;
            $user->mobile_verified=0;
            
            $user->role = 'user';

            $user->manager = $account_manager;
            $user->ip = $location['query'];
            $user->currency = $currency;
            $user->timezone_id = \App\Http\Controllers\Front\CartController::getTimezoneByName($location['timezone']);
            $user->fill($request->except('password'))->save();
            //$this->sendActivation($user->email, $request->method(), $pass);
            $this->accountManagerMail($user);
            if ($user) {
                $response = ['type' => 'success', 'user_id' => $user->id, 'message' => 'Your Submission has been received successfully. Verify your Email and Mobile to log into the Website.'];

                return response()->json($response);
            }
        } catch (\Exception $ex) {
            dd($ex);
            //return redirect()->back()->with('fails', $ex->getMessage());
            $result = [$ex->getMessage()];
            
            return response()->json($result);
        }


       // return $request->all();

       
    }
     public function sendActivationByGet($email, Request $request)
    {
        try {
            
            $mail = $this->sendActivation($email, $request->method());
            if ($mail == 'success') {
                return redirect()->back()->with('success', 'Activation link has sent to your email address');
            }
        } catch (\Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    public function sendActivation($email, $method, $str = '')
    {

        
        try {
            $user = new User();
            $activate_model = new AccountActivate();
            $user = $user->where('email', $email)->first();
            if (!$user) {
                return redirect()->back()->with('fails', 'Invalid Email');
            }
            
            if ($method == 'GET') {
                $activate_model = $activate_model->where('email', $email)->first();
                $token = $activate_model->token;
            } else {
                $token = str_random(40);
                $activate = $activate_model->create(['email' => $email, 'token' => $token]);
                $token = $activate->token;
            }
            $url = url("activate/$token");
            //check in the settings
            $settings = new \App\Model\Common\Setting();
            $settings = $settings->where('id', 1)->first();
            //template
            $template = new \App\Model\Common\Template();
            $temp_id = $settings->where('id', 1)->first()->welcome_mail;
            $template = $template->where('id', $temp_id)->first();
            $from = $settings->email;
            $to = $user->email;
            $subject = $template->name;
            $data = $template->data;
            $replace = ['name' => $user->first_name.' '.$user->last_name, 'username' => $user->email, 'password' => $str, 'url' => $url];
            $type = '';
            if ($template) {
                $type_id = $template->type;
                $temp_type = new \App\Model\Common\TemplateType();
                $type = $temp_type->where('id', $type_id)->first()->name;
            }
            //dd($type);
            $templateController = new \App\Http\Controllers\Common\TemplateController();
            $mail = $templateController->mailing($from, $to, $data, $subject, $replace, $type);

            return $mail;
        } catch (\Exception $ex) {
            dd($e);
            throw new \Exception($ex->getMessage());
        }
    }

    public function Activate($token, AccountActivate $activate, Request $request, User $user)
    {
        try {
            if ($activate->where('token', $token)->first()) {
                $email = $activate->where('token', $token)->first()->email;
            } else {
                throw new NotFoundHttpException();
            }
            
            $url = 'auth/login';
            $user = $user->where('email', $email)->first();
            if ($user->where('email', $email)->first()) {
                $user->active = 1;
                $user->save();
                $mailchimp = new \App\Http\Controllers\Common\MailChimpController();
                $r = $mailchimp->addSubscriber($user->email);
                if (\Session::has('session-url')) {
                    $url = \Session::get('session-url');

                    return redirect($url);
                }

                return redirect($url)->with('success', 'Email verification successful.. Please login to access your account');
            } else {
                throw new NotFoundHttpException();
            }
        } catch (\Exception $ex) {
            if ($ex->getCode() == 400) {
                return redirect($url)->with('success', 'Email verification successful, Please login to access your account');

                return redirect($url);
            }

            return redirect($url)->with('fails', $ex->getMessage());
        }
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validator(array $data)
    {
        return Validator::make($data, [
                    'name'     => 'required|max:255',
                    'email'    => 'required|email|max:255|unique:users',
                    'password' => 'required|confirmed|min:6',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     *
     * @return User
     */
    public function create(array $data)
    {
        return User::create([
                    'name'     => $data['name'],
                    'email'    => $data['email'],
                    'password' => bcrypt($data['password']),
        ]);
    }

    /**
     * Get the post register / login redirect path.
     *
     * @return string
     */
    public function redirectPath()
    {  


        if (\Session::has('session-url')) {
            $url = \Session::get('session-url');

            return property_exists($this, 'redirectTo') ? $this->redirectTo : '/'.$url;
        } else {
            return property_exists($this, 'redirectTo') ? $this->redirectTo : '/home';
        }
    }

    public function sendOtp($mobile, $code)
    {
        $client = new \GuzzleHttp\Client();
        $number = $code.$mobile;
        $response = $client->request('GET', 'https://control.msg91.com/api/sendotp.php', [
            'query' => ['authkey' => '54870AO9t5ZB1IEY5913f8e2', 'mobile' => $number],
        ]);
        $send = $response->getBody()->getContents();
        $array = json_decode($send, true);
        if ($array['type'] == 'error') {
            throw new \Exception($array['message']);
        }

        return $array['type'];
    }

    public function sendForReOtp($mobile, $code)
    {
        $client = new \GuzzleHttp\Client();
        $number = $code.$mobile;
        $response = $client->request('GET', 'https://control.msg91.com/api/retryotp.php', [
            'query' => ['authkey' => '54870AO9t5ZB1IEY5913f8e2', 'mobile' => $number],
        ]);
        $send = $response->getBody()->getContents();
        $array = json_decode($send, true);
        if ($array['type'] == 'error') {
            throw new \Exception($array['message']);
        }

        return $array['type'];
    }

    public function requestOtp(Request $request)
    {
        $this->validate($request, [
            'code'   => 'required|numeric',
            'mobile' => 'required|numeric',
        ]);

        try {
            $code = $request->input('code');
            $mobile = $request->input('mobile');
            $number = $code.$mobile;
            $result = $this->sendOtp($mobile, $code);
            $response = ['type' => 'success', 'message' => 'OTP has been sent to '.$number];

            return response()->json($response);
        } catch (\Exception $ex) {
            $result = [$ex->getMessage()];

            return response()->json(compact('result'), 500);
        }
    }

    // public function requestOtpFromAjax(Request $request)
    // {
    //     $this->validate($request, [
    //         'email'  => 'required|email',
    //         'code'   => 'required|numeric',
    //         'mobile' => 'required|numeric',
    //     ]);

    //     try {
    //         $code = $request->input('code');
    //         $mobile = $request->input('mobile');
    //         $userid = $request->input('id');
    //         $email = $request->input('email');
    //         $pass = $request->input('password');
    //         $number = $code.$mobile;
    //         $result = $this->sendOtp($mobile, $code);
    //         $method = 'POST';
    //         $this->sendActivation($email, $method, $pass);
    //         $response = ['type' => 'success', 'message' => 'Activation link has been sent to '.$email.'<br>OTP has been sent to '.$number];

    //         return response()->json($response);
    //     } catch (\Exception $ex) {
    //         $result = [$ex->getMessage()];

    //         return response()->json(compact('result'), 500);
    //     }
    // }

    // public function retryOTP($request)
    // {
    //     $this->validate($request, [
    //         'code'   => 'required|numeric',
    //         'mobile' => 'required|numeric',
    //     ]);

    //     try {
    //         $code = $request->input('code');
    //         $mobile = $request->input('mobile');
    //         $number = $code.$mobile;
    //         $result = $this->sendForReOtp($mobile, $code);
    //         $response = ['type' => 'success', 'message' => 'OTP has been sent to '.$number.' via voice call..'];

    //         return response()->json($response);
    //     } catch (\Exception $ex) {
    //         $result = [$ex->getMessage()];

    //         return response()->json(compact('result'), 500);
    //     }
    // }

    // public function verifyOtp($mobile, $code, $otp)
    // {
    //     $client = new \GuzzleHttp\Client();
    //     $number = $code.$mobile;
    //     $response = $client->request('GET', 'https://control.msg91.com/api/verifyRequestOTP.php', [
    //         'query' => ['authkey' => '54870AO9t5ZB1IEY5913f8e2', 'mobile' => $number, 'otp' => $otp],
    //     ]);

    //     return $response->getBody()->getContents();
    // }

    public function postOtp(Request $request)
    {
        $this->validate($request, [
            'otp' => 'required|numeric',
        ]);

        try {
            $code = $request->input('code');
            $mobile = $request->input('mobile');
            $otp = $request->input('otp');
            $userid = $request->input('id');
            $verify = $this->verifyOtp($mobile, $code, $otp);
            $array = json_decode($verify, true);
            if ($array['type'] == 'error') {
                throw new \Exception($array['message']);
            }

            $user = User::find($userid);
            if ($user) {
                $user->mobile = $mobile;
                $user->mobile_code = $code;
                $user->mobile_verified = 1;
                $user->save();
            }
            $check = $this->checkVerify($user);
            $response = ['type' => 'success', 'proceed' => $check, 'user_id' => $userid, 'message' => 'Mobile verified..'];

            return response()->json($response);
            // return redirect('/login');
        } catch (\Exception $ex) {
            $result = [$ex->getMessage()];
            if ($ex->getMessage() == 'otp_not_verified') {
                $result = ['OTP Not Verified!'];
            }

            return response()->json(compact('result'), 500);
        }
    }

    public function verifyEmail(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
        ]);

        try {
            $email = $request->input('email');
            $userid = $request->input('id');
            $user = User::find($userid);
            $check = $this->checkVerify($user);
            $method = 'POST';
            //$this->sendActivation($email, $request->method());
            $this->sendActivation($email, $method);
            $response = ['type' => 'success', 'proceed' => $check, 'email' => $email, 'message' => 'Activation link has been sent to '.$email];

            return response()->json($response);

        } catch (\Exception $ex) {
            //dd($ex);
            $result = [$ex->getMessage()];

            return response()->json(compact('result'), 500);
        }
    }

    public function checkVerify($user)
    {
        $check = false;
        if ($user->active == '1' && $user->mobile_verified == '1') {
            \Auth::login($user);
            $check = true;
        }

        return $check;
    }

    public function accountManager()
    {
        
        // $users = new User();
        // $account_count = $users->select(\DB::raw("count('manager') as count"), 'manager')
        //         ->whereNotNull('manager')
        //         ->groupBy('manager')
        //         ->pluck('count', 'manager')
        //         ->toArray();
              
        // if ($account_count) {
        //     $manager = array_keys($account_count, min($account_count))[0];
        // }


        
        $managers = User::where('role', 'admin')->where('position', 'manager')->pluck('id','first_name')->toArray();


         if(count($managers)>0){


            $randomized[] = array_rand($managers);
        shuffle($randomized);
        $manager = $managers[$randomized[0]];
        }
        
        else{
            $manager = '';
        }
        
        return $manager;
    }

    public function accountManagerMail($user)
    {
        $manager = $user->manager()

                ->where('position', 'manager')
                ->select('first_name', 'last_name', 'email', 'mobile_code', 'mobile', 'skype')
                ->first();
        if ($user && $user->role == 'user' && $manager) {
            $settings = new \App\Model\Common\Setting();
            $setting = $settings->first();
            $from = $setting->email;
            $to = $user->email;
            $templates = new \App\Model\Common\Template();
            $template = $templates
                    ->join('template_types', 'templates.type', '=', 'template_types.id')
                    ->where('template_types.name', '=', 'manager_email')
                    ->select('templates.data', 'templates.name')
                    ->first();
            $template_data = $template->data;
            $template_name = $template->name;
            $template_controller = new \App\Http\Controllers\Common\TemplateController();
            $replace = [
                'name'               => $user->first_name.' '.$user->last_name,
                'manager_first_name' => $manager->first_name,
                'manager_last_name'  => $manager->last_name,
                'manager_email'      => $manager->email,
                'manager_code'       => $manager->mobile_code,
                'manager_mobile'     => $manager->mobile,
                'manager_skype'      => $manager->skype,
            ];
            //dd($from, $to, $template_data, $template_name, $replace);
            $template_controller->mailing($from, $to, $template_data, $template_name, $replace, 'manager_email');
        }
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        event(new Registered($user = $this->create($request->all())));

        $this->guard()->login($user);

        return $this->registered($request, $user)
                        ?: redirect($this->redirectPath());
    }

    /**
     * Get the guard to be used during registration.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard();
    }

    /**
     * The user has been registered.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function registered(Request $request, $user)
    {
        //
    }
}
